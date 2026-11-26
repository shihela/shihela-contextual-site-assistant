<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Cleans up all plugin options, transients, and the custom leads database table.
 *
 * @link       https://shihela.com/contextual-site-assistant
 * @since      1.4.0
 * @package    Shihela_Contextual_Site_Assistant
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove all plugin data for the current site.
 */
function shihela_contextual_site_assistant_uninstall_site() {
	$option_keys = array(
		'shihela_contextual_site_assistant_widget_position',
		'shihela_contextual_site_assistant_bot_name',
		'shihela_contextual_site_assistant_welcome_message',
		'shihela_contextual_site_assistant_system_instructions',
		'shihela_contextual_site_assistant_temperature',
		'shihela_contextual_site_assistant_suggestion_chips',
		'shihela_contextual_site_assistant_theme_color',
		'shihela_contextual_site_assistant_webhook_url',
		'shihela_contextual_site_assistant_webhook_secret',
		'shihela_contextual_site_assistant_access_control',
		'shihela_contextual_site_assistant_daily_limit',
		'shihela_contextual_site_assistant_rate_limit',
		'shihela_contextual_site_assistant_max_length',
		'shihela_contextual_site_assistant_max_history',
		'shihela_contextual_site_assistant_ip_daily_limit',
		'shihela_contextual_site_assistant_whatsapp_number',
		'shihela_contextual_site_assistant_whatsapp_message',
		'shihela_contextual_site_assistant_whatsapp_header_btn',
		'shihela_contextual_site_assistant_daily_data',
		'shihela_contextual_site_assistant_db_version',
	);

	foreach ( $option_keys as $option_key ) {
		delete_option( $option_key );
	}

	global $wpdb;

	// Remove plugin rate-limit and IP daily-limit transients stored in wp_options.
	$like_rate_val     = $wpdb->esc_like( '_transient_shihela_contextual_site_assistant_rate_' ) . '%';
	$like_rate_timeout = $wpdb->esc_like( '_transient_timeout_shihela_contextual_site_assistant_rate_' ) . '%';
	$like_ip_val       = $wpdb->esc_like( '_transient_shihela_daily_ip_' ) . '%';
	$like_ip_timeout   = $wpdb->esc_like( '_transient_timeout_shihela_daily_ip_' ) . '%';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s",
			$like_rate_val,
			$like_rate_timeout,
			$like_ip_val,
			$like_ip_timeout
		)
	);

	// Drop the custom leads database table.
	$table_name = $wpdb->prefix . 'shihela_contextual_site_assistant_leads';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table_name ) );
}

if ( is_multisite() ) {
	$shihela_site_ids = get_sites( array( 'fields' => 'ids' ) );
	foreach ( $shihela_site_ids as $shihela_site_id ) {
		switch_to_blog( (int) $shihela_site_id );
		shihela_contextual_site_assistant_uninstall_site();
		restore_current_blog();
	}
} else {
	shihela_contextual_site_assistant_uninstall_site();
}
