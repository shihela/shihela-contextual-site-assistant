<?php
/**
 * Handles communication with native WordPress AI Client
 *
 * @link       https://shihela.com/contextual-site-assistant
 * @since      1.0.0
 *
 * @package    Shihela_Contextual_Site_Assistant
 * @subpackage Shihela_Contextual_Site_Assistant/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WordPress\AiClient\Messages\DTO\UserMessage;
use WordPress\AiClient\Messages\DTO\ModelMessage;
use WordPress\AiClient\Messages\DTO\MessagePart;

/**
 * Handles AI API integration via WP AI Client.
 *
 * @since      1.0.0
 * @package    Shihela_Contextual_Site_Assistant
 * @subpackage Shihela_Contextual_Site_Assistant/includes
 * @author     shihela
 */
class Shihela_Contextual_Site_Assistant_API {

	/**
	 * Send a chat message with context and history via native WP AI Client.
	 *
	 * @since    1.0.0
	 * @param    string $message       The user's query.
	 * @param    int    $post_id       The current page/post ID for context.
	 * @param    array  $chat_history  Array of previous chat messages.
	 * @return   string|WP_Error       The AI text response or WP_Error.
	 */
	public function get_response( $message, $post_id = 0, $chat_history = array() ) {
		if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
			return new WP_Error( 'ai_client_missing', __( 'Native WordPress AI Client is not available. This plugin requires WordPress 7.0 or higher.', 'shihela-contextual-site-assistant' ) );
		}

		// Check if text generation is supported at all by any configured provider/model
		$base_builder = wp_ai_client_prompt( $message );
		if ( ! $base_builder->is_supported_for_text_generation() ) {
			return new WP_Error(
				'no_active_provider',
				__( 'No active AI models found that support text generation. Please configure an AI Provider in your WordPress dashboard under Settings > Connectors.', 'shihela-contextual-site-assistant' )
			);
		}

		// Compile page and global context
		$page_context = $this->get_page_context( $post_id );
		$system_prompt = $this->build_system_prompt( $page_context );

		// Translate history array to WP AI Client DTOs (guarded for class availability)
		$wp_history      = array();
		$has_history_dto = class_exists( UserMessage::class ) && class_exists( ModelMessage::class ) && class_exists( MessagePart::class );
		if ( $has_history_dto && is_array( $chat_history ) ) {
			foreach ( $chat_history as $msg ) {
				$role    = isset( $msg['role'] ) ? $msg['role'] : 'user';
				$content = isset( $msg['content'] ) ? $msg['content'] : '';

				if ( '' === $content ) {
					continue;
				}

				if ( 'user' === $role ) {
					$wp_history[] = new UserMessage( array( new MessagePart( $content ) ) );
				} elseif ( 'assistant' === $role || 'model' === $role ) {
					$wp_history[] = new ModelMessage( array( new MessagePart( $content ) ) );
				}
			}
		}

		$temperature = (float) get_option( 'shihela_contextual_site_assistant_temperature', 0.3 );
		$response    = $this->execute_with_fallback( $message, $system_prompt, $wp_history, $chat_history, $temperature, $base_builder );

		if ( is_wp_error( $response ) ) {
			/**
			 * Action hook fired when AI text generation fails.
			 *
			 * @since 1.4.0
			 * @param WP_Error $response Error object from WP AI Client.
			 * @param string   $message  User query message.
			 * @param int      $post_id  Current page/post ID.
			 */
			do_action( 'shihela_assistant_ai_error', $response, $message, $post_id );
			return $response;
		}

