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
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		// Only enqueue if the user is authorized to use the widget
		if ( ! $this->is_user_authorized() ) {
			return;
		}
		wp_enqueue_style( $this->plugin_name, SHIHELA_CONTEXTUAL_SITE_ASSISTANT_URL . 'public/css/shihela-contextual-site-assistant-public.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		if ( ! $this->is_user_authorized() ) {
			return;
		}

		$ip                  = $this->get_visitor_ip();
		$daily_limit_reached = $this->is_daily_limit_reached() || $this->is_ip_daily_limit_reached( $ip );

		wp_enqueue_script( $this->plugin_name, SHIHELA_CONTEXTUAL_SITE_ASSISTANT_URL . 'public/js/shihela-contextual-site-assistant-public.js', array( 'jquery' ), $this->version, true );
		wp_localize_script( $this->plugin_name, 'shihelaContextualSiteAssistantPublic', array(
			'rest_url'            => esc_url_raw( get_rest_url( null, '/shihela-contextual-site-assistant/v1/chat' ) ),
			'nonce'               => wp_create_nonce( 'wp_rest' ), // Standard WP REST API Nonce
			'bot_name'            => esc_html( get_option( 'shihela_contextual_site_assistant_bot_name', 'Shihela AI' ) ),
			'welcome_message'     => esc_html( get_option( 'shihela_contextual_site_assistant_welcome_message', 'Hello! I am your AI assistant.' ) ),
			'theme_color'         => sanitize_hex_color( get_option( 'shihela_contextual_site_assistant_theme_color', '#4f46e5' ) ),
			'post_id'             => get_the_ID() ? get_the_ID() : 0,
			'reset_confirm'       => __( 'Are you sure you want to reset this conversation?', 'shihela-contextual-site-assistant' ),
			'error_response'      => __( 'Sorry, I encountered an issue processing that response.', 'shihela-contextual-site-assistant' ),
			'error_connection'    => __( 'Sorry, I am unable to connect to the assistant right now. Please try again later.', 'shihela-contextual-site-assistant' ),
			'max_length'          => (int) get_option( 'shihela_contextual_site_assistant_max_length', 300 ),
			'daily_limit_reached' => $daily_limit_reached,
			'error_daily_limit'   => __( 'Daily query limit reached. Please try again tomorrow.', 'shihela-contextual-site-assistant' ),
		) );
	}

	/**
	 * Append the floating chat widget HTML markup into page footers.
	 *
	 * @since    1.0.0
	 */
	public function render_chat_widget() {
		if ( ! $this->is_user_authorized() ) {
			return;
		}

		require_once SHIHELA_CONTEXTUAL_SITE_ASSISTANT_PATH . 'public/partials/shihela-contextual-site-assistant-public-display.php';
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
		) );
	}

	/**
	 * Permissions callback for the public REST route.
	 *
	 * @since    1.0.0
	 */
	public function check_chat_permissions( $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'Security verification failed.', 'shihela-contextual-site-assistant' ),
				array( 'status' => 403 )
			);
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

		// Allow PRO version to override incoming query or context
		$message    = apply_filters( 'shihela_chat_message', $message, $post_id );
		$history    = apply_filters( 'shihela_chat_history', $history, $post_id );

		// Restrict History Size (sends only last X messages to save tokens)
		$max_history = (int) get_option( 'shihela_contextual_site_assistant_max_history', 10 );
		if ( $max_history > 0 && count( $history ) > $max_history ) {
			$history = array_slice( $history, -$max_history );
		}

		require_once SHIHELA_CONTEXTUAL_SITE_ASSISTANT_PATH . 'includes/class-shihela-contextual-site-assistant-api.php';
		$api      = new Shihela_Contextual_Site_Assistant_API();
		$response = $api->get_response( $message, $post_id, $history );

		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response( array(
				'success' => false,
				'message' => $response->get_error_message(),
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
					$email_body .= sprintf( '<tr><td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">%s</td><td style="padding: 10px; border: 1px solid #ddd;"><a href="%s" target="_blank">%s</a></td></tr>', esc_html__( 'Origin Page:', 'shihela-contextual-site-assistant' ), esc_url( $page_url ), esc_url( $page_url ? $page_url : home_url() ) );
					$email_body .= sprintf( '<tr style="background-color: #f9f9f9;"><td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">%s</td><td style="padding: 10px; border: 1px solid #ddd;">%s</td></tr>', esc_html__( 'Date:', 'shihela-contextual-site-assistant' ), esc_html( $date_now ) );
					$email_body .= '</table>';
					$email_body .= sprintf( '<p style="font-size: 12px; color: #777; margin-top: 25px;">%s</p>', esc_html__( 'This email was automatically generated by the Shihela Contextual Site Assistant Plugin.', 'shihela-contextual-site-assistant' ) );
					$email_body .= '</body></html>';

					// Header email yang aman untuk menghindari masalah SPF/DMARC jika admin_email menggunakan domain eksternal seperti Gmail
					$site_domain = wp_parse_url( home_url(), PHP_URL_HOST );
					$from_email  = 'wordpress@' . $site_domain;

					$headers = array(
						'Content-Type: text/html; charset=UTF-8',
						'From: Shihela Site Assistant <' . $from_email . '>',
						'Reply-To: ' . $admin_email,
					);

					// Eksekusi pengiriman email bawaan WordPress core
					wp_mail( $admin_email, $subject, $email_body, $headers );


					// Trigger webhook if URL is configured
					$webhook_url = get_option( 'shihela_contextual_site_assistant_webhook_url', '' );
					if ( ! empty( $webhook_url ) ) {
						$lead_payload = array(
							'event'      => 'lead_captured',
							'lead_id'    => $wpdb->insert_id,
							'lead_date'  => current_time( 'mysql' ),
							'name'       => $name,
							'contact'    => $contact,
							'details'    => $details,
							'page_url'   => $page_url,
							'session_id' => $session_id,
						);

						wp_remote_post( $webhook_url, array(
							'method'    => 'POST',
							'headers'   => array(
								'Content-Type' => 'application/json; charset=utf-8',
							),
							'body'      => wp_json_encode( $lead_payload ),
							'timeout'   => 15,
							'blocking'  => false, // Fire-and-forget (non-blocking) so it does not delay the chat response
						) );
					}
				}
			}

			// Clean the tag from the final response text
			$response = preg_replace( '/\[LEAD:\s*({.*?})\s*\]/s', '', $response );
			$response = trim( $response );
		}

		// Trigger action for PRO extension (logging, tracking, CRM etc)
		do_action( 'shihela_chat_response_received', $response, $message, $session_id, $post_id );

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

		update_option( 'shihela_contextual_site_assistant_daily_data', $data );
	}

	/**
	 * Get visitor IP address safely.
	 *
	 * @since    1.2.0
	 * @return   string IP address.
	 */
	private function get_visitor_ip() {
		$ip = '';
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			// In case of proxies, get the first IP in the list
			$forwarded_ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			$ip            = trim( reset( $forwarded_ips ) );
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}
		return $ip;
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
		$count        = (int) get_transient( $ip_limit_key );
		return $count >= $ip_limit;
	}

	/**
	 * Increment the daily request counter for a specific IP.
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
		$count        = get_transient( $ip_limit_key );

		if ( false === $count ) {
			$timezone = wp_timezone();
			$now      = new DateTime( 'now', $timezone );
			$tomorrow = new DateTime( 'tomorrow', $timezone );
			$seconds  = $tomorrow->getTimestamp() - $now->getTimestamp();
			$seconds  = max( 3600, min( 86400, $seconds ) );

			set_transient( $ip_limit_key, 1, $seconds );
		} else {
			$timeout   = get_option( '_transient_timeout_' . $ip_limit_key );
			$remaining = $timeout ? $timeout - time() : 86400;
			$remaining = max( 60, $remaining );
			set_transient( $ip_limit_key, (int) $count + 1, $remaining );
		}
	}

	/**
	 * Sanitize the conversation history array.
	 *
	 * @since    1.0.0
	 */
	private function sanitize_chat_history( $history ) {
		if ( ! is_array( $history ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $history as $msg ) {
			if ( isset( $msg['role'] ) && isset( $msg['content'] ) ) {
				$sanitized[] = array(
					'role'    => sanitize_text_field( $msg['role'] ),
					'content' => sanitize_text_field( $msg['content'] ),
				);
			}
		}

		return $sanitized;
	}
}
