<?php
/**
 * The admin-specific functionality of the plugin.
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
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Shihela_Contextual_Site_Assistant
 * @subpackage Shihela_Contextual_Site_Assistant/includes
 * @author     shihela
 */
class Shihela_Contextual_Site_Assistant_Admin {

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
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 * @param    string $hook Current admin page hook suffix.
	 */
	public function enqueue_styles( $hook = '' ) {
		if ( false === strpos( (string) $hook, 'shihela-contextual-site-assistant' ) ) {
			return;
		}
		wp_enqueue_style( $this->plugin_name, SHIHELA_CONTEXTUAL_SITE_ASSISTANT_URL . 'admin/css/shihela-contextual-site-assistant-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 * @param    string $hook Current admin page hook suffix.
	 */
	public function enqueue_scripts( $hook ) {
		if ( false === strpos( (string) $hook, 'shihela-contextual-site-assistant' ) ) {
			return;
		}
		wp_enqueue_script(
			$this->plugin_name . '-admin',
			SHIHELA_CONTEXTUAL_SITE_ASSISTANT_URL . 'admin/js/shihela-contextual-site-assistant-admin.js',
			array( 'jquery' ),
			$this->version,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_localize_script(
			$this->plugin_name . '-admin',
			'shihelaContextualSiteAssistantAdmin',
			array(
				'confirm_delete_lead' => __( 'Are you sure you want to delete this lead?', 'shihela-contextual-site-assistant' ),
			)
		);
	}

	/**
	 * Add an options page under Settings.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {
		// Inline SVG icon for crisp rendering in WordPress 7.2+ SVG admin sidebar.
		$menu_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="black" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21c4.97 0 9-4.03 9-9s-4.03-9-9-9-9 4.03-9 9c0 1.81.53 3.49 1.45 4.89L3.03 20.45c-.1.25-.01.53.21.67.21.14.49.15.72.02l3.82-2.09c1.28.62 2.72.95 4.22.95z"/><circle cx="8.5" cy="12" r="1" fill="black"/><circle cx="12" cy="12" r="1" fill="black"/><circle cx="15.5" cy="12" r="1" fill="black"/></svg>';
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		$menu_icon = 'data:image/svg+xml;base64,' . base64_encode( $menu_svg );

		// Register top-level parent menu
		add_menu_page(
			__( 'Shihela Site Assistant', 'shihela-contextual-site-assistant' ),
			__( 'Shihela Site Assistant', 'shihela-contextual-site-assistant' ),
			'manage_options',
			'shihela-contextual-site-assistant',
			array( $this, 'display_plugin_admin_page' ),
			$menu_icon,
			90
		);

		// Settings submenu
		add_submenu_page(
			'shihela-contextual-site-assistant',
			__( 'Shihela Contextual Site Assistant Settings', 'shihela-contextual-site-assistant' ),
			__( 'Settings', 'shihela-contextual-site-assistant' ),
			'manage_options',
			'shihela-contextual-site-assistant',
			array( $this, 'display_plugin_admin_page' )
		);

		// Leads submenu
		add_submenu_page(
			'shihela-contextual-site-assistant',
			__( 'Shihela Contextual Site Assistant Leads', 'shihela-contextual-site-assistant' ),
			__( 'Leads', 'shihela-contextual-site-assistant' ),
			'manage_options',
			'shihela-contextual-site-assistant-leads',
			array( $this, 'display_leads_page' )
		);
	}

	/**
	 * Handle lead actions (e.g. deletion) before headers are sent.
	 *
	 * @since    1.0.0
	 */
	public function handle_lead_actions() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['page'] ) && 'shihela-contextual-site-assistant-leads' === $_GET['page'] && isset( $_GET['action'] ) && 'delete' === $_GET['action'] && isset( $_GET['id'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$lead_id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

			// Verify security Nonce
			if ( ! wp_verify_nonce( $nonce, 'delete_lead_' . $lead_id ) ) {
				wp_die( esc_html__( 'Security verification failed.', 'shihela-contextual-site-assistant' ), 403 );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to perform this action.', 'shihela-contextual-site-assistant' ), 403 );
			}

			global $wpdb;
			$table_name = $wpdb->prefix . 'shihela_contextual_site_assistant_leads';
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $table_name, array( 'id' => $lead_id ), array( '%d' ) );

			// Redirect back safely before any HTML output
			wp_safe_redirect( admin_url( 'admin.php?page=shihela-contextual-site-assistant-leads&deleted=1' ) );
			exit;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['page'] ) && 'shihela-contextual-site-assistant-leads' === $_GET['page'] && isset( $_GET['action'] ) && 'export_csv' === $_GET['action'] ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

			// Verify security Nonce
			if ( ! wp_verify_nonce( $nonce, 'shihela_contextual_site_assistant_export_leads' ) ) {
				wp_die( esc_html__( 'Security verification failed.', 'shihela-contextual-site-assistant' ), 403 );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to perform this action.', 'shihela-contextual-site-assistant' ), 403 );
			}

			$this->export_leads_to_csv();
		}
	}

