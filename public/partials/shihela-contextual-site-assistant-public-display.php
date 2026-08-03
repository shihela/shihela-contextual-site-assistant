<?php
/**
 * Public HTML template for the floating chat assistant widget.
 *
 * @link       https://shihela.com/contextual-site-assistant
 * @since      1.0.0
 *
 * @package    Shihela_Contextual_Site_Assistant
 * @subpackage Shihela_Contextual_Site_Assistant/public/partials
 */

// Block direct access
if ( ! defined( 'WPINC' ) ) {
	die;
}

$shihela_contextual_site_assistant_bot_name        = get_option( 'shihela_contextual_site_assistant_bot_name', 'Shihela AI' );
$shihela_contextual_site_assistant_welcome_message = get_option( 'shihela_contextual_site_assistant_welcome_message', 'Hello! I am your AI assistant. How can I help you today?' );
$shihela_contextual_site_assistant_theme_color     = get_option( 'shihela_contextual_site_assistant_theme_color', '#4f46e5' );
$shihela_contextual_site_assistant_widget_position = get_option( 'shihela_contextual_site_assistant_widget_position', 'bottom-right' );
?>

<div id="shihela-contextual-site-assistant-widget-root" class="shihela-contextual-site-assistant-widget-root shihela-position-<?php echo esc_attr( $shihela_contextual_site_assistant_widget_position ); ?>" style="--shihela-accent: <?php echo esc_attr( $shihela_contextual_site_assistant_theme_color ); ?>;">
	<!-- Floating Launcher Button -->
	<button id="shihela-contextual-site-assistant-launcher" class="shihela-contextual-site-assistant-launcher" aria-label="<?php esc_attr_e( 'Open Chat Assistant', 'shihela-contextual-site-assistant' ); ?>">
		<svg class="shihela-contextual-site-assistant-icon-chat" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M12 21C16.9706 21 21 16.9706 21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12C3 13.8055 3.53327 15.4859 4.45037 16.8929L3.02942 20.4452C2.93049 20.6926 3.01819 20.9734 3.23849 21.1147C3.34444 21.1827 3.46672 21.218 3.58988 21.218C3.69389 21.218 3.79782 21.1923 3.89222 21.1405L7.7126 19.0494C8.98906 19.6706 10.4357 20 12 20C12 20.3333 12 20.6667 12 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
			<path d="M8 12H8.01M12 12H12.01M16 12H16.01" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
		</svg>
		<svg class="shihela-contextual-site-assistant-icon-close" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: none;">
			<path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
		</svg>
	</button>

	<!-- Chat Box Panel -->
	<div id="shihela-contextual-site-assistant-panel" class="shihela-contextual-site-assistant-panel hidden">
		<!-- Header -->
		<header class="shihela-contextual-site-assistant-panel-header">
			<div class="shihela-contextual-site-assistant-header-bot-info">
				<div class="shihela-contextual-site-assistant-avatar">
					<span><?php echo esc_html( strtoupper( substr( $shihela_contextual_site_assistant_bot_name, 0, 1 ) ) ); ?></span>
					<span class="shihela-contextual-site-assistant-online-indicator"></span>
				</div>
				<div class="shihela-contextual-site-assistant-bot-details">
					<h3 class="shihela-contextual-site-assistant-bot-title"><?php echo esc_html( $shihela_contextual_site_assistant_bot_name ); ?></h3>
					<p class="shihela-contextual-site-assistant-bot-subtitle"><?php esc_html_e( 'Assistant Support', 'shihela-contextual-site-assistant' ); ?></p>
				</div>
			</div>
			<div class="shihela-contextual-site-assistant-header-actions" style="display: flex; align-items: center; gap: 6px;">
				<?php
				$shihela_wa_num    = get_option( 'shihela_contextual_site_assistant_whatsapp_number', '' );
				$shihela_wa_header = get_option( 'shihela_contextual_site_assistant_whatsapp_header_btn', '1' );
				if ( ! empty( $shihela_wa_num ) && '1' === $shihela_wa_header ) :
				?>
					<button id="shihela-contextual-site-assistant-header-wa-btn" class="shihela-contextual-site-assistant-header-wa-btn" aria-label="<?php esc_attr_e( 'Contact CS on WhatsApp', 'shihela-contextual-site-assistant' ); ?>" title="<?php esc_attr_e( 'Chat via WhatsApp', 'shihela-contextual-site-assistant' ); ?>">
						<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
							<path d="M12.012 2C6.5 2 2.012 6.48 2.012 12c0 2.07.63 3.99 1.71 5.58L2 22l4.56-1.68c1.53.97 3.35 1.54 5.44 1.54 5.51 0 10-4.48 10-10S17.522 2 12.012 2zm.01 17.86c-1.8 0-3.47-.51-4.9-1.39l-.35-.21-2.71.99.88-2.61-.23-.37a7.84 7.84 0 0 1-1.22-4.27c0-4.34 3.53-7.86 7.87-7.86 4.34 0 7.86 3.52 7.86 7.86 0 4.34-3.52 7.86-7.86 7.86zm4.31-5.88c-.24-.12-1.41-.7-1.63-.78-.22-.08-.38-.12-.54.12-.16.24-.62.78-.76.94-.14.16-.28.18-.52.06-.24-.12-1.01-.37-1.93-1.19-.71-.63-1.19-1.41-1.33-1.65-.14-.24-.01-.37.11-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.54-1.31-.74-1.79-.2-.47-.4-.41-.54-.42h-.46c-.16 0-.42.06-.64.3-.22.24-.85.83-.85 2.03 0 1.2.87 2.36 1 2.52.12.16 1.72 2.63 4.17 3.69.58.25 1.04.4 1.39.51.59.19 1.12.16 1.54.1.47-.07 1.41-.58 1.61-1.14.2-.56.2-1.04.14-1.14-.06-.1-.22-.16-.46-.28z"/>
						</svg>
					</button>
				<?php endif; ?>
				<button id="shihela-contextual-site-assistant-reset-chat" class="shihela-contextual-site-assistant-reset-chat" aria-label="<?php esc_attr_e( 'Reset conversation', 'shihela-contextual-site-assistant' ); ?>" title="<?php esc_attr_e( 'Reset Chat', 'shihela-contextual-site-assistant' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M4 4V9H4.5M4.5 9C5.39567 7.07223 7.07065 5.6174 9.12353 4.90807C11.1764 4.19875 13.4357 4.29367 15.4223 5.17242C17.4089 6.05118 18.96 7.64095 19.7423 9.59972C20.5247 11.5585 20.4739 13.7258 19.6008 15.645C18.7276 17.5642 17.103 19.0805 15.0768 19.8659C13.0506 20.6514 10.7937 20.6408 8.79093 19.8364C6.78813 19.0321 5.20455 17.498 4.38139 15.5656C3.55823 13.6332 3.56272 11.4589 4.39396 9.5M4.5 9H9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</button>
				<button id="shihela-contextual-site-assistant-close-panel" class="shihela-contextual-site-assistant-close-panel" aria-label="<?php esc_attr_e( 'Close chat', 'shihela-contextual-site-assistant' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</button>
			</div>
		</header>

		<!-- Messages Body -->
		<div id="shihela-contextual-site-assistant-messages-body" class="shihela-contextual-site-assistant-messages-body">
			<div class="shihela-contextual-site-assistant-message assistant">
				<div class="shihela-contextual-site-assistant-message-bubble">
					<?php echo esc_html( $shihela_contextual_site_assistant_welcome_message ); ?>
				</div>
				<span class="shihela-contextual-site-assistant-message-time"><?php echo esc_html( date_i18n( get_option( 'time_format' ) ) ); ?></span>
			</div>
		</div>

		<!-- Quick Suggestion Chips Container -->
		<div id="shihela-contextual-site-assistant-chips-container" class="shihela-contextual-site-assistant-chips-container"></div>

		<!-- Footer Input Field -->
		<footer class="shihela-contextual-site-assistant-panel-footer">
			<form id="shihela-contextual-site-assistant-chat-form" class="shihela-contextual-site-assistant-chat-form">
				<div style="position: absolute; left: -9999px; overflow: hidden; height: 1px; width: 1px;">
					<input type="text" id="shihela-hp" name="shihela_hp" tabindex="-1" autocomplete="off" aria-hidden="true">
				</div>
				<input type="text" id="shihela-contextual-site-assistant-chat-input" class="shihela-contextual-site-assistant-chat-input" placeholder="<?php esc_attr_e( 'Type your question here...', 'shihela-contextual-site-assistant' ); ?>" required autocomplete="off">
				<button type="submit" id="shihela-contextual-site-assistant-chat-submit" class="shihela-contextual-site-assistant-chat-submit" aria-label="<?php esc_attr_e( 'Send message', 'shihela-contextual-site-assistant' ); ?>">
					<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M22 2L11 13M22 2L15 22L11 13M11 13L2 9L22 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</button>
			</form>
		</footer>
	</div>
</div>
