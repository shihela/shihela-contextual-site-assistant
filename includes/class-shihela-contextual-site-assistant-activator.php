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
	 * Set up default options for the plugin upon activation.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		$default_settings = array(
			'shihela_contextual_site_assistant_widget_position'     => 'bottom-right',
			'shihela_contextual_site_assistant_bot_name'            => 'Shihela AI',
			'shihela_contextual_site_assistant_welcome_message'     => 'Hello! I am your AI assistant. How can I help you today?',
			'shihela_contextual_site_assistant_system_instructions' => 'You are a helpful, professional customer support AI assistant for this website. Answer the user\'s questions using the provided page context and global context. If you cannot find the answer in the context, politely suggest checking other pages or contacting support, but still answer to the best of your ability with general knowledge.',
			'shihela_contextual_site_assistant_theme_color'         => '#4f46e5', // Sleek indigo
		);

		foreach ( $default_settings as $key => $default_value ) {
			if ( get_option( $key ) === false ) {
				update_option( $key, $default_value );
			}
		}

		// Create leads database table
		global $wpdb;
		$table_name = $wpdb->prefix . 'shihela_contextual_site_assistant_leads';
		$charset_collate = $wpdb->get_charset_collate();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = "CREATE TABLE $table_name (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			lead_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			lead_name varchar(100) NOT NULL,
			lead_email varchar(100) NOT NULL,
			lead_message text NOT NULL,
			page_url varchar(255) DEFAULT '' NOT NULL,
			session_id varchar(100) DEFAULT '' NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		dbDelta( $sql );
	}
}