	/**
	 * Export leads to CSV file format.
	 *
	 * @since    1.0.0
	 */
	private function export_leads_to_csv() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'shihela_contextual_site_assistant_leads';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search_query = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$start_date   = isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$end_date     = isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : '';

		$where_clauses = array( '1 = %d' );
		$params        = array( 1 );

		if ( ! empty( $search_query ) ) {
			$search_like = '%' . $wpdb->esc_like( $search_query ) . '%';
			$where_clauses[] = "(lead_name LIKE %s OR lead_email LIKE %s OR lead_message LIKE %s OR page_url LIKE %s)";
			$params[] = $search_like;
			$params[] = $search_like;
			$params[] = $search_like;
			$params[] = $search_like;
		}

		if ( ! empty( $start_date ) ) {
			$where_clauses[] = "lead_date >= %s";
			$params[] = $start_date . ' 00:00:00';
		}

		if ( ! empty( $end_date ) ) {
			$where_clauses[] = "lead_date <= %s";
			$params[] = $end_date . ' 23:59:59';
		}

		// Set headers for download
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=shihela-contextual-site-assistant-leads-' . current_time( 'Y-m-d' ) . '.csv' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$output = fopen( 'php://output', 'w' );

		// UTF-8 BOM for proper Excel compatibility
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputs
		fputs( $output, "\xEF\xBB\xBF" );

		// Column headers (explicit $escape = '' for PHP 8.4–8.6 compatibility)
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
		fputcsv(
			$output,
			array(
				__( 'Date & Time', 'shihela-contextual-site-assistant' ),
				__( 'Name', 'shihela-contextual-site-assistant' ),
				__( 'Contact Info', 'shihela-contextual-site-assistant' ),
				__( 'Inquiry Details', 'shihela-contextual-site-assistant' ),
				__( 'Page Origin', 'shihela-contextual-site-assistant' ),
			),
			',',
			'"',
			''
		);

		// Stream leads in chunked batches of 500 rows to prevent PHP memory exhaustion on large datasets.
		$batch_size = 500;
		$offset     = 0;
		$where_sql  = implode( ' AND ', $where_clauses );

