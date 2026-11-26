<?php
/**
 * The public-facing functionality of the plugin.
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

/**
 * The public-facing functionality of the plugin.
 *
 * Handles enqueuing styles and scripts, injecting the floating widget markup,
 * and registering REST API endpoints.
 *
 * @package    Shihela_Contextual_Site_Assistant
 * @subpackage Shihela_Contextual_Site_Assistant/includes
 * @author     shihela
 */
class Shihela_Contextual_Site_Assistant_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name       The name of this plugin.
	 * @param    string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * Styles are loaded directly inside the widget's Shadow DOM via localized `css_url`
	 * to prevent duplicate network requests and style leakage.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		// Intentionally no-op: widget stylesheet is injected inside Shadow DOM with ?ver= cache busting.
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		if ( ! $this->should_display_widget() ) {
			return;
		}

		$ip                  = $this->get_visitor_ip();
		$daily_limit_reached = $this->is_daily_limit_reached() || $this->is_ip_daily_limit_reached( $ip );

		$raw_chips   = get_option( 'shihela_contextual_site_assistant_suggestion_chips', "How can you help me?\nWhat services are offered?\nHow to contact human support?" );
		$chips_array = array_filter( array_map( 'trim', explode( "\n", $raw_chips ) ) );
		/**
		 * Filter suggestion chips array localized to front-end widget.
		 *
		 * @since 1.1.0
		 * @param array $chips_array Array of chip strings.
		 */
		$chips_array = apply_filters( 'shihela_assistant_suggestion_chips', array_values( $chips_array ) );

		$post_id    = get_the_ID() ? get_the_ID() : 0;
		$page_title = $post_id ? get_the_title( $post_id ) : get_bloginfo( 'name' );
		$page_url   = $post_id ? get_permalink( $post_id ) : get_home_url();

		wp_enqueue_script(
			$this->plugin_name,
			SHIHELA_CONTEXTUAL_SITE_ASSISTANT_URL . 'public/js/shihela-contextual-site-assistant-public.js',
			array(),
			$this->version,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		$default_wa_msg = __( 'Hello Support, I am viewing the page {page_title} ({page_url}) and need assistance.', 'shihela-contextual-site-assistant' );

		wp_localize_script( $this->plugin_name, 'shihelaContextualSiteAssistantPublic', array(
			'rest_url'               => esc_url_raw( get_rest_url( null, '/shihela-contextual-site-assistant/v1/chat' ) ),
			'nonce'                  => wp_create_nonce( 'wp_rest' ),
			'css_url'                => esc_url_raw( add_query_arg( 'ver', $this->version, SHIHELA_CONTEXTUAL_SITE_ASSISTANT_URL . 'public/css/shihela-contextual-site-assistant-public.css' ) ),
			'bot_name'               => wp_strip_all_tags( (string) get_option( 'shihela_contextual_site_assistant_bot_name', 'Shihela AI' ) ),
			'welcome_message'        => wp_strip_all_tags( (string) get_option( 'shihela_contextual_site_assistant_welcome_message', __( 'Hello! I am your AI assistant. How can I help you today?', 'shihela-contextual-site-assistant' ) ) ),
			'theme_color'            => sanitize_hex_color( get_option( 'shihela_contextual_site_assistant_theme_color', '#4f46e5' ) ),
			'theme_mode'             => sanitize_key( (string) get_option( 'shihela_contextual_site_assistant_theme_mode', 'light' ) ),
			'post_id'                => $post_id,
			'page_title'             => html_entity_decode( wp_strip_all_tags( (string) $page_title ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
			'page_url'               => esc_url_raw( $page_url ),
			'reset_confirm'          => __( 'Are you sure you want to reset this conversation?', 'shihela-contextual-site-assistant' ),
			'error_response'         => __( 'Sorry, I encountered an issue processing that response.', 'shihela-contextual-site-assistant' ),
			'error_connection'       => __( 'Sorry, I am unable to connect to the assistant right now. Please try again later.', 'shihela-contextual-site-assistant' ),
			'max_length'             => (int) get_option( 'shihela_contextual_site_assistant_max_length', 300 ),
			'daily_limit_reached'    => $daily_limit_reached,
			'error_daily_limit'      => __( 'Daily query limit reached. Please try again tomorrow.', 'shihela-contextual-site-assistant' ),
			'suggestion_chips'       => $chips_array,
			'whatsapp_number'        => sanitize_text_field( get_option( 'shihela_contextual_site_assistant_whatsapp_number', '' ) ),
			'whatsapp_message'       => wp_strip_all_tags( (string) get_option( 'shihela_contextual_site_assistant_whatsapp_message', $default_wa_msg ) ),
			'whatsapp_header_btn'    => get_option( 'shihela_contextual_site_assistant_whatsapp_header_btn', '1' ),
			'whatsapp_default_label' => __( 'Chat via WhatsApp', 'shihela-contextual-site-assistant' ),
			'greeting_enable'        => '1' === get_option( 'shihela_contextual_site_assistant_greeting_enable', '0' ),
			'greeting_delay'         => max( 1, (int) get_option( 'shihela_contextual_site_assistant_greeting_delay', 5 ) ),
			'greeting_text'          => wp_strip_all_tags( (string) get_option( 'shihela_contextual_site_assistant_greeting_text', __( 'Need help with this page? Ask me!', 'shihela-contextual-site-assistant' ) ) ),
			'aria_open_chat'         => __( 'Open Chat Assistant', 'shihela-contextual-site-assistant' ),
			'aria_panel_label'       => __( 'Site Chat Assistant', 'shihela-contextual-site-assistant' ),
			'aria_typing'            => __( 'Assistant is typing...', 'shihela-contextual-site-assistant' ),
		) );
	}

	/**
	 * Append the floating chat widget HTML markup into page footers.
	 *
	 * @since    1.0.0
	 */
	public function render_chat_widget() {
		if ( ! $this->should_display_widget() ) {
			return;
		}

		require_once SHIHELA_CONTEXTUAL_SITE_ASSISTANT_PATH . 'public/partials/shihela-contextual-site-assistant-public-display.php';
	}

	/**
	 * Determine whether the floating chat widget should be displayed on the current page.
	 * Evaluates authorization, per-page metabox opt-out, and excluded page IDs.
	 *
	 * @since    1.5.0
	 * @return   bool True if widget should render, false otherwise.
	 */
	private function should_display_widget() {
		if ( ! $this->is_user_authorized() ) {
			return false;
		}

		$post_id        = is_singular() ? (int) get_queried_object_id() : 0;
		$should_display = true;

		if ( $post_id > 0 ) {
			// Check per-page metabox opt-out.
			if ( '1' === get_post_meta( $post_id, '_shihela_csa_disable_widget', true ) ) {
				$should_display = false;
			}

			// Check global excluded page IDs list.
			if ( $should_display ) {
				$raw_excluded = (string) get_option( 'shihela_contextual_site_assistant_excluded_pages', '' );
				if ( '' !== trim( $raw_excluded ) ) {
					$excluded_ids = array_filter( array_map( 'absint', explode( ',', $raw_excluded ) ) );
					if ( in_array( $post_id, $excluded_ids, true ) ) {
						$should_display = false;
					}
				}
			}
		}

		/**
		 * Filter whether the floating chat widget should display on the current request.
		 *
		 * @since 1.5.0
		 * @param bool $should_display Whether widget is allowed to display.
		 * @param int  $post_id        Current singular post/page ID (0 for archives/home).
		 */
		return (bool) apply_filters( 'shihela_assistant_should_display', $should_display, $post_id );
	}

	/**
	 * Register the REST API route.
	 *
	 * @since    1.0.0
	 */
	public function register_rest_routes() {
		register_rest_route( 'shihela-contextual-site-assistant/v1', '/chat', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'handle_chat_request' ),
			'permission_callback' => array( $this, 'check_chat_permissions' ),
			'args'                => array(
				'message'    => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'post_id'    => array(
					'required'          => false,
					'type'              => 'integer',
					'default'           => 0,
					'sanitize_callback' => 'absint',
				),
				'history'    => array(
					'required' => false,
					'type'     => 'array',
					'default'  => array(),
				),
				'session_id' => array(
					'required'          => false,
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'hp_value'   => array(
					'required'          => false,
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		) );
	}

	/**
	 * Permissions callback for the public REST route.
	 *
	 * @since    1.0.0
	 */
	public function check_chat_permissions( $request ) {
		$nonce          = $request->get_header( 'X-WP-Nonce' );
		$nonce_valid    = ! empty( $nonce ) && wp_verify_nonce( $nonce, 'wp_rest' );
		$access_control = get_option( 'shihela_contextual_site_assistant_access_control', 'public' );

		// Require valid nonce for logged-in/admin modes or authenticated users.
		// For anonymous public visitors behind full-page caches (where nonces may expire after 24h),
		// fall back to same-origin header verification when nonce is missing/expired.
		if ( ! $nonce_valid ) {
			$allow_public_origin_fallback = ( 'public' === $access_control && ! is_user_logged_in() );
			if ( ! $allow_public_origin_fallback || ! $this->is_same_origin_request( $request ) ) {
				return new WP_Error(
					'rest_forbidden',
					__( 'Security verification failed.', 'shihela-contextual-site-assistant' ),
					array( 'status' => 403 )
				);
			}
		}

		// 1. Role-based Access Control Check
		if ( ! $this->is_user_authorized() ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You are not authorized to use the assistant.', 'shihela-contextual-site-assistant' ),
				array( 'status' => 403 )
			);
		}

		$params   = $request->get_json_params();
		$message  = isset( $params['message'] ) ? sanitize_text_field( $params['message'] ) : '';
		$hp_value = isset( $params['hp_value'] ) ? sanitize_text_field( $params['hp_value'] ) : '';

		// 2. Spam Honeypot Check
		if ( ! empty( $hp_value ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Spam detected.', 'shihela-contextual-site-assistant' ),
				array( 'status' => 403 )
			);
		}

		if ( empty( $message ) ) {
			return new WP_Error(
				'rest_invalid_param',
				__( 'Message cannot be empty.', 'shihela-contextual-site-assistant' ),
				array( 'status' => 400 )
			);
		}

		// 3. Character Length Validation
		$max_length = (int) get_option( 'shihela_contextual_site_assistant_max_length', 300 );
		if ( $max_length > 0 && mb_strlen( $message ) > $max_length ) {
			return new WP_Error(
				'rest_invalid_param',
				/* translators: %d: Maximum allowed message character length */
				sprintf( __( 'Message exceeds maximum length of %d characters.', 'shihela-contextual-site-assistant' ), $max_length ),
				array( 'status' => 400 )
			);
		}

		$ip = $this->get_visitor_ip();

		// 4. Global Daily Limit validation
		if ( $this->is_daily_limit_reached() ) {
			return new WP_Error(
				'rest_too_many_requests',
				__( 'Daily query limit reached. Please try again tomorrow.', 'shihela-contextual-site-assistant' ),
				array( 'status' => 429 )
			);
		}

		// 5. Per-IP Daily Limit validation
		if ( $this->is_ip_daily_limit_reached( $ip ) ) {
			return new WP_Error(
				'rest_too_many_requests',
				__( 'Daily query limit reached for your session. Please try again tomorrow.', 'shihela-contextual-site-assistant' ),
				array( 'status' => 429 )
			);
		}

		// 6. Minute Rate Limiting (bypassed for administrators)
		$limit_per_minute = (int) get_option( 'shihela_contextual_site_assistant_rate_limit', 5 );
		if ( ! current_user_can( 'manage_options' ) && $limit_per_minute > 0 ) {
			$rate_limit_key = 'shihela_contextual_site_assistant_rate_' . md5( $ip );
			$requests_count = get_transient( $rate_limit_key );

			if ( false !== $requests_count && $requests_count >= $limit_per_minute ) {
				return new WP_Error(
					'rest_too_many_requests',
					__( 'Too many requests. Please slow down and try again later.', 'shihela-contextual-site-assistant' ),
					array( 'status' => 429 )
				);
			}
		}

		return true;
	}

	/**
	 * Handles the public REST chat request.
	 *
	 * @since    1.0.0
	 */
	public function handle_chat_request( $request ) {
		/**
		 * Action hook fired before processing REST API chat request.
		 *
		 * @since 1.1.0
		 * @param WP_REST_Request $request REST API request object.
		 */
		do_action( 'shihela_assistant_before_chat_request', $request );

		// Increment limit counters and rate limit keys (safe side-effects inside controller)
		$ip = $this->get_visitor_ip();
		$this->increment_daily_count();
		$this->increment_ip_daily_count( $ip );

		$limit_per_minute = (int) get_option( 'shihela_contextual_site_assistant_rate_limit', 5 );
		if ( ! current_user_can( 'manage_options' ) && $limit_per_minute > 0 ) {
			$rate_limit_key = 'shihela_contextual_site_assistant_rate_' . md5( $ip );
			$requests_count = get_transient( $rate_limit_key );

			if ( false === $requests_count ) {
				set_transient( $rate_limit_key, 1, 60 ); // 60 seconds expiration
			} else {
				set_transient( $rate_limit_key, (int) $requests_count + 1, 60 );
			}
		}

		$params     = $request->get_json_params();
		$message    = isset( $params['message'] ) ? sanitize_text_field( $params['message'] ) : '';
		$post_id    = isset( $params['post_id'] ) ? absint( $params['post_id'] ) : 0;
		$history    = isset( $params['history'] ) ? $this->sanitize_chat_history( $params['history'] ) : array();
		$session_id = isset( $params['session_id'] ) ? sanitize_text_field( $params['session_id'] ) : '';

		// Restrict History Size (sends only last X messages to save tokens)
		$max_history = (int) get_option( 'shihela_contextual_site_assistant_max_history', 10 );
		if ( $max_history > 0 && count( $history ) > $max_history ) {
			$history = array_slice( $history, -$max_history );
		}

		require_once SHIHELA_CONTEXTUAL_SITE_ASSISTANT_PATH . 'includes/class-shihela-contextual-site-assistant-api.php';
		$api      = new Shihela_Contextual_Site_Assistant_API();
		$response = $api->get_response( $message, $post_id, $history );

		if ( is_wp_error( $response ) ) {
			$error_message = current_user_can( 'manage_options' )
				? $response->get_error_message()
				: __( 'Sorry, I am unable to process your request right now. Please try again later.', 'shihela-contextual-site-assistant' );

			return new WP_REST_Response( array(
				'success' => false,
				'message' => $error_message,
			), 500 );
		}

		// Parse and capture lead info if present
		if ( is_string( $response ) && preg_match( '/\[LEAD:\s*({.*?})\s*\]/s', $response, $matches ) ) {
			$lead_json = $matches[1];
			$lead_data = json_decode( $lead_json, true );
			if ( $lead_data ) {
				global $wpdb;
				$table_name = $wpdb->prefix . 'shihela_contextual_site_assistant_leads';

				// Check if a lead has already been captured for this session
				$lead_exists = 0;
				if ( ! empty( $session_id ) ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					$lead_exists = $wpdb->get_var( $wpdb->prepare(
						"SELECT COUNT(*) FROM %i WHERE session_id = %s",
						$table_name,
						$session_id
					) );
				}

				if ( ! $lead_exists ) {
					$name     = isset( $lead_data['name'] ) ? sanitize_text_field( $lead_data['name'] ) : '';
					$contact  = isset( $lead_data['contact'] ) ? sanitize_text_field( $lead_data['contact'] ) : '';
					$details  = isset( $lead_data['details'] ) ? sanitize_textarea_field( $lead_data['details'] ) : '';
					$page_url = $post_id ? get_permalink( $post_id ) : '';

					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->insert(
						$table_name,
						array(
							'lead_date'    => current_time( 'mysql' ),
							'lead_name'    => $name,
							'lead_email'   => $contact,
							'lead_message' => $details,
							'page_url'     => $page_url,
							'session_id'   => $session_id,
						)
					);
					$new_lead_id = $wpdb->insert_id;

					/**
					 * Action hook fired after a new lead is captured and saved.
					 *
					 * @since 1.1.0
					 * @param int    $new_lead_id Database ID of newly created lead.
					 * @param array  $lead_data   Array of lead details (name, contact, details).
					 * @param string $page_url    Page URL origin.
					 * @param string $session_id  Chat session identifier.
					 */
					do_action( 'shihela_assistant_lead_captured', $new_lead_id, $lead_data, $page_url, $session_id );

					// Send notification email to Admin
					$admin_email = sanitize_email( get_option( 'admin_email' ) );
					/* translators: %s: Name of the captured lead */
					$subject     = sprintf( esc_html__( '[Shihela Site Assistant] New Lead Captured: %s', 'shihela-contextual-site-assistant' ), $name );
					$date_now    = current_time( 'mysql' );

					// Menyusun format body email dalam struktur HTML yang bersih dan rapi
					$email_body  = '<html><body style="font-family: Arial, sans-serif; color: #333; line-height: 1.6;">';
					$email_body .= sprintf( '<h2 style="color: #4f46e5;">%s</h2>', esc_html__( 'Hello Admin,', 'shihela-contextual-site-assistant' ) );
					$email_body .= sprintf( '<p>%s</p>', esc_html__( 'A new lead has been captured by your website AI assistant (Shihela Contextual Site Assistant). Here are the details:', 'shihela-contextual-site-assistant' ) );
					$email_body .= '<table style="border-collapse: collapse; width: 100%; max-width: 600px; margin-top: 15px; font-size: 14px;">';
					$email_body .= sprintf( '<tr style="background-color: #f9f9f9;"><td style="padding: 10px; border: 1px solid #ddd; font-weight: bold; width: 25%%;">%s</td><td style="padding: 10px; border: 1px solid #ddd;">%s</td></tr>', esc_html__( 'Name:', 'shihela-contextual-site-assistant' ), esc_html( $name ) );
					$email_body .= sprintf( '<tr><td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">%s</td><td style="padding: 10px; border: 1px solid #ddd;">%s</td></tr>', esc_html__( 'Contact:', 'shihela-contextual-site-assistant' ), esc_html( $contact ) );
					$email_body .= sprintf( '<tr style="background-color: #f9f9f9;"><td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">%s</td><td style="padding: 10px; border: 1px solid #ddd;">%s</td></tr>', esc_html__( 'Details:', 'shihela-contextual-site-assistant' ), nl2br( esc_html( $details ) ) );
					$email_body .= sprintf( '<tr><td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">%s</td><td style="padding: 10px; border: 1px solid #ddd;"><a href="%s" target="_blank" rel="noopener noreferrer">%s</a></td></tr>', esc_html__( 'Origin Page:', 'shihela-contextual-site-assistant' ), esc_url( $page_url ), esc_url( $page_url ? $page_url : home_url() ) );
					$email_body .= sprintf( '<tr style="background-color: #f9f9f9;"><td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">%s</td><td style="padding: 10px; border: 1px solid #ddd;">%s</td></tr>', esc_html__( 'Date:', 'shihela-contextual-site-assistant' ), esc_html( $date_now ) );
					$email_body .= '</table>';
					$email_body .= sprintf( '<p style="font-size: 12px; color: #777; margin-top: 25px;">%s</p>', esc_html__( 'This email was automatically generated by the Shihela Contextual Site Assistant Plugin.', 'shihela-contextual-site-assistant' ) );
					$email_body .= '</body></html>';

					// Respect site/SMTP configured From address; only set HTML content-type and Reply-To.
					$headers = array(
						'Content-Type: text/html; charset=UTF-8',
						'Reply-To: ' . $admin_email,
					);

					/**
					 * Filter email headers for captured lead notifications.
					 *
					 * @since 1.4.0
					 * @param array  $headers     Email headers array.
					 * @param string $admin_email Recipient admin email.
					 * @param array  $lead_data   Captured lead data.
					 */
					$headers = apply_filters( 'shihela_assistant_lead_email_headers', $headers, $admin_email, $lead_data );

					// Send lead notification via WordPress core mail.
					wp_mail( $admin_email, $subject, $email_body, $headers );


					// Trigger webhook if URL is configured (using wp_safe_remote_post to mitigate SSRF)
					$webhook_url = esc_url_raw( get_option( 'shihela_contextual_site_assistant_webhook_url', '' ), array( 'http', 'https' ) );
					if ( ! empty( $webhook_url ) ) {
						$lead_payload = array(
							'event'      => 'lead_captured',
							'lead_id'    => $new_lead_id,
							'lead_date'  => current_time( 'mysql' ),
							'name'       => $name,
							'contact'    => $contact,
							'details'    => $details,
							'page_url'   => $page_url,
							'session_id' => $session_id,
						);

						$json_body       = wp_json_encode( $lead_payload );
						$webhook_headers = array(
							'Content-Type' => 'application/json; charset=utf-8',
						);

						// Optional HMAC-SHA256 signing for enterprise webhook verification.
						$webhook_secret = (string) get_option( 'shihela_contextual_site_assistant_webhook_secret', '' );
						if ( '' !== $webhook_secret && is_string( $json_body ) ) {
							$timestamp                              = (string) time();
							$signature                              = hash_hmac( 'sha256', $timestamp . '.' . $json_body, $webhook_secret );
							$webhook_headers['X-Shihela-Timestamp'] = $timestamp;
							$webhook_headers['X-Shihela-Signature'] = 'sha256=' . $signature;
						}

						wp_safe_remote_post( $webhook_url, array(
							'method'   => 'POST',
							'headers'  => $webhook_headers,
							'body'     => $json_body,
							'timeout'  => 15,
							'blocking' => false, // Fire-and-forget (non-blocking) so it does not delay the chat response
						) );
					}
				}
			}

			// Clean the tag from the final response text
			$response = preg_replace( '/\[LEAD:\s*({.*?})\s*\]/s', '', $response );
			$response = trim( $response );
		}

		return new WP_REST_Response( array(
			'success'  => true,
			'response' => $response,
		), 200 );
	}

	private function is_configured() {
		return function_exists( 'wp_ai_client_prompt' );
	}

	/**
	 * Check if current user is authorized to use the chat assistant based on Access Control settings.
	 *
	 * @since    1.1.0
	 * @return   bool True if authorized, false otherwise.
	 */
	private function is_user_authorized() {
		if ( ! $this->is_configured() ) {
			return false;
		}

		$access_control = get_option( 'shihela_contextual_site_assistant_access_control', 'public' );

		if ( 'admin' === $access_control ) {
			return current_user_can( 'manage_options' );
		}

		if ( 'logged_in' === $access_control ) {
			return is_user_logged_in();
		}

		return true;
	}

	/**
	 * Checks if the daily global API limit has been reached.
	 *
	 * @since    1.1.0
	 * @return   bool True if limit reached, false otherwise.
	 */
	private function is_daily_limit_reached() {
		if ( current_user_can( 'manage_options' ) ) {
			return false;
		}

		$daily_limit = (int) get_option( 'shihela_contextual_site_assistant_daily_limit', 100 );
		if ( $daily_limit <= 0 ) {
			return false;
		}

		return $this->get_daily_count() >= $daily_limit;
	}

	/**
	 * Get current daily count. Resets automatically at midnight local WP time.
	 *
	 * @since    1.1.0
	 * @return   int Daily requests count.
	 */
	private function get_daily_count() {
		$data  = get_option( 'shihela_contextual_site_assistant_daily_data', array() );
		$today = current_time( 'Y-m-d' );

		if ( ! is_array( $data ) || empty( $data['date'] ) || $data['date'] !== $today ) {
			return 0;
		}

		return isset( $data['count'] ) ? (int) $data['count'] : 0;
	}

	/**
	 * Increment the daily count.
	 *
	 * @since    1.1.0
	 */
	private function increment_daily_count() {
		$today = current_time( 'Y-m-d' );
		$data  = get_option( 'shihela_contextual_site_assistant_daily_data', array() );

		if ( ! is_array( $data ) || empty( $data['date'] ) || $data['date'] !== $today ) {
			$data = array(
				'date'  => $today,
				'count' => 1,
			);
		} else {
			$data['count'] = ( isset( $data['count'] ) ? (int) $data['count'] : 0 ) + 1;
		}

		// Store with autoload = false to prevent alloptions cache thrashing on high-traffic sites.
		update_option( 'shihela_contextual_site_assistant_daily_data', $data, false );
	}

	/**
	 * Verify that an unauthenticated public REST request originates from this site.
	 *
	 * @since    1.4.0
	 * @param    WP_REST_Request $request REST API request.
	 * @return   bool True if Origin or Referer matches site host.
	 */
	private function is_same_origin_request( $request ) {
		$site_host = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( empty( $site_host ) ) {
			return false;
		}

		$origin  = $request->get_header( 'origin' );
		$referer = $request->get_header( 'referer' );
		$source  = ! empty( $origin ) ? $origin : $referer;

		if ( empty( $source ) ) {
			return false;
		}

		$source_host = wp_parse_url( $source, PHP_URL_HOST );
		return is_string( $source_host ) && 0 === strcasecmp( $site_host, $source_host );
	}

	/**
	 * Get visitor IP address safely.
	 *
	 * @since    1.2.0
	 * @return   string IP address.
	 */
	private function get_visitor_ip() {
		$ip = '127.0.0.1'; // Default fallback if REMOTE_ADDR is not set (e.g., CLI/Cron)
		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		/**
		 * Filter the resolved visitor IP address (e.g. for trusted reverse proxy / CDN environments).
		 *
		 * @since 1.4.0
		 * @param string $ip Visitor IP address.
		 */
		return (string) apply_filters( 'shihela_assistant_visitor_ip', $ip );
	}

	/**
	 * Checks if the daily limit per IP has been reached.
	 *
	 * @since    1.2.0
	 * @param    string $ip Visitor IP address.
	 * @return   bool True if limit reached, false otherwise.
	 */
	private function is_ip_daily_limit_reached( $ip ) {
		if ( current_user_can( 'manage_options' ) ) {
			return false;
		}

		$ip_limit = (int) get_option( 'shihela_contextual_site_assistant_ip_daily_limit', 20 );
		if ( $ip_limit <= 0 ) {
			return false;
		}

		$ip_limit_key = 'shihela_daily_ip_' . md5( $ip );
		$stored       = get_transient( $ip_limit_key );
		if ( is_array( $stored ) ) {
			$expires_at = isset( $stored['expires_at'] ) ? (int) $stored['expires_at'] : 0;
			if ( $expires_at > 0 && time() >= $expires_at ) {
				return false;
			}
			$count = isset( $stored['count'] ) ? (int) $stored['count'] : 0;
		} else {
			$count = (int) $stored;
		}

		return $count >= $ip_limit;
	}

	/**
	 * Increment the daily request counter for a specific IP (compatible with Redis/Memcached Object Cache).
	 *
	 * @since    1.2.0
	 * @param    string $ip Visitor IP address.
	 */
	private function increment_ip_daily_count( $ip ) {
		$ip_limit = (int) get_option( 'shihela_contextual_site_assistant_ip_daily_limit', 20 );
		if ( $ip_limit <= 0 ) {
			return;
		}

		$ip_limit_key = 'shihela_daily_ip_' . md5( $ip );
		$stored       = get_transient( $ip_limit_key );
		$now_ts       = time();

		if ( is_array( $stored ) && isset( $stored['expires_at'] ) && (int) $stored['expires_at'] > $now_ts ) {
			$expires_at = (int) $stored['expires_at'];
			$count      = ( isset( $stored['count'] ) ? (int) $stored['count'] : 0 ) + 1;
			$remaining  = max( 60, $expires_at - $now_ts );
		} else {
			$timezone   = wp_timezone();
			$now        = new DateTimeImmutable( 'now', $timezone );
			$tomorrow   = new DateTimeImmutable( 'tomorrow', $timezone );
			$remaining  = max( 60, min( 86400, $tomorrow->getTimestamp() - $now->getTimestamp() ) );
			$expires_at = $now_ts + $remaining;
			$count      = is_numeric( $stored ) ? ( (int) $stored + 1 ) : 1;
		}

		set_transient(
			$ip_limit_key,
			array(
				'count'      => $count,
				'expires_at' => $expires_at,
			),
			$remaining
		);
	}

	/**
	 * Sanitize the conversation history array with role allowlisting and character limits.
	 *
	 * @since    1.0.0
	 * @param    mixed $history Raw history payload from client.
	 * @return   array Sanitized history entries.
	 */
	private function sanitize_chat_history( $history ) {
		if ( ! is_array( $history ) ) {
			return array();
		}

		$allowed_roles   = array( 'user', 'assistant' );
		$max_length      = max( 300, (int) get_option( 'shihela_contextual_site_assistant_max_length', 300 ) );
		$max_item_chars  = min( 2000, $max_length * 3 );
		$max_total_chars = 8000;
		$total_chars     = 0;

		$sanitized = array();
		foreach ( $history as $msg ) {
			if ( ! is_array( $msg ) || ! isset( $msg['role'] ) || ! isset( $msg['content'] ) ) {
				continue;
			}

			$role = sanitize_text_field( (string) $msg['role'] );
			if ( ! in_array( $role, $allowed_roles, true ) ) {
				continue;
			}

			$content = sanitize_textarea_field( (string) $msg['content'] );
			if ( '' === $content ) {
				continue;
			}

			if ( mb_strlen( $content ) > $max_item_chars ) {
				$content = mb_substr( $content, 0, $max_item_chars );
			}

			$item_len = mb_strlen( $content );
			if ( $total_chars + $item_len > $max_total_chars ) {
				break;
			}

			$total_chars += $item_len;
			$sanitized[]  = array(
				'role'    => $role,
				'content' => $content,
			);
		}

		return $sanitized;
	}
}
