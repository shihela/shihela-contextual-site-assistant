<?php
/**
 * Fired during plugin activation
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
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Shihela_Contextual_Site_Assistant
 * @subpackage Shihela_Contextual_Site_Assistant/includes
 * @author     shihela
 */
class Shihela_Contextual_Site_Assistant_Activator {

	/**
	 * Database schema version.
	 *
	 * @since    1.4.0
	 * @var      string
	 */
	const DB_VERSION = '1.1.0';

	/**
	 * Set up default options and database tables upon activation (supports Multisite).
	 *
	 * @since    1.0.0
	 * @param    bool $network_wide Whether network-activated on Multisite.
	 */
	public static function activate( $network_wide = false ) {
		if ( is_multisite() && $network_wide ) {
			$site_ids = get_sites( array( 'fields' => 'ids' ) );
			foreach ( $site_ids as $site_id ) {
				switch_to_blog( (int) $site_id );
				self::activate_single_site();
				restore_current_blog();
			}
			return;
		}

		self::activate_single_site();
	}

	/**
	 * Initialize defaults and tables for a single site.
	 *
	 * @since    1.4.0
	 */
	public static function activate_single_site() {
		$default_settings = array(
			'shihela_contextual_site_assistant_widget_position'     => 'bottom-right',
			'shihela_contextual_site_assistant_bot_name'            => 'Shihela AI',
			'shihela_contextual_site_assistant_welcome_message'     => __( 'Hello! I am your AI assistant. How can I help you today?', 'shihela-contextual-site-assistant' ),
			'shihela_contextual_site_assistant_system_instructions' => 'You are a helpful, professional customer support AI assistant for this website. Answer the user\'s questions using the provided page context and global context. If you cannot find the answer in the context, politely suggest checking other pages or contacting support.',
			'shihela_contextual_site_assistant_theme_color'         => '#4f46e5', // Sleek indigo
			'shihela_contextual_site_assistant_whatsapp_number'     => '',
			'shihela_contextual_site_assistant_whatsapp_message'    => __( 'Hello Support, I am viewing the page {page_title} ({page_url}) and need assistance.', 'shihela-contextual-site-assistant' ),
			'shihela_contextual_site_assistant_whatsapp_header_btn' => '1',
		);

		foreach ( $default_settings as $key => $default_value ) {
			if ( get_option( $key ) === false ) {
				update_option( $key, $default_value );
			}
		}

		self::create_or_upgrade_tables();
	}

	/**
	 * Run database schema upgrades on plugin update when needed.
	 *
	 * @since    1.4.0
	 */
	public static function maybe_upgrade() {
		$installed_version = get_option( 'shihela_contextual_site_assistant_db_version', '' );
		if ( self::DB_VERSION !== $installed_version ) {
			self::create_or_upgrade_tables();
		}
	}

	/**
	 * Create or migrate the leads database table with MySQL 8+ strict mode compatibility and indexes.
	 *
	 * @since    1.4.0
	 */
	private static function create_or_upgrade_tables() {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'shihela_contextual_site_assistant_leads';
		$charset_collate = $wpdb->get_charset_collate();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			lead_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			lead_name varchar(100) NOT NULL,
			lead_email varchar(100) NOT NULL,
			lead_message text NOT NULL,
			page_url varchar(255) DEFAULT '' NOT NULL,
			session_id varchar(100) DEFAULT '' NOT NULL,
			PRIMARY KEY  (id),
			KEY idx_lead_date (lead_date),
			KEY idx_session_id (session_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		dbDelta( $sql );

		update_option( 'shihela_contextual_site_assistant_db_version', self::DB_VERSION, false );
	}
}