		do {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
			$sql          = "SELECT lead_date, lead_name, lead_email, lead_message, page_url FROM %i WHERE {$where_sql} ORDER BY lead_date DESC LIMIT %d OFFSET %d";
			$batch_params = array_merge( array( $table_name ), $params, array( $batch_size, $offset ) );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
			$leads = $wpdb->get_results( $wpdb->prepare( $sql, $batch_params ) );

			if ( ! empty( $leads ) ) {
				foreach ( $leads as $lead ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv
					fputcsv(
						$output,
						array(
							$this->escape_csv_cell( $lead->lead_date ),
							$this->escape_csv_cell( $lead->lead_name ),
							$this->escape_csv_cell( $lead->lead_email ),
							$this->escape_csv_cell( $lead->lead_message ),
							$this->escape_csv_cell( $lead->page_url ),
						),
						',',
						'"',
						''
					);
				}
			}

			$offset += $batch_size;
		} while ( is_array( $leads ) && count( $leads ) === $batch_size );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $output );
		exit;
	}

	/**
	 * Neutralize spreadsheet formula injection characters in CSV cells.
	 *
	 * @since    1.4.0
	 * @param    mixed $value Cell value.
	 * @return   string Safe CSV cell value.
	 */
	private function escape_csv_cell( $value ) {
		$str = (string) $value;
		if ( '' !== $str && in_array( $str[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			return "'" . $str;
		}
		return $str;
	}

	/**
	 * Render the leads management page.
	 *
	 * @since    1.0.0
	 */
	public function display_leads_page() {
		require_once SHIHELA_CONTEXTUAL_SITE_ASSISTANT_PATH . 'admin/partials/shihela-contextual-site-assistant-leads-display.php';
	}

	/**
	 * Register and sanitize the settings.
	 *
	 * @since    1.0.0
	 */
	public function register_settings() {
		register_setting( $this->plugin_name . '_group', 'shihela_contextual_site_assistant_widget_position', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'bottom-right',
		) );

		register_setting( $this->plugin_name . '_group', 'shihela_contextual_site_assistant_bot_name', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'Shihela AI',
		) );

		register_setting( $this->plugin_name . '_group', 'shihela_contextual_site_assistant_welcome_message', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => 'Hello! I am your AI assistant. How can I help you today?',
		) );

		register_setting( $this->plugin_name . '_group', 'shihela_contextual_site_assistant_system_instructions', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => '',
		) );

		register_setting( $this->plugin_name . '_group', 'shihela_contextual_site_assistant_temperature', array(
			'type'              => 'number',
			'sanitize_callback' => array( $this, 'sanitize_temperature' ),
			'default'           => 0.3,
		) );

		register_setting( $this->plugin_name . '_group', 'shihela_contextual_site_assistant_suggestion_chips', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => "How can you help me?\nWhat services are offered?\nHow to contact human support?",
		) );

		register_setting( $this->plugin_name . '_group', 'shihela_contextual_site_assistant_theme_color', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_hex_color',
			'default'           => '#4f46e5',
		) );

		register_setting( $this->plugin_name . '_group', 'shihela_contextual_site_assistant_webhook_url', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_webhook_url' ),
			'default'           => '',
		) );

		register_setting( $this->plugin_name . '_group', 'shihela_contextual_site_assistant_webhook_secret', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		register_setting( $this->plugin_name . '_group', 'shihela_contextual_site_assistant_access_control', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'public',
		) );

		register_setting( $this->plugin_name . '_group', 'shihela_contextual_site_assistant_daily_limit', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 100,
		) );

		register_setting( $this->plugin_name . '_group', 'shihela_contextual_site_assistant_rate_limit', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 5,
		) );

		register_setting( $this->plugin_name . '_group', 'shihela_contextual_site_assistant_max_length', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 300,
		) );

		register_setting( $this->plugin_name . '_group', 'shihela_contextual_site_assistant_max_history', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 10,
		) );

		register_setting( $this->plugin_name . '_group', 'shihela_contextual_site_assistant_ip_daily_limit', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 20,
		) );

		register_setting( $this->plugin_name . '_group', 'shihela_contextual_site_assistant_whatsapp_number', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		register_setting( $this->plugin_name . '_group', 'shihela_contextual_site_assistant_whatsapp_message', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => __( 'Hello Support, I am viewing the page {page_title} ({page_url}) and need assistance.', 'shihela-contextual-site-assistant' ),
		) );

		register_setting( $this->plugin_name . '_group', 'shihela_contextual_site_assistant_whatsapp_header_btn', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '1',
		) );
	}

	/**
	 * Sanitize webhook URL and restrict to http/https schemes.
	 *
	 * @since    1.4.0
	 * @param    mixed $value Input URL.
	 * @return   string Sanitized webhook URL or empty string.
	 */
	public function sanitize_webhook_url( $value ) {
		$url = esc_url_raw( trim( (string) $value ), array( 'http', 'https' ) );
		if ( empty( $url ) ) {
			return '';
		}
		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
			return '';
		}
		return $url;
	}

	/**
	 * Sanitize temperature input value (clamp between 0.0 and 1.0).
	 *
	 * @since    1.3.0
	 * @param    mixed $value Input temperature.
	 * @return   float Clamped float temperature value.
	 */
	public function sanitize_temperature( $value ) {
		$val = floatval( $value );
		if ( $val < 0.0 ) {
			return 0.0;
		}
		if ( $val > 1.0 ) {
			return 1.0;
		}
		return round( $val, 2 );
	}

	/**
	 * Render the settings page.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		require_once SHIHELA_CONTEXTUAL_SITE_ASSISTANT_PATH . 'admin/partials/shihela-contextual-site-assistant-admin-display.php';
	}
}
