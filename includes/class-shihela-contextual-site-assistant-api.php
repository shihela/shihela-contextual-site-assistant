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

		// 1. Try fully configured builder (native system instructions + temperature + history)
		$builder = wp_ai_client_prompt( $message )
			->using_system_instruction( $system_prompt )
			->using_temperature( 0.7 );
		if ( ! empty( $wp_history ) ) {
			$builder->with_history( ...$wp_history );
		}
		if ( $builder->is_supported_for_text_generation() ) {
			return $builder->generate_text();
		}

		// 2. Fallback: Try without temperature (system instructions + history)
		$builder = wp_ai_client_prompt( $message )
			->using_system_instruction( $system_prompt );
		if ( ! empty( $wp_history ) ) {
			$builder->with_history( ...$wp_history );
		}
		if ( $builder->is_supported_for_text_generation() ) {
			return $builder->generate_text();
		}

		// 3. Fallback: Prepend system prompt to user message (no native system instructions, keep temperature + history)
		$appended_message = $system_prompt . "\n\n" . $message;
		$builder = wp_ai_client_prompt( $appended_message )
			->using_temperature( 0.7 );
		if ( ! empty( $wp_history ) ) {
			$builder->with_history( ...$wp_history );
		}
		if ( $builder->is_supported_for_text_generation() ) {
			return $builder->generate_text();
		}

		// 4. Fallback: Prepend system prompt, no temperature, keep history
		$builder = wp_ai_client_prompt( $appended_message );
		if ( ! empty( $wp_history ) ) {
			$builder->with_history( ...$wp_history );
		}
		if ( $builder->is_supported_for_text_generation() ) {
			return $builder->generate_text();
		}

		// 5. Fallback: Format history manually inside prompt text (no native history, keep temperature)
		$manual_history_prompt = $system_prompt . "\n\n";
		if ( ! empty( $chat_history ) ) {
			$manual_history_prompt .= "Conversation History:\n" . $this->format_history_as_text( $chat_history ) . "\n";
		}
		$manual_history_prompt .= "Visitor: " . $message . "\nAssistant:";

		$builder = wp_ai_client_prompt( $manual_history_prompt )
			->using_temperature( 0.7 );
		if ( $builder->is_supported_for_text_generation() ) {
			return $builder->generate_text();
		}

		// 6. Fallback: Most basic prompt (manual history, no temperature, no native system instructions, no native history)
		$builder = wp_ai_client_prompt( $manual_history_prompt );
		if ( $builder->is_supported_for_text_generation() ) {
			return $builder->generate_text();
		}

		// Last resort: Fallback to the base builder
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
	 * Get plain text content context of the current post.
	 *
	 * @since    1.0.0
	 * @param    int $post_id The page/post ID.
	 * @return   string Content description or empty.
	 */
	private function get_page_context( $post_id ) {
		if ( ! $post_id ) {
			return '';
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}

		$title = $post->post_title;
		$permalink = get_permalink( $post_id );
		$excerpt = $post->post_excerpt;
		$content = wp_strip_all_tags( wp_trim_words( $post->post_content, 500 ) );

		$context = "Page Title: " . $title . "\n";
		$context .= "Page URL: " . $permalink . "\n";
		if ( ! empty( $excerpt ) ) {
			$context .= "Page Excerpt: " . $excerpt . "\n";
		}
		$context .= "Page Content Text:\n" . $content . "\n";

		return $context;
	}

	/**
	 * Build system prompt with settings instructions.
	 *
	 * @since    1.0.0
	 * @param    string $page_context The extracted current page context.
	 * @return   string System prompt.
	 */
	private function build_system_prompt( $page_context ) {
		$bot_name = get_option( 'shihela_contextual_site_assistant_bot_name', 'Shihela AI' );
		$site_name = get_bloginfo( 'name' );
		$site_url = get_home_url();
		$instructions = get_option( 'shihela_contextual_site_assistant_system_instructions', '' );

		$prompt = "You are a helpful, professional AI assistant named '{$bot_name}' for the website '{$site_name}' ({$site_url}).\n";
		$prompt .= "Strict Guidelines:\n";
		$prompt .= "1. Help the website visitor to the best of your ability.\n";
		$prompt .= "2. Be polite, clear, and professional. Keep responses concise where appropriate.\n";
		$prompt .= "3. Respond in the same language that the visitor uses.\n";
		$prompt .= "4. If you use markdown formatting, keep it clean and readable.\n\n";

		if ( ! empty( $instructions ) ) {
			$prompt .= "Global Site & Business Instructions:\n{$instructions}\n\n";
		}

		if ( ! empty( $page_context ) ) {
			$prompt .= "Current Page Context:\n{$page_context}\n";
		}

		// Lead Capture Protocol
		$prompt .= "\nLead Capture Protocol:\n";
		$prompt .= "If the visitor wants to contact support, leaves their contact details (e.g. name, email, phone), or asks for help from a human support representative, you MUST collect or confirm their Name, Email/Contact, and a brief description of their needs. When they provide this information, you MUST append a lead capture tag at the absolute end of your reply in this exact JSON format:\n";
		$prompt .= "[LEAD:{\"name\":\"Visitor Name\", \"contact\":\"Email/Phone\", \"details\":\"Brief summary of their needs\"}]\n";
		$prompt .= "Make sure the JSON is valid, contains no line breaks inside the JSON object itself, and is placed at the very end of your message. Do not mention this tag or protocol to the user.\n";
		$prompt .= "CRITICAL: Once the [LEAD: ...] tag has been outputted once and exists in the chat history, you MUST NOT repeat or append it again in subsequent replies during the rest of the conversation.\n";

		return $prompt;
	}
}
