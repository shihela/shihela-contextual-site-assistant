<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://shihela.com/contextual-site-assistant
 * @since      1.0.0
 *
 * @package    Shihela_Contextual_Site_Assistant
 * @subpackage Shihela_Contextual_Site_Assistant/admin/partials
 */

// Block direct access
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Retrieve current options values
$shihela_contextual_site_assistant_bot_name            = get_option( 'shihela_contextual_site_assistant_bot_name', 'Shihela AI' );
$shihela_contextual_site_assistant_welcome_message     = get_option( 'shihela_contextual_site_assistant_welcome_message', 'Hello! I am your AI assistant. How can I help you today?' );
$shihela_contextual_site_assistant_system_instructions = get_option( 'shihela_contextual_site_assistant_system_instructions', '' );
$shihela_contextual_site_assistant_theme_color         = get_option( 'shihela_contextual_site_assistant_theme_color', '#4f46e5' );
$shihela_contextual_site_assistant_widget_position     = get_option( 'shihela_contextual_site_assistant_widget_position', 'bottom-right' );
$shihela_contextual_site_assistant_webhook_url         = get_option( 'shihela_contextual_site_assistant_webhook_url', '' );
?>

<div class="wrap shihela-contextual-site-assistant-admin-wrap">
	<header class="shihela-contextual-site-assistant-admin-header">
		<div class="shihela-contextual-site-assistant-logo-area">
			<span class="shihela-contextual-site-assistant-dashicon dashicons dashicons-text-page"></span>
			<h1><?php esc_html_e( 'Shihela Contextual Site Assistant Settings', 'shihela-contextual-site-assistant' ); ?></h1>
		</div>
		<p class="shihela-contextual-site-assistant-tagline"><?php esc_html_e( 'Configure your floating, context-aware AI assistant widget.', 'shihela-contextual-site-assistant' ); ?></p>
	</header>

	<div class="shihela-contextual-site-assistant-admin-container">
		<div class="shihela-contextual-site-assistant-admin-main">
			<form method="post" action="options.php">
				<?php settings_fields( 'shihela-contextual-site-assistant_group' ); ?>



				<!-- Card: Bot Configuration -->
				<div class="shihela-contextual-site-assistant-card">
					<h2 class="shihela-contextual-site-assistant-card-title">
						<span class="dashicons dashicons-admin-users"></span>
						<?php esc_html_e( 'Assistant Configuration & Customization', 'shihela-contextual-site-assistant' ); ?>
					</h2>
					<div class="shihela-contextual-site-assistant-card-body">
						<div class="shihela-contextual-site-assistant-form-group">
							<label for="shihela_contextual_site_assistant_bot_name"><?php esc_html_e( 'Assistant Name', 'shihela-contextual-site-assistant' ); ?></label>
							<input type="text" name="shihela_contextual_site_assistant_bot_name" id="shihela_contextual_site_assistant_bot_name" value="<?php echo esc_attr( $shihela_contextual_site_assistant_bot_name ); ?>" class="regular-text shihela-contextual-site-assistant-input" required>
							<p class="description"><?php esc_html_e( 'This name will display in the header of the chat widget.', 'shihela-contextual-site-assistant' ); ?></p>
						</div>

						<div class="shihela-contextual-site-assistant-form-group">
							<label for="shihela_contextual_site_assistant_welcome_message"><?php esc_html_e( 'Welcome Message', 'shihela-contextual-site-assistant' ); ?></label>
							<textarea name="shihela_contextual_site_assistant_welcome_message" id="shihela_contextual_site_assistant_welcome_message" rows="3" class="large-text shihela-contextual-site-assistant-textarea" required><?php echo esc_textarea( $shihela_contextual_site_assistant_welcome_message ); ?></textarea>
							<p class="description"><?php esc_html_e( 'First message sent by the AI when the chat widget is opened.', 'shihela-contextual-site-assistant' ); ?></p>
						</div>

						<div class="shihela-contextual-site-assistant-form-group">
							<label for="shihela_contextual_site_assistant_system_instructions"><?php esc_html_e( 'Global Context & Instructions', 'shihela-contextual-site-assistant' ); ?></label>
							<textarea name="shihela_contextual_site_assistant_system_instructions" id="shihela_contextual_site_assistant_system_instructions" rows="6" class="large-text shihela-contextual-site-assistant-textarea" placeholder="<?php esc_attr_e( 'Write custom details about your website, business hours, services, or support protocols here. The AI will refer to this context when answering inquiries.', 'shihela-contextual-site-assistant' ); ?>"><?php echo esc_textarea( $shihela_contextual_site_assistant_system_instructions ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Give the AI specific guidelines on what it should represent (e.g. "You are an e-commerce assistant. Do not talk about coding. Suggest customers email sales@example.com for refunds.").', 'shihela-contextual-site-assistant' ); ?></p>
						</div>

						<div class="shihela-contextual-site-assistant-form-group">
							<label for="shihela_contextual_site_assistant_theme_color"><?php esc_html_e( 'Theme Accent Color', 'shihela-contextual-site-assistant' ); ?></label>
							<input type="color" name="shihela_contextual_site_assistant_theme_color" id="shihela_contextual_site_assistant_theme_color" value="<?php echo esc_attr( $shihela_contextual_site_assistant_theme_color ); ?>" class="shihela-contextual-site-assistant-color-picker">
							<p class="description"><?php esc_html_e( 'Customize the look of the floating launcher bubble and chat header.', 'shihela-contextual-site-assistant' ); ?></p>
						</div>

						<div class="shihela-contextual-site-assistant-form-group">
							<label for="shihela_contextual_site_assistant_widget_position"><?php esc_html_e( 'Widget Position', 'shihela-contextual-site-assistant' ); ?></label>
							<select name="shihela_contextual_site_assistant_widget_position" id="shihela_contextual_site_assistant_widget_position" class="shihela-contextual-site-assistant-select">
								<option value="bottom-right" <?php selected( $shihela_contextual_site_assistant_widget_position, 'bottom-right' ); ?>><?php esc_html_e( 'Bottom Right', 'shihela-contextual-site-assistant' ); ?></option>
								<option value="bottom-left" <?php selected( $shihela_contextual_site_assistant_widget_position, 'bottom-left' ); ?>><?php esc_html_e( 'Bottom Left', 'shihela-contextual-site-assistant' ); ?></option>
								<option value="top-right" <?php selected( $shihela_contextual_site_assistant_widget_position, 'top-right' ); ?>><?php esc_html_e( 'Top Right', 'shihela-contextual-site-assistant' ); ?></option>
								<option value="top-left" <?php selected( $shihela_contextual_site_assistant_widget_position, 'top-left' ); ?>><?php esc_html_e( 'Top Left', 'shihela-contextual-site-assistant' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Choose where the chat launcher bubble is positioned on the visitor\'s screen.', 'shihela-contextual-site-assistant' ); ?></p>
						</div>
					</div>
				</div>
				<!-- Card: Webhooks & Integrations -->
				<div class="shihela-contextual-site-assistant-card">
					<h2 class="shihela-contextual-site-assistant-card-title">
						<span class="dashicons dashicons-admin-settings"></span>
						<?php esc_html_e( 'Webhooks & External Integrations', 'shihela-contextual-site-assistant' ); ?>
					</h2>
					<div class="shihela-contextual-site-assistant-card-body">
						<div class="shihela-contextual-site-assistant-form-group">
							<label for="shihela_contextual_site_assistant_webhook_url"><?php esc_html_e( 'Webhook Endpoint URL', 'shihela-contextual-site-assistant' ); ?></label>
							<input type="url" name="shihela_contextual_site_assistant_webhook_url" id="shihela_contextual_site_assistant_webhook_url" value="<?php echo esc_url( $shihela_contextual_site_assistant_webhook_url ); ?>" class="large-text shihela-contextual-site-assistant-input" placeholder="https://hooks.zapier.com/... or https://hook.make.com/...">
							<p class="description"><?php esc_html_e( 'Whenever a new lead is captured by the chatbot, a non-blocking POST request with lead details (JSON) is sent to this URL to trigger automated workflows on Zapier, Make, etc.', 'shihela-contextual-site-assistant' ); ?></p>
						</div>
					</div>
				</div>

				<?php submit_button( __( 'Save All Changes', 'shihela-contextual-site-assistant' ), 'primary', 'submit', true, array( 'class' => 'shihela-contextual-site-assistant-save-btn' ) ); ?>
			</form>
		</div>

		<!-- Sidebar info -->
		<div class="shihela-contextual-site-assistant-admin-sidebar">
			<div class="shihela-contextual-site-assistant-sidebar-card">
				<h3><span class="dashicons dashicons-info"></span> <?php esc_html_e( 'How it works', 'shihela-contextual-site-assistant' ); ?></h3>
				<ol>
					<li><?php esc_html_e( 'Configure your branding and system instructions below.', 'shihela-contextual-site-assistant' ); ?></li>
					<li><?php esc_html_e( 'The assistant is automatically injected in the bottom right corner of your public site pages.', 'shihela-contextual-site-assistant' ); ?></li>
					<li><?php esc_html_e( 'When a visitor asks a question, the plugin automatically retrieves the current page title and up to 500 words of content, feeding it as page context directly into the AI request.', 'shihela-contextual-site-assistant' ); ?></li>
					<li><?php esc_html_e( 'This allows the AI to answer contextually about the exact page the user is currently viewing!', 'shihela-contextual-site-assistant' ); ?></li>
				</ol>
			</div>
		</div>
	</div>
</div>
