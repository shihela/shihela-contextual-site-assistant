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

		// Translate history array to WP AI Client DTOs
		$wp_history = array();
		if ( is_array( $chat_history ) ) {
			foreach ( $chat_history as $msg ) {
				$role = isset( $msg['role'] ) ? $msg['role'] : 'user';
				$content = isset( $msg['content'] ) ? $msg['content'] : '';

				if ( 'user' === $role ) {
					$wp_history[] = new UserMessage( array( new MessagePart( $content ) ) );
				} elseif ( 'assistant' === $role || 'model' === $role ) {
					$wp_history[] = new ModelMessage( array( new MessagePart( $content ) ) );
				}
			}
		}

		$response = '';
		$temperature = (float) get_option( 'shihela_contextual_site_assistant_temperature', 0.3 );

		// 1. Try fully configured builder (native system instructions + temperature + history)
		$builder = wp_ai_client_prompt( $message )
			->using_system_instruction( $system_prompt )
			->using_temperature( $temperature );
		if ( ! empty( $wp_history ) ) {
			$builder->with_history( ...$wp_history );
		}
		if ( $builder->is_supported_for_text_generation() ) {
			$response = $builder->generate_text();
		} elseif ( true ) {
			// 2. Fallback: Try without temperature (system instructions + history)
			$builder = wp_ai_client_prompt( $message )
				->using_system_instruction( $system_prompt );
			if ( ! empty( $wp_history ) ) {
				$builder->with_history( ...$wp_history );
			}
			if ( $builder->is_supported_for_text_generation() ) {
				$response = $builder->generate_text();
			} elseif ( true ) {
				// 3. Fallback: Prepend system prompt to user message (no native system instructions, keep temperature + history)
				$appended_message = $system_prompt . "\n\n" . $message;
				$builder = wp_ai_client_prompt( $appended_message )
					->using_temperature( $temperature );
				if ( ! empty( $wp_history ) ) {
					$builder->with_history( ...$wp_history );
				}
				if ( $builder->is_supported_for_text_generation() ) {
					$response = $builder->generate_text();
				} elseif ( true ) {
					// 4. Fallback: Prepend system prompt, no temperature, keep history
					$builder = wp_ai_client_prompt( $appended_message );
					if ( ! empty( $wp_history ) ) {
						$builder->with_history( ...$wp_history );
					}
					if ( $builder->is_supported_for_text_generation() ) {
						$response = $builder->generate_text();
					} elseif ( true ) {
						// 5. Fallback: Format history manually inside prompt text (no native history, keep temperature)
						$manual_history_prompt = $system_prompt . "\n\n";
						if ( ! empty( $chat_history ) ) {
							$manual_history_prompt .= "Conversation History:\n" . $this->format_history_as_text( $chat_history ) . "\n";
						}
						$manual_history_prompt .= "Visitor: " . $message . "\nAssistant:";

						$builder = wp_ai_client_prompt( $manual_history_prompt )
							->using_temperature( $temperature );
						if ( $builder->is_supported_for_text_generation() ) {
							$response = $builder->generate_text();
						} elseif ( true ) {
							// 6. Fallback: Most basic prompt
							$builder = wp_ai_client_prompt( $manual_history_prompt );
							if ( $builder->is_supported_for_text_generation() ) {
								$response = $builder->generate_text();
							} else {
								$response = $base_builder->generate_text();
							}
						}
					}
				}
			}
		}

		if ( is_wp_error( $response ) ) {
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
			if ( $post ) {
				$title           = $post->post_title;
				$permalink       = get_permalink( $post_id );
				$excerpt         = $this->sanitize_page_text( $post->post_excerpt );
				$raw_content     = $post->post_content;
				
				// Clean raw content first, THEN trim to 600 pure text words
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
