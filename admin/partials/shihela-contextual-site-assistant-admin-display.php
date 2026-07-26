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
$shihela_contextual_site_assistant_suggestion_chips    = get_option( 'shihela_contextual_site_assistant_suggestion_chips', "How can you help me?\nWhat services are offered?\nHow to contact human support?" );
$shihela_contextual_site_assistant_theme_color         = get_option( 'shihela_contextual_site_assistant_theme_color', '#4f46e5' );
$shihela_contextual_site_assistant_widget_position     = get_option( 'shihela_contextual_site_assistant_widget_position', 'bottom-right' );
$shihela_contextual_site_assistant_webhook_url         = get_option( 'shihela_contextual_site_assistant_webhook_url', '' );

$shihela_contextual_site_assistant_access_control      = get_option( 'shihela_contextual_site_assistant_access_control', 'public' );
$shihela_contextual_site_assistant_daily_limit          = get_option( 'shihela_contextual_site_assistant_daily_limit', 100 );
$shihela_contextual_site_assistant_rate_limit           = get_option( 'shihela_contextual_site_assistant_rate_limit', 5 );
$shihela_contextual_site_assistant_max_length           = get_option( 'shihela_contextual_site_assistant_max_length', 300 );
$shihela_contextual_site_assistant_max_history          = get_option( 'shihela_contextual_site_assistant_max_history', 10 );
$shihela_contextual_site_assistant_ip_daily_limit      = get_option( 'shihela_contextual_site_assistant_ip_daily_limit', 20 );
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
							<label for="shihela_contextual_site_assistant_suggestion_chips"><?php esc_html_e( 'Quick Suggestion Chips (Preset Prompts)', 'shihela-contextual-site-assistant' ); ?></label>
							<textarea name="shihela_contextual_site_assistant_suggestion_chips" id="shihela_contextual_site_assistant_suggestion_chips" rows="3" class="large-text shihela-contextual-site-assistant-textarea" placeholder="<?php esc_attr_e( 'Enter preset questions (one per line)...', 'shihela-contextual-site-assistant' ); ?>"><?php echo esc_textarea( $shihela_contextual_site_assistant_suggestion_chips ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Enter predefined prompt questions for visitors to click (one prompt question per line).', 'shihela-contextual-site-assistant' ); ?></p>
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

				<!-- Card: Security & API Budget -->
				<div class="shihela-contextual-site-assistant-card">
					<h2 class="shihela-contextual-site-assistant-card-title">
						<span class="dashicons dashicons-shield"></span>
						<?php esc_html_e( 'Security & API Budget (Token & Spam Prevention)', 'shihela-contextual-site-assistant' ); ?>
					</h2>
					<div class="shihela-contextual-site-assistant-card-body">
						<div class="shihela-contextual-site-assistant-form-group">
							<label for="shihela_contextual_site_assistant_access_control"><?php esc_html_e( 'Access Control', 'shihela-contextual-site-assistant' ); ?></label>
							<select name="shihela_contextual_site_assistant_access_control" id="shihela_contextual_site_assistant_access_control" class="shihela-contextual-site-assistant-select">
								<option value="public" <?php selected( $shihela_contextual_site_assistant_access_control, 'public' ); ?>><?php esc_html_e( 'Public (Everyone)', 'shihela-contextual-site-assistant' ); ?></option>
								<option value="logged_in" <?php selected( $shihela_contextual_site_assistant_access_control, 'logged_in' ); ?>><?php esc_html_e( 'Registered Users Only (Logged-in)', 'shihela-contextual-site-assistant' ); ?></option>
								<option value="admin" <?php selected( $shihela_contextual_site_assistant_access_control, 'admin' ); ?>><?php esc_html_e( 'Administrators Only', 'shihela-contextual-site-assistant' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Define who can view and interact with the chatbot widget on your website. If restricted, assets and markup are not loaded for unauthorized visitors.', 'shihela-contextual-site-assistant' ); ?></p>
						</div>

						<div class="shihela-contextual-site-assistant-form-group">
							<label for="shihela_contextual_site_assistant_daily_limit"><?php esc_html_e( 'Global Daily API Request Limit', 'shihela-contextual-site-assistant' ); ?></label>
							<input type="number" name="shihela_contextual_site_assistant_daily_limit" id="shihela_contextual_site_assistant_daily_limit" value="<?php echo esc_attr( $shihela_contextual_site_assistant_daily_limit ); ?>" min="0" class="regular-text shihela-contextual-site-assistant-input">
							<p class="description"><?php esc_html_e( 'Maximum cumulative requests allowed per day across the entire site. Use 0 for unlimited. Protects against massive API token drainage/bills.', 'shihela-contextual-site-assistant' ); ?></p>
						</div>

						<div class="shihela-contextual-site-assistant-form-group">
							<label for="shihela_contextual_site_assistant_ip_daily_limit"><?php esc_html_e( 'Visitor Daily Limit (Per IP)', 'shihela-contextual-site-assistant' ); ?></label>
							<input type="number" name="shihela_contextual_site_assistant_ip_daily_limit" id="shihela_contextual_site_assistant_ip_daily_limit" value="<?php echo esc_attr( $shihela_contextual_site_assistant_ip_daily_limit ); ?>" min="0" class="regular-text shihela-contextual-site-assistant-input">
							<p class="description"><?php esc_html_e( 'Maximum requests a single IP address can make per day. Use 0 for unlimited. Prevents a single visitor from exhausting the global daily quota.', 'shihela-contextual-site-assistant' ); ?></p>
						</div>

						<div class="shihela-contextual-site-assistant-form-group">
							<label for="shihela_contextual_site_assistant_rate_limit"><?php esc_html_e( 'Rate Limit (Requests per Minute per IP)', 'shihela-contextual-site-assistant' ); ?></label>
							<input type="number" name="shihela_contextual_site_assistant_rate_limit" id="shihela_contextual_site_assistant_rate_limit" value="<?php echo esc_attr( $shihela_contextual_site_assistant_rate_limit ); ?>" min="1" class="regular-text shihela-contextual-site-assistant-input">
							<p class="description"><?php esc_html_e( 'Maximum number of messages a single IP address can send per minute. Administrators are automatically exempted from this limit.', 'shihela-contextual-site-assistant' ); ?></p>
						</div>

						<div class="shihela-contextual-site-assistant-form-group">
							<label for="shihela_contextual_site_assistant_max_length"><?php esc_html_e( 'Max Message Character Length', 'shihela-contextual-site-assistant' ); ?></label>
							<input type="number" name="shihela_contextual_site_assistant_max_length" id="shihela_contextual_site_assistant_max_length" value="<?php echo esc_attr( $shihela_contextual_site_assistant_max_length ); ?>" min="10" class="regular-text shihela-contextual-site-assistant-input">
							<p class="description"><?php esc_html_e( 'Restricts the character length of visitor messages to prevent token wasting on excessively long texts.', 'shihela-contextual-site-assistant' ); ?></p>
						</div>

						<div class="shihela-contextual-site-assistant-form-group">
							<label for="shihela_contextual_site_assistant_max_history"><?php esc_html_e( 'Max Conversation History Depth', 'shihela-contextual-site-assistant' ); ?></label>
							<input type="number" name="shihela_contextual_site_assistant_max_history" id="shihela_contextual_site_assistant_max_history" value="<?php echo esc_attr( $shihela_contextual_site_assistant_max_history ); ?>" min="1" class="regular-text shihela-contextual-site-assistant-input">
							<p class="description"><?php esc_html_e( 'The maximum number of previous messages to retain and send to the AI model as context. Lower numbers save significant tokens per query.', 'shihela-contextual-site-assistant' ); ?></p>
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
