<?php
/**
 * WordPress Privacy Tools (GDPR) integration for Shihela Contextual Site Assistant.
 *
 * Registers privacy policy suggested text, Personal Data Exporter, and Personal Data Eraser
 * for customer leads stored in the plugin's custom database table.
 *
 * @link       https://shihela.com/contextual-site-assistant
 * @since      1.4.0
 *
 * @package    Shihela_Contextual_Site_Assistant
 * @subpackage Shihela_Contextual_Site_Assistant/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles GDPR / WordPress Core Privacy API hooks.
 *
 * @since      1.4.0
 * @package    Shihela_Contextual_Site_Assistant
 * @subpackage Shihela_Contextual_Site_Assistant/includes
 */
class Shihela_Contextual_Site_Assistant_Privacy {

	/**
	 * Register suggested privacy policy content in wp-admin Privacy Policy Guide.
	 *
	 * @since 1.4.0
	 */
	public function add_privacy_policy_content() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = sprintf(
			'<p>%s</p><p>%s</p>',
			esc_html__(
				'When you interact with the AI chat assistant on this site, your messages, recent conversation history, and the public text of the page you are viewing are transmitted to the generative AI provider configured by the site administrator via the WordPress AI Client.',
				'shihela-contextual-site-assistant'
			),
			esc_html__(
				'If you voluntarily provide your contact details (name, email address, phone number, or inquiry details) during a chat, those details along with the origin page URL and session identifier are stored in the site database so our team can respond to your inquiry. If configured by the site administrator, this lead information may also be forwarded via webhook to an external CRM or automation service.',
				'shihela-contextual-site-assistant'
			)
		);

		wp_add_privacy_policy_content(
			__( 'Shihela Contextual Site Assistant', 'shihela-contextual-site-assistant' ),
			wp_kses_post( $content )
		);
	}

	/**
	 * Register Personal Data Exporter.
	 *
	 * @since 1.4.0
	 * @param array $exporters Existing registered exporters.
	 * @return array Modified exporters array.
	 */
	public function register_exporter( $exporters ) {
		$exporters['shihela-contextual-site-assistant'] = array(
			'exporter_friendly_name' => __( 'Shihela Contextual Site Assistant Leads', 'shihela-contextual-site-assistant' ),
			'callback'               => array( $this, 'export_personal_data' ),
		);
		return $exporters;
	}

	/**
	 * Export personal data matching an email address.
	 *
	 * @since 1.4.0
	 * @param string $email_address User email address.
	 * @param int    $page          Pagination page number.
	 * @return array Export payload.
	 */
	public function export_personal_data( $email_address, $page = 1 ) {
		global $wpdb;

		$email_address = sanitize_email( $email_address );
		$per_page      = 100;
		$page          = max( 1, (int) $page );
		$offset        = ( $page - 1 ) * $per_page;
		$table_name    = $wpdb->prefix . 'shihela_contextual_site_assistant_leads';

		if ( empty( $email_address ) ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$leads = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, lead_date, lead_name, lead_email, lead_message, page_url, session_id FROM %i WHERE lead_email = %s ORDER BY id ASC LIMIT %d OFFSET %d',
				$table_name,
				$email_address,
				$per_page,
				$offset
			)
		);

		$export_items = array();
		if ( ! empty( $leads ) ) {
			foreach ( $leads as $lead ) {
				$export_items[] = array(
					'group_id'          => 'shihela-contextual-site-assistant-leads',
					'group_label'       => __( 'Site Assistant Captured Leads', 'shihela-contextual-site-assistant' ),
					'group_description' => __( 'Customer inquiries submitted through the floating AI chat assistant.', 'shihela-contextual-site-assistant' ),
					'item_id'           => 'shihela-lead-' . absint( $lead->id ),
					'data'              => array(
						array(
							'name'  => __( 'Date & Time', 'shihela-contextual-site-assistant' ),
							'value' => $lead->lead_date,
						),
						array(
							'name'  => __( 'Name', 'shihela-contextual-site-assistant' ),
							'value' => $lead->lead_name,
						),
						array(
							'name'  => __( 'Contact Info', 'shihela-contextual-site-assistant' ),
							'value' => $lead->lead_email,
						),
						array(
							'name'  => __( 'Inquiry Details', 'shihela-contextual-site-assistant' ),
							'value' => $lead->lead_message,
						),
						array(
							'name'  => __( 'Origin Page URL', 'shihela-contextual-site-assistant' ),
							'value' => $lead->page_url,
						),
					),
				);
			}
		}

		return array(
			'data' => $export_items,
			'done' => count( $leads ) < $per_page,
		);
	}

	/**
	 * Register Personal Data Eraser.
	 *
	 * @since 1.4.0
	 * @param array $erasers Existing registered erasers.
	 * @return array Modified erasers array.
	 */
	public function register_eraser( $erasers ) {
		$erasers['shihela-contextual-site-assistant'] = array(
			'eraser_friendly_name' => __( 'Shihela Contextual Site Assistant Leads', 'shihela-contextual-site-assistant' ),
			'callback'             => array( $this, 'erase_personal_data' ),
		);
		return $erasers;
	}

	/**
	 * Erase personal data matching an email address.
	 *
	 * @since 1.4.0
	 * @param string $email_address User email address.
	 * @param int    $page          Pagination page number.
	 * @return array Erasure result.
	 */
	public function erase_personal_data( $email_address, $page = 1 ) {
		global $wpdb;

		$email_address = sanitize_email( $email_address );
		$table_name    = $wpdb->prefix . 'shihela_contextual_site_assistant_leads';

		if ( empty( $email_address ) ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete(
			$table_name,
			array( 'lead_email' => $email_address ),
			array( '%s' )
		);

		return array(
			'items_removed'  => (int) $deleted > 0,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}
}
