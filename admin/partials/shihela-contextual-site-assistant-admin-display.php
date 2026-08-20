<?php
/**
 * Provide a modern SaaS-style vertical tabbed admin area view for the plugin.
 *
 * @link       https://shihela.com/contextual-site-assistant
 * @since      1.2.0
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
$shihela_contextual_site_assistant_temperature         = get_option( 'shihela_contextual_site_assistant_temperature', 0.3 );
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

// WhatsApp Options (v1.2.0)
$shihela_contextual_site_assistant_whatsapp_number     = get_option( 'shihela_contextual_site_assistant_whatsapp_number', '' );
$shihela_contextual_site_assistant_whatsapp_message    = get_option( 'shihela_contextual_site_assistant_whatsapp_message', 'Halo CS/Admin, saya sedang melihat halaman {page_title} ({page_url}) dan membutuhkan bantuan.' );
$shihela_contextual_site_assistant_whatsapp_header_btn = get_option( 'shihela_contextual_site_assistant_whatsapp_header_btn', '1' );
?>

<div class="wrap shihela-contextual-site-assistant-admin-wrap">
	<!-- Modern Header Banner -->
	<header class="shihela-contextual-site-assistant-admin-header">
		<div class="shihela-header-left">
			<div class="shihela-brand-badge">
				<span class="dashicons dashicons-admin-comments"></span>
			</div>
			<div>
				<h1><?php esc_html_e( 'Shihela Site Assistant', 'shihela-contextual-site-assistant' ); ?> <span class="shihela-version-pill">v1.3.0</span></h1>
				<p class="shihela-contextual-site-assistant-tagline"><?php esc_html_e( 'Configure floating context-aware AI chat, WhatsApp CS routing, and security quotas.', 'shihela-contextual-site-assistant' ); ?></p>
			</div>
		</div>
		<div class="shihela-header-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=shihela-contextual-site-assistant-leads' ) ); ?>" class="button button-secondary shihela-leads-btn">
				<span class="dashicons dashicons-groups"></span> <?php esc_html_e( 'View Captured Leads', 'shihela-contextual-site-assistant' ); ?>
			</a>
		</div>
	</header>

	<div class="shihela-contextual-site-assistant-admin-container vertical-layout">
		<!-- LEFT SIDEBAR: Vertical Tab Navigation & Status Cards -->
		<aside class="shihela-admin-sidebar-nav">
			<nav class="shihela-admin-tabs-nav vertical" role="tablist">
				<button type="button" class="shihela-admin-tab-btn active" data-tab="tab-general" role="tab" aria-selected="true">
					<span class="dashicons dashicons-admin-appearance"></span>
					<span class="tab-label"><?php esc_html_e( 'General & Branding', 'shihela-contextual-site-assistant' ); ?></span>
				</button>
				<button type="button" class="shihela-admin-tab-btn" data-tab="tab-whatsapp" role="tab" aria-selected="false">
					<span class="dashicons dashicons-whatsapp"></span>
					<span class="tab-label"><?php esc_html_e( 'WhatsApp & CS', 'shihela-contextual-site-assistant' ); ?></span>
				</button>
				<button type="button" class="shihela-admin-tab-btn" data-tab="tab-ai" role="tab" aria-selected="false">
					<span class="dashicons dashicons-lightbulb"></span>
					<span class="tab-label"><?php esc_html_e( 'AI Instructions', 'shihela-contextual-site-assistant' ); ?></span>
				</button>
				<button type="button" class="shihela-admin-tab-btn" data-tab="tab-integrations" role="tab" aria-selected="false">
					<span class="dashicons dashicons-rest-api"></span>
					<span class="tab-label"><?php esc_html_e( 'Webhooks & Leads', 'shihela-contextual-site-assistant' ); ?></span>
				</button>
				<button type="button" class="shihela-admin-tab-btn" data-tab="tab-security" role="tab" aria-selected="false">
					<span class="dashicons dashicons-shield"></span>
					<span class="tab-label"><?php esc_html_e( 'Security & Quotas', 'shihela-contextual-site-assistant' ); ?></span>
				</button>
			</nav>

			<div class="shihela-sidebar-cards">
				<div class="shihela-contextual-site-assistant-sidebar-card">
					<h3><span class="dashicons dashicons-info"></span> <?php esc_html_e( 'Quick Status', 'shihela-contextual-site-assistant' ); ?></h3>
					<ul class="shihela-contextual-site-assistant-status-list">
						<li><strong><?php esc_html_e( 'WP AI Client:', 'shihela-contextual-site-assistant' ); ?></strong> <span class="shihela-badge green"><?php echo function_exists( 'wp_ai_client_prompt' ) ? esc_html__( 'Active', 'shihela-contextual-site-assistant' ) : esc_html__( 'Inactive', 'shihela-contextual-site-assistant' ); ?></span></li>
						<li><strong><?php esc_html_e( 'WhatsApp CS:', 'shihela-contextual-site-assistant' ); ?></strong> <span class="shihela-badge <?php echo ! empty( $shihela_contextual_site_assistant_whatsapp_number ) ? 'green' : 'gray'; ?>"><?php echo ! empty( $shihela_contextual_site_assistant_whatsapp_number ) ? esc_html__( 'Enabled', 'shihela-contextual-site-assistant' ) : esc_html__( 'Not Set', 'shihela-contextual-site-assistant' ); ?></span></li>
						<li><strong><?php esc_html_e( 'Visibility:', 'shihela-contextual-site-assistant' ); ?></strong> <span><?php echo esc_html( ucfirst( $shihela_contextual_site_assistant_access_control ) ); ?></span></li>
					</ul>
				</div>

				<div class="shihela-contextual-site-assistant-sidebar-card">
					<h3><span class="dashicons dashicons-lightbulb"></span> <?php esc_html_e( 'Pro Tip', 'shihela-contextual-site-assistant' ); ?></h3>
					<p><?php esc_html_e( 'Configure your LLM model provider under Settings > Connectors in WordPress dashboard.', 'shihela-contextual-site-assistant' ); ?></p>
				</div>
			</div>
		</aside>

		<!-- RIGHT MAIN CONTENT AREA -->
		<main class="shihela-admin-content-area">
			<form method="post" action="options.php" id="shihela-admin-settings-form">
				<?php settings_fields( 'shihela-contextual-site-assistant_group' ); ?>

				<!-- Tab 1: General & Branding -->
				<div class="shihela-admin-tab-panel active" id="tab-general" role="tabpanel">
					<div class="shihela-contextual-site-assistant-card">
						<div class="shihela-card-header">
							<h2><span class="dashicons dashicons-format-image"></span> <?php esc_html_e( 'Widget Branding & Appearance', 'shihela-contextual-site-assistant' ); ?></h2>
							<p><?php esc_html_e( 'Customize how your assistant looks and greets visitors on your website.', 'shihela-contextual-site-assistant' ); ?></p>
						</div>
						<div class="shihela-contextual-site-assistant-card-body">
							<div class="shihela-form-row">
								<div class="shihela-contextual-site-assistant-form-group col-half">
									<label for="shihela_contextual_site_assistant_bot_name"><?php esc_html_e( 'Assistant Name', 'shihela-contextual-site-assistant' ); ?></label>
									<input type="text" name="shihela_contextual_site_assistant_bot_name" id="shihela_contextual_site_assistant_bot_name" value="<?php echo esc_attr( $shihela_contextual_site_assistant_bot_name ); ?>" class="regular-text shihela-contextual-site-assistant-input" required>
									<p class="description"><?php esc_html_e( 'Displayed in the header of the floating chat window.', 'shihela-contextual-site-assistant' ); ?></p>
								</div>

								<div class="shihela-contextual-site-assistant-form-group col-half">
									<label for="shihela_contextual_site_assistant_theme_color"><?php esc_html_e( 'Theme Accent Color', 'shihela-contextual-site-assistant' ); ?></label>
									<div class="shihela-color-picker-wrapper">
										<input type="color" name="shihela_contextual_site_assistant_theme_color" id="shihela_contextual_site_assistant_theme_color" value="<?php echo esc_attr( $shihela_contextual_site_assistant_theme_color ); ?>" class="shihela-contextual-site-assistant-color-picker">
										<span class="shihela-color-code"><?php echo esc_html( $shihela_contextual_site_assistant_theme_color ); ?></span>
									</div>
									<p class="description"><?php esc_html_e( 'Primary accent color for launcher bubble and chat header.', 'shihela-contextual-site-assistant' ); ?></p>
								</div>
							</div>

							<div class="shihela-contextual-site-assistant-form-group">
								<label for="shihela_contextual_site_assistant_welcome_message"><?php esc_html_e( 'Welcome Greeting Message', 'shihela-contextual-site-assistant' ); ?></label>
								<textarea name="shihela_contextual_site_assistant_welcome_message" id="shihela_contextual_site_assistant_welcome_message" rows="3" class="large-text shihela-contextual-site-assistant-textarea" required><?php echo esc_textarea( $shihela_contextual_site_assistant_welcome_message ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Initial greeting sent by the AI when a visitor opens the chat widget.', 'shihela-contextual-site-assistant' ); ?></p>
							</div>

							<div class="shihela-contextual-site-assistant-form-group">
								<label for="shihela_contextual_site_assistant_suggestion_chips"><?php esc_html_e( 'Quick Suggestion Chips (Preset Prompts)', 'shihela-contextual-site-assistant' ); ?></label>
								<textarea name="shihela_contextual_site_assistant_suggestion_chips" id="shihela_contextual_site_assistant_suggestion_chips" rows="3" class="large-text shihela-contextual-site-assistant-textarea" placeholder="<?php esc_attr_e( 'Enter preset questions (one per line)...', 'shihela-contextual-site-assistant' ); ?>"><?php echo esc_textarea( $shihela_contextual_site_assistant_suggestion_chips ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Clickable suggestion pills shown above the input box (one question per line).', 'shihela-contextual-site-assistant' ); ?></p>
							</div>

							<div class="shihela-contextual-site-assistant-form-group">
								<label for="shihela_contextual_site_assistant_widget_position"><?php esc_html_e( 'Screen Widget Position', 'shihela-contextual-site-assistant' ); ?></label>
								<select name="shihela_contextual_site_assistant_widget_position" id="shihela_contextual_site_assistant_widget_position" class="shihela-contextual-site-assistant-select">
									<option value="bottom-right" <?php selected( $shihela_contextual_site_assistant_widget_position, 'bottom-right' ); ?>><?php esc_html_e( 'Bottom Right (Recommended)', 'shihela-contextual-site-assistant' ); ?></option>
									<option value="bottom-left" <?php selected( $shihela_contextual_site_assistant_widget_position, 'bottom-left' ); ?>><?php esc_html_e( 'Bottom Left', 'shihela-contextual-site-assistant' ); ?></option>
									<option value="top-right" <?php selected( $shihela_contextual_site_assistant_widget_position, 'top-right' ); ?>><?php esc_html_e( 'Top Right', 'shihela-contextual-site-assistant' ); ?></option>
									<option value="top-left" <?php selected( $shihela_contextual_site_assistant_widget_position, 'top-left' ); ?>><?php esc_html_e( 'Top Left', 'shihela-contextual-site-assistant' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'Position of the launcher bubble on visitors\' screens.', 'shihela-contextual-site-assistant' ); ?></p>
							</div>
						</div>
					</div>
				</div>

				<!-- Tab 2: WhatsApp & CS Integration (v1.2.0) -->
				<div class="shihela-admin-tab-panel" id="tab-whatsapp" role="tabpanel" style="display: none;">
					<div class="shihela-contextual-site-assistant-card">
						<div class="shihela-card-header green-header">
							<h2><span class="dashicons dashicons-whatsapp"></span> <?php esc_html_e( 'WhatsApp Direct CS Routing (v1.2.0)', 'shihela-contextual-site-assistant' ); ?></h2>
							<p><?php esc_html_e( 'Enable seamless human handoff. When visitors ask for CS or human support, Shihela provides an interactive Click-to-WhatsApp button.', 'shihela-contextual-site-assistant' ); ?></p>
						</div>
						<div class="shihela-contextual-site-assistant-card-body">
							<div class="shihela-contextual-site-assistant-form-group">
								<label for="shihela_contextual_site_assistant_whatsapp_number"><?php esc_html_e( 'Admin / CS WhatsApp Phone Number', 'shihela-contextual-site-assistant' ); ?></label>
								<input type="text" name="shihela_contextual_site_assistant_whatsapp_number" id="shihela_contextual_site_assistant_whatsapp_number" value="<?php echo esc_attr( $shihela_contextual_site_assistant_whatsapp_number ); ?>" placeholder="6281234567890" class="regular-text shihela-contextual-site-assistant-input">
								<p class="description"><?php esc_html_e( 'Enter number in international format without plus (+) sign or dashes, e.g. 628123456789 for Indonesia (+62). Leave empty to disable WhatsApp CTA feature.', 'shihela-contextual-site-assistant' ); ?></p>
							</div>

							<div class="shihela-contextual-site-assistant-form-group">
								<label for="shihela_contextual_site_assistant_whatsapp_message"><?php esc_html_e( 'Pre-filled WhatsApp Message Template', 'shihela-contextual-site-assistant' ); ?></label>
								<textarea name="shihela_contextual_site_assistant_whatsapp_message" id="shihela_contextual_site_assistant_whatsapp_message" rows="3" class="large-text shihela-contextual-site-assistant-textarea"><?php echo esc_textarea( $shihela_contextual_site_assistant_whatsapp_message ); ?></textarea>
								<p class="description">
									<?php esc_html_e( 'Pre-filled text when visitor opens WhatsApp chat. Available tags: ', 'shihela-contextual-site-assistant' ); ?>
									<code>{page_title}</code>, <code>{page_url}</code>.
								</p>
							</div>

							<div class="shihela-toggle-card">
								<div class="shihela-toggle-info">
									<strong><?php esc_html_e( 'Show Quick WhatsApp Button in Chat Widget Header', 'shihela-contextual-site-assistant' ); ?></strong>
									<p><?php esc_html_e( 'Display a WhatsApp icon button directly in the chat panel header bar so visitors can click to WA anytime.', 'shihela-contextual-site-assistant' ); ?></p>
								</div>
								<label class="shihela-switch">
									<input type="checkbox" name="shihela_contextual_site_assistant_whatsapp_header_btn" value="1" <?php checked( $shihela_contextual_site_assistant_whatsapp_header_btn, '1' ); ?>>
									<span class="shihela-slider"></span>
								</label>
							</div>

							<div class="shihela-info-banner">
								<span class="dashicons dashicons-info"></span>
								<div>
									<strong><?php esc_html_e( 'How AI Smart WhatsApp Trigger Works:', 'shihela-contextual-site-assistant' ); ?></strong>
									<p><?php esc_html_e( 'When a WhatsApp number is provided, Shihela AI automatically instructs the model to detect inquiries asking for CS, human contact, or phone numbers. When detected, the chatbot renders an attractive green WhatsApp CTA button directly inside the chat window.', 'shihela-contextual-site-assistant' ); ?></p>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Tab 3: AI Instructions & Context -->
				<div class="shihela-admin-tab-panel" id="tab-ai" role="tabpanel" style="display: none;">
					<div class="shihela-contextual-site-assistant-card">
						<div class="shihela-card-header">
							<h2><span class="dashicons dashicons-lightbulb"></span> <?php esc_html_e( 'AI Behavior & Knowledge Context', 'shihela-contextual-site-assistant' ); ?></h2>
							<p><?php esc_html_e( 'Guide how the AI responds, what business rules it obeys, and how page content is processed.', 'shihela-contextual-site-assistant' ); ?></p>
						</div>
						<div class="shihela-contextual-site-assistant-card-body">
							<div class="shihela-contextual-site-assistant-form-group">
								<label for="shihela_contextual_site_assistant_system_instructions"><?php esc_html_e( 'Global Business Instructions & Prompt Rules', 'shihela-contextual-site-assistant' ); ?></label>
								<textarea name="shihela_contextual_site_assistant_system_instructions" id="shihela_contextual_site_assistant_system_instructions" rows="7" class="large-text shihela-contextual-site-assistant-textarea" placeholder="<?php esc_attr_e( 'e.g. You are an assistant for an online clothing store. Business hours are 09:00 - 18:00. Always recommend products politely...', 'shihela-contextual-site-assistant' ); ?>"><?php echo esc_textarea( $shihela_contextual_site_assistant_system_instructions ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Specific guidelines and domain knowledge the AI must adhere to for all visitor questions.', 'shihela-contextual-site-assistant' ); ?></p>
							</div>

							<div class="shihela-contextual-site-assistant-form-group">
								<label for="shihela_contextual_site_assistant_temperature"><?php esc_html_e( 'AI Creativity Temperature (0.0 - 1.0)', 'shihela-contextual-site-assistant' ); ?></label>
								<input type="number" name="shihela_contextual_site_assistant_temperature" id="shihela_contextual_site_assistant_temperature" value="<?php echo esc_attr( $shihela_contextual_site_assistant_temperature ); ?>" step="0.05" min="0.0" max="1.0" class="small-text shihela-contextual-site-assistant-input">
								<p class="description">
									<?php esc_html_e( 'Recommended: 0.3 for strict contextual grounding & anti-hallucination (0.0 = completely deterministic/strict, 0.7 = creative/loose).', 'shihela-contextual-site-assistant' ); ?>
								</p>
							</div>

							<div class="shihela-feature-highlight">
								<h4><span class="dashicons dashicons-admin-links"></span> <?php esc_html_e( 'Context Reading Protocol (Automatic)', 'shihela-contextual-site-assistant' ); ?></h4>
								<ul>
									<li><?php esc_html_e( 'Reads current Page Title & URL dynamically.', 'shihela-contextual-site-assistant' ); ?></li>
									<li><?php esc_html_e( 'Extracts Categories, Tags, and Page Excerpt.', 'shihela-contextual-site-assistant' ); ?></li>
									<li><?php esc_html_e( 'Sanitizes HTML tags, scripts, shortcodes, and feeds up to 1,000 pure text words as page context.', 'shihela-contextual-site-assistant' ); ?></li>
								</ul>
							</div>
						</div>
					</div>
				</div>

				<!-- Tab 4: Integrations & Webhooks -->
				<div class="shihela-admin-tab-panel" id="tab-integrations" role="tabpanel" style="display: none;">
					<div class="shihela-contextual-site-assistant-card">
						<div class="shihela-card-header">
							<h2><span class="dashicons dashicons-rest-api"></span> <?php esc_html_e( 'Lead Webhooks & Integrations', 'shihela-contextual-site-assistant' ); ?></h2>
							<p><?php esc_html_e( 'Connect Shihela chatbot with external automation workflows such as Zapier, Make, or N8N.', 'shihela-contextual-site-assistant' ); ?></p>
						</div>
						<div class="shihela-contextual-site-assistant-card-body">
							<div class="shihela-contextual-site-assistant-form-group">
								<label for="shihela_contextual_site_assistant_webhook_url"><?php esc_html_e( 'Webhook Endpoint URL', 'shihela-contextual-site-assistant' ); ?></label>
								<input type="url" name="shihela_contextual_site_assistant_webhook_url" id="shihela_contextual_site_assistant_webhook_url" value="<?php echo esc_url( $shihela_contextual_site_assistant_webhook_url ); ?>" class="large-text shihela-contextual-site-assistant-input" placeholder="https://hooks.zapier.com/... or https://hook.make.com/...">
								<p class="description"><?php esc_html_e( 'Whenever a new lead is captured by the chatbot, a non-blocking POST request containing JSON data is sent to this URL.', 'shihela-contextual-site-assistant' ); ?></p>
							</div>

							<div class="shihela-webhook-box">
								<h4><?php esc_html_e( 'Lead Data Export', 'shihela-contextual-site-assistant' ); ?></h4>
								<p><?php esc_html_e( 'All captured customer leads are stored safely in your WordPress database.', 'shihela-contextual-site-assistant' ); ?></p>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=shihela-contextual-site-assistant-leads' ) ); ?>" class="button button-primary">
									<span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Manage & Export Leads (CSV)', 'shihela-contextual-site-assistant' ); ?>
								</a>
							</div>
						</div>
					</div>
				</div>

				<!-- Tab 5: Security & API Quotas -->
				<div class="shihela-admin-tab-panel" id="tab-security" role="tabpanel" style="display: none;">
					<div class="shihela-contextual-site-assistant-card">
						<div class="shihela-card-header">
							<h2><span class="dashicons dashicons-shield"></span> <?php esc_html_e( 'Access Control & API Spam Prevention', 'shihela-contextual-site-assistant' ); ?></h2>
							<p><?php esc_html_e( 'Protect your server resources and AI API usage limits against spam and excessive queries.', 'shihela-contextual-site-assistant' ); ?></p>
						</div>
						<div class="shihela-contextual-site-assistant-card-body">
							<div class="shihela-contextual-site-assistant-form-group">
								<label for="shihela_contextual_site_assistant_access_control"><?php esc_html_e( 'Widget Visibility Control', 'shihela-contextual-site-assistant' ); ?></label>
								<select name="shihela_contextual_site_assistant_access_control" id="shihela_contextual_site_assistant_access_control" class="shihela-contextual-site-assistant-select">
									<option value="public" <?php selected( $shihela_contextual_site_assistant_access_control, 'public' ); ?>><?php esc_html_e( 'Public (Everyone)', 'shihela-contextual-site-assistant' ); ?></option>
									<option value="logged_in" <?php selected( $shihela_contextual_site_assistant_access_control, 'logged_in' ); ?>><?php esc_html_e( 'Registered Users Only (Logged-in)', 'shihela-contextual-site-assistant' ); ?></option>
									<option value="admin" <?php selected( $shihela_contextual_site_assistant_access_control, 'admin' ); ?>><?php esc_html_e( 'Administrators Only', 'shihela-contextual-site-assistant' ); ?></option>
								</select>
								<p class="description"><?php esc_html_e( 'Define who can view and interact with the chatbot widget.', 'shihela-contextual-site-assistant' ); ?></p>
							</div>

							<div class="shihela-form-row">
								<div class="shihela-contextual-site-assistant-form-group col-half">
									<label for="shihela_contextual_site_assistant_daily_limit"><?php esc_html_e( 'Global Site Daily Request Limit', 'shihela-contextual-site-assistant' ); ?></label>
									<input type="number" name="shihela_contextual_site_assistant_daily_limit" id="shihela_contextual_site_assistant_daily_limit" value="<?php echo esc_attr( $shihela_contextual_site_assistant_daily_limit ); ?>" min="0" class="regular-text shihela-contextual-site-assistant-input">
									<p class="description"><?php esc_html_e( 'Cumulative queries allowed per day for the entire site (0 = unlimited).', 'shihela-contextual-site-assistant' ); ?></p>
								</div>

								<div class="shihela-contextual-site-assistant-form-group col-half">
									<label for="shihela_contextual_site_assistant_ip_daily_limit"><?php esc_html_e( 'Visitor Daily Limit (Per IP)', 'shihela-contextual-site-assistant' ); ?></label>
									<input type="number" name="shihela_contextual_site_assistant_ip_daily_limit" id="shihela_contextual_site_assistant_ip_daily_limit" value="<?php echo esc_attr( $shihela_contextual_site_assistant_ip_daily_limit ); ?>" min="0" class="regular-text shihela-contextual-site-assistant-input">
									<p class="description"><?php esc_html_e( 'Maximum queries per single IP address per day (0 = unlimited).', 'shihela-contextual-site-assistant' ); ?></p>
								</div>
							</div>

							<div class="shihela-form-row">
								<div class="shihela-contextual-site-assistant-form-group col-half">
									<label for="shihela_contextual_site_assistant_rate_limit"><?php esc_html_e( 'Rate Limit (Requests / Min / IP)', 'shihela-contextual-site-assistant' ); ?></label>
									<input type="number" name="shihela_contextual_site_assistant_rate_limit" id="shihela_contextual_site_assistant_rate_limit" value="<?php echo esc_attr( $shihela_contextual_site_assistant_rate_limit ); ?>" min="1" class="regular-text shihela-contextual-site-assistant-input">
									<p class="description"><?php esc_html_e( 'Max messages per minute per IP (Admins exempted).', 'shihela-contextual-site-assistant' ); ?></p>
								</div>

								<div class="shihela-contextual-site-assistant-form-group col-half">
									<label for="shihela_contextual_site_assistant_max_length"><?php esc_html_e( 'Max Message Length (Chars)', 'shihela-contextual-site-assistant' ); ?></label>
									<input type="number" name="shihela_contextual_site_assistant_max_length" id="shihela_contextual_site_assistant_max_length" value="<?php echo esc_attr( $shihela_contextual_site_assistant_max_length ); ?>" min="10" class="regular-text shihela-contextual-site-assistant-input">
									<p class="description"><?php esc_html_e( 'Restricts character length of visitor messages.', 'shihela-contextual-site-assistant' ); ?></p>
								</div>
							</div>

							<div class="shihela-contextual-site-assistant-form-group">
								<label for="shihela_contextual_site_assistant_max_history"><?php esc_html_e( 'Max Conversation History Depth', 'shihela-contextual-site-assistant' ); ?></label>
								<input type="number" name="shihela_contextual_site_assistant_max_history" id="shihela_contextual_site_assistant_max_history" value="<?php echo esc_attr( $shihela_contextual_site_assistant_max_history ); ?>" min="1" class="regular-text shihela-contextual-site-assistant-input">
								<p class="description"><?php esc_html_e( 'Number of previous chat turns sent as history context (saving tokens).', 'shihela-contextual-site-assistant' ); ?></p>
							</div>
						</div>
					</div>
				</div>

				<!-- Save Bar -->
				<div class="shihela-admin-save-bar">
					<?php submit_button( __( 'Save All Settings', 'shihela-contextual-site-assistant' ), 'primary', 'submit', false, array( 'class' => 'shihela-contextual-site-assistant-save-btn' ) ); ?>
				</div>
			</form>
		</main>
	</div>
</div>