		/**
		 * Filter the generated response text before sending back to the visitor.
		 *
		 * @since 1.1.0
		 * @param string $response The AI generated text response.
		 * @param string $message  The user query message.
		 * @param int    $post_id  The current page/post ID.
		 */
		return apply_filters( 'shihela_assistant_chat_response', $response, $message, $post_id );
	}

	/**
	 * Execute prompt generation with graceful capability negotiation across LLM providers.
	 *
	 * @since    1.4.0
	 * @param    string $message       User query message.
	 * @param    string $system_prompt Compiled system prompt.
	 * @param    array  $wp_history    Array of WP AI Client message DTOs.
	 * @param    array  $chat_history  Raw sanitized chat history array.
	 * @param    float  $temperature   Model temperature.
	 * @param    object $base_builder  Pre-checked base prompt builder.
	 * @return   string|WP_Error       AI response text or WP_Error.
	 */
	private function execute_with_fallback( $message, $system_prompt, $wp_history, $chat_history, $temperature, $base_builder ) {
		$appended_message      = $system_prompt . "\n\n" . $message;
		$manual_history_prompt = $system_prompt . "\n\n";
		if ( ! empty( $chat_history ) ) {
			$manual_history_prompt .= "Conversation History:\n" . $this->format_history_as_text( $chat_history ) . "\n";
		}
		$manual_history_prompt .= 'Visitor: ' . $message . "\nAssistant:";

		$strategies = array(
			// 1. Full native capabilities: system instruction + temperature + DTO history.
			array(
				'prompt'      => $message,
				'system'      => true,
				'temperature' => true,
				'history'     => true,
			),
			// 2. Native system instruction + DTO history (without temperature).
			array(
				'prompt'      => $message,
				'system'      => true,
				'temperature' => false,
				'history'     => true,
			),
			// 3. Inline system prompt + temperature + DTO history.
			array(
				'prompt'      => $appended_message,
				'system'      => false,
				'temperature' => true,
				'history'     => true,
			),
			// 4. Inline system prompt + DTO history (without temperature).
			array(
				'prompt'      => $appended_message,
				'system'      => false,
				'temperature' => false,
				'history'     => true,
			),
			// 5. Manual history prompt + temperature.
			array(
				'prompt'      => $manual_history_prompt,
				'system'      => false,
				'temperature' => true,
				'history'     => false,
			),
			// 6. Basic manual history prompt.
			array(
				'prompt'      => $manual_history_prompt,
				'system'      => false,
				'temperature' => false,
				'history'     => false,
			),
		);

		foreach ( $strategies as $strategy ) {
			$builder = wp_ai_client_prompt( $strategy['prompt'] );

			if ( $strategy['system'] ) {
				$builder = $builder->using_system_instruction( $system_prompt );
			}
			if ( $strategy['temperature'] ) {
				$builder = $builder->using_temperature( $temperature );
			}
			if ( $strategy['history'] && ! empty( $wp_history ) ) {
				$builder->with_history( ...$wp_history );
			}

			if ( $builder->is_supported_for_text_generation() ) {
				return $builder->generate_text();
			}
		}

		return $base_builder->generate_text();
	}

	/**
	 * Format chat history as a plain text string for manual injection fallback.
	 *
	 * @since    1.0.0
	 * @param    array $chat_history Array of previous chat messages.
	 * @return   string Formatted plain text conversation history.
	 */
	private function format_history_as_text( $chat_history ) {
		$text = '';
		if ( is_array( $chat_history ) ) {
			foreach ( $chat_history as $msg ) {
				$role    = isset( $msg['role'] ) ? $msg['role'] : 'user';
				$content = isset( $msg['content'] ) ? $msg['content'] : '';
				$speaker = ( 'user' === $role ) ? 'Visitor' : 'Assistant';
				$text   .= $speaker . ': ' . $content . "\n";
			}
		}
		return $text;
	}

	/**
	 * Get plain text content context of the current post with comprehensive sanitization.
	 *
	 * @since    1.0.0
	 * @param    int $post_id The page/post ID.
	 * @return   string Content description or empty.
	 */
	private function get_page_context( $post_id ) {
		$context = '';

		if ( $post_id ) {
			$post = get_post( $post_id );
			if ( $post instanceof WP_Post && $this->can_read_post_context( $post ) ) {
				$title           = $post->post_title;
				$permalink       = get_permalink( $post_id );
				$excerpt         = $this->sanitize_page_text( $post->post_excerpt );
				$raw_content     = $post->post_content;
				
				// Clean raw content first, THEN trim to 1,000 pure text words
				$clean_content   = $this->sanitize_page_text( $raw_content );
				$trimmed_content = wp_trim_words( $clean_content, 1000, '...' );

				$context  = "Page Title: " . $title . "\n";
				$context .= "Page URL: " . $permalink . "\n";

				// Extract Post Taxonomies (Categories & Tags)
				$categories = get_the_category( $post_id );
				if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
					$cat_names = wp_list_pluck( $categories, 'name' );
					$context .= "Categories: " . implode( ', ', $cat_names ) . "\n";
				}
				$tags = get_the_tags( $post_id );
				if ( ! empty( $tags ) && ! is_wp_error( $tags ) ) {
					$tag_names = wp_list_pluck( $tags, 'name' );
					$context .= "Tags: " . implode( ', ', $tag_names ) . "\n";
				}

				if ( ! empty( $excerpt ) ) {
					$context .= "Page Excerpt: " . $excerpt . "\n";
				}
				if ( ! empty( $trimmed_content ) ) {
					$context .= "Page Content Text:\n" . $trimmed_content . "\n";
				}

				// Basic Read-Only WooCommerce Product Context (skipped if PRO Commerce engine is active to avoid duplication).
				$wc_context = $this->get_woocommerce_product_context( $post );
				if ( ! empty( $wc_context ) ) {
					$context .= "\n" . $wc_context;
				}
			}
		}

		/**
		 * Filter the extracted page context before generating AI response.
		 *
		 * @since 1.1.0
		 * @param string $context Computed page context text.
		 * @param int    $post_id Current post ID.
		 */
		return apply_filters( 'shihela_assistant_chat_context', $context, $post_id );
	}

	/**
	 * Extract basic read-only WooCommerce product details (price, stock, SKU, attributes) for the current product page.
	 *
	 * @since    1.5.0
	 * @param    WP_Post $post Verified viewable post object.
	 * @return   string Formatted WooCommerce product context or empty string.
	 */
	private function get_woocommerce_product_context( $post ) {
		if ( ! function_exists( 'wc_get_product' ) || 'product' !== $post->post_type ) {
			return '';
		}

		// Yield to PRO Commerce 2.0 engine when active so product details are not duplicated.
		if ( get_option( 'shihela_pro_wc_enable_commerce', false ) ) {
			return '';
		}

		$product = wc_get_product( $post->ID );
		if ( ! $product ) {
			return '';
		}

		$sku = $product->get_sku();
		if ( $product->is_on_sale() && $product->get_regular_price() ) {
			$price_raw = sprintf(
				'%1$s (Regular: %2$s)',
				wc_price( $product->get_price() ),
				wc_price( $product->get_regular_price() )
			);
		} else {
			$price_raw = $product->get_price_html() ? $product->get_price_html() : wc_price( $product->get_price() );
		}
		$price      = trim( html_entity_decode( wp_strip_all_tags( (string) $price_raw ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		$stock      = $product->is_in_stock() ? 'In Stock' : 'Out of Stock';
		$short_desc = $this->sanitize_page_text( (string) $product->get_short_description() );

		$wc_context  = "WooCommerce Product Details:\n";
		$wc_context .= 'Product Name: ' . $product->get_name() . "\n";
		if ( ! empty( $sku ) ) {
			$wc_context .= 'SKU: ' . $sku . "\n";
		}
		if ( ! empty( $price ) ) {
			$wc_context .= 'Price: ' . $price . "\n";
		}
		$wc_context .= 'Availability: ' . $stock . "\n";

		$terms = get_the_terms( $post->ID, 'product_cat' );
		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			$wc_context .= 'Product Categories: ' . implode( ', ', wp_list_pluck( $terms, 'name' ) ) . "\n";
		}

		if ( ! empty( $short_desc ) ) {
			$wc_context .= 'Product Summary: ' . $short_desc . "\n";
		}

		$attributes = $product->get_attributes();
		if ( ! empty( $attributes ) ) {
			$attr_lines = array();
			foreach ( $attributes as $attribute ) {
				if ( $attribute->is_taxonomy() ) {
					$attr_terms = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) );
					if ( ! empty( $attr_terms ) && ! is_wp_error( $attr_terms ) ) {
						$attr_lines[] = wc_attribute_label( $attribute->get_name() ) . ': ' . implode( ', ', $attr_terms );
					}
				} else {
					$options = $attribute->get_options();
					if ( ! empty( $options ) ) {
						$attr_lines[] = $attribute->get_name() . ': ' . implode( ', ', $options );
					}
				}
			}
			if ( ! empty( $attr_lines ) ) {
				$wc_context .= 'Attributes: ' . implode( ' | ', $attr_lines ) . "\n";
			}
		}

		return $wc_context;
	}

	/**
	 * Verify whether the current visitor is allowed to read a post's context.
	 * Prevents IDOR exposure of private, draft, password-protected, or non-public CPT content.
	 *
	 * @since    1.4.0
	 * @param    WP_Post $post Post object to check.
	 * @return   bool True if safe to expose as AI context, false otherwise.
	 */
	private function can_read_post_context( $post ) {
		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		// Must be a publicly viewable post type (excludes orders, revisions, nav items, internal CPTs).
		if ( ! is_post_type_viewable( $post->post_type ) ) {
			return false;
		}

		// Never expose password-protected post content via REST API.
		if ( ! empty( $post->post_password ) || post_password_required( $post ) ) {
			return false;
		}

		$status = get_post_status( $post );
		if ( 'publish' === $status ) {
			return true;
		}

		// Allow non-published posts only if the authenticated user explicitly has read permission.
		return is_user_logged_in() && current_user_can( 'read_post', $post->ID );
	}

	/**
	 * Clean HTML tags, shortcodes, scripts, styles, and extra whitespaces from raw text content.
	 *
	 * @since    1.2.0
	 * @param    string $content Raw content string.
	 * @return   string Cleaned text.
	 */
	private function sanitize_page_text( $content ) {
		if ( empty( $content ) ) {
			return '';
		}

		// 1. Remove shortcode tags
		$content = strip_shortcodes( $content );

		// 2. Remove script and style tags along with their inner content
		$content = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $content );
		$content = preg_replace( '/<style\b[^>]*>(.*?)<\/style>/is', '', $content );

		// 3. Convert HTML line break tags to actual newlines before stripping HTML tags
		$content = preg_replace( '/<br\s*\/?>/i', "\n", $content );
		$content = preg_replace( '/<\/p>/i', "\n\n", $content );
		$content = preg_replace( '/<\/h[1-6]>/i', "\n\n", $content );
		$content = preg_replace( '/<\/li>/i', "\n", $content );

		// 4. Strip all remaining HTML tags securely
		$content = wp_strip_all_tags( $content, true );

		// 5. Decode HTML entities (&amp;, &quot;, &nbsp;, etc.)
		$content = html_entity_decode( $content, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		// 6. Normalize whitespaces (tabs, non-breaking spaces, excessive spaces and newlines)
		$content = str_replace( array( "\r\n", "\r" ), "\n", $content );
		$content = preg_replace( '/[ \t]+/', ' ', $content );
		$content = preg_replace( '/\n[ \t]+/', "\n", $content );
		$content = preg_replace( '/\n{3,}/', "\n\n", $content );

		return trim( $content );
	}

	/**
	 * Build system prompt with settings instructions.
	 *
	 * @since    1.0.0
	 * @param    string $page_context The extracted current page context.
	 * @return   string System prompt.
	 */
	private function build_system_prompt( $page_context ) {
		$bot_name     = get_option( 'shihela_contextual_site_assistant_bot_name', 'Shihela AI' );
		$site_name    = get_bloginfo( 'name' );
		$site_url     = get_home_url();
		$instructions = get_option( 'shihela_contextual_site_assistant_system_instructions', '' );

		$prompt  = "You are a helpful, polite, and strictly factual AI assistant named '{$bot_name}' for the website '{$site_name}' ({$site_url}).\n\n";
		$prompt .= "STRICT CONTEXTUAL GROUNDING & ANTI-HALLUCINATION RULES:\n";
		$prompt .= "1. Answer the visitor's query ONLY using the information provided inside the <current_page_context> block and the Global Site & Business Instructions below.\n";
		$prompt .= "2. DO NOT use pre-trained outside knowledge, assumptions, or invent facts/prices/policies/features that are not explicitly stated in the context.\n";
		$prompt .= "3. For short or general questions (e.g. 'Berapa harganya?', 'Ada garansi?', 'Bisa kirim ke mana?', 'Fiturnya apa aja?'), interpret them as strictly referring to the specific page/product described in <current_page_context>.\n";
		$prompt .= "4. IF THE REQUESTED INFORMATION IS NOT FOUND in the context, DO NOT guess or invent an answer. Politely state that the information is not available on this page (e.g. 'Maaf, informasi tersebut tidak tercantum pada halaman ini.') and offer to assist with available page details or connect them to human support.\n";
		$prompt .= "5. Always respond in the exact same language used by the visitor.\n";
		$prompt .= "6. Keep responses clean, concise, and professional using readable Markdown formatting.\n\n";

		if ( ! empty( $instructions ) ) {
			$prompt .= "Global Site & Business Instructions:\n{$instructions}\n\n";
		}

		if ( ! empty( $page_context ) ) {
			$prompt .= "<current_page_context>\n{$page_context}\n</current_page_context>\n";
		} else {
			$prompt .= "<current_page_context>\nNo specific page context available. Answer general site questions based on Global Site & Business Instructions.\n</current_page_context>\n";
		}

		// Lead Capture Protocol
		$prompt .= "\nLead Capture Protocol:\n";
		$prompt .= "If the visitor wants to contact support, leaves their contact details (e.g. name, email, phone), asks for help from a human support representative, OR asks a question whose answer is NOT found in the page context, you MUST collect or confirm their Name, Email/Contact, and a brief description of their needs. When they provide this information, you MUST append a lead capture tag at the absolute end of your reply in this exact JSON format:\n";
		$prompt .= "[LEAD:{\"name\":\"Visitor Name\", \"contact\":\"Email/Phone\", \"details\":\"Brief summary of their needs\"}]\n";
		$prompt .= "Make sure the JSON is valid, contains no line breaks inside the JSON object itself, and is placed at the very end of your message. Do not mention this tag or protocol to the user.\n";
		$prompt .= "CRITICAL: Once the [LEAD: ...] tag has been outputted once and exists in the chat history, you MUST NOT repeat or append it again in subsequent replies during the rest of the conversation.\n";

		// WhatsApp CS Protocol (v1.2.0+)
		$wa_number = get_option( 'shihela_contextual_site_assistant_whatsapp_number', '' );
		if ( ! empty( $wa_number ) ) {
			$prompt .= "\nWhatsApp Support Protocol:\n";
			$prompt .= "If the visitor asks to speak with customer support, contact admin, asks for WhatsApp/phone number, requires direct human assistance, OR asks a question whose answer is NOT found in the page context, you MUST offer to connect them via WhatsApp and append the tag [WHATSAPP_BTN] (or [WHATSAPP_BTN: custom button label]) in your reply. The system will render this tag as an interactive WhatsApp button.\n";
		}

		/**
		 * Filter system prompt instructions before sending to AI Client.
		 *
		 * @since 1.1.0
		 * @param string $prompt       Compiled system prompt string.
		 * @param string $page_context Current page context.
		 */
		return apply_filters( 'shihela_assistant_system_prompt', $prompt, $page_context );
	}
}
