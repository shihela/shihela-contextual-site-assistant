<?php
/**
 * Render the Leads list page in the WordPress admin panel.
 * Modern SaaS UI Design System.
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

global $wpdb;
$shihela_contextual_site_assistant_table_name = $wpdb->prefix . 'shihela_contextual_site_assistant_leads';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$shihela_contextual_site_assistant_search     = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$shihela_contextual_site_assistant_start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$shihela_contextual_site_assistant_end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$shihela_contextual_site_assistant_paged      = isset( $_GET['paged'] ) ? absint( wp_unslash( $_GET['paged'] ) ) : 1;
if ( $shihela_contextual_site_assistant_paged < 1 ) {
	$shihela_contextual_site_assistant_paged = 1;
}

$shihela_contextual_site_assistant_limit  = 10;
$shihela_contextual_site_assistant_offset = ( $shihela_contextual_site_assistant_paged - 1 ) * $shihela_contextual_site_assistant_limit;

$shihela_contextual_site_assistant_where_clauses = array( '1 = %d' );
$shihela_contextual_site_assistant_params        = array( 1 );

if ( ! empty( $shihela_contextual_site_assistant_search ) ) {
	$shihela_contextual_site_assistant_search_like     = '%' . $wpdb->esc_like( $shihela_contextual_site_assistant_search ) . '%';
	$shihela_contextual_site_assistant_where_clauses[] = "(lead_name LIKE %s OR lead_email LIKE %s OR lead_message LIKE %s OR page_url LIKE %s)";
	$shihela_contextual_site_assistant_params[]        = $shihela_contextual_site_assistant_search_like;
	$shihela_contextual_site_assistant_params[]        = $shihela_contextual_site_assistant_search_like;
	$shihela_contextual_site_assistant_params[]        = $shihela_contextual_site_assistant_search_like;
	$shihela_contextual_site_assistant_params[]        = $shihela_contextual_site_assistant_search_like;
}

if ( ! empty( $shihela_contextual_site_assistant_start_date ) ) {
	$shihela_contextual_site_assistant_where_clauses[] = "lead_date >= %s";
	$shihela_contextual_site_assistant_params[]        = $shihela_contextual_site_assistant_start_date . ' 00:00:00';
}

if ( ! empty( $shihela_contextual_site_assistant_end_date ) ) {
	$shihela_contextual_site_assistant_where_clauses[] = "lead_date <= %s";
	$shihela_contextual_site_assistant_params[]        = $shihela_contextual_site_assistant_end_date . ' 23:59:59';
}

$shihela_contextual_site_assistant_where_sql = " WHERE " . implode( " AND ", $shihela_contextual_site_assistant_where_clauses );

// Count total matching leads for pagination
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
$shihela_contextual_site_assistant_total_sql    = "SELECT COUNT(*) FROM %i" . $shihela_contextual_site_assistant_where_sql;
$shihela_contextual_site_assistant_total_params = array_merge( array( $shihela_contextual_site_assistant_table_name ), $shihela_contextual_site_assistant_params );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
$shihela_contextual_site_assistant_total_leads  = (int) $wpdb->get_var( $wpdb->prepare( $shihela_contextual_site_assistant_total_sql, $shihela_contextual_site_assistant_total_params ) );
$shihela_contextual_site_assistant_total_pages  = ceil( $shihela_contextual_site_assistant_total_leads / $shihela_contextual_site_assistant_limit );

// Get the paginated results
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
$shihela_contextual_site_assistant_query_sql    = "SELECT * FROM %i" . $shihela_contextual_site_assistant_where_sql . " ORDER BY lead_date DESC LIMIT %d OFFSET %d";
$shihela_contextual_site_assistant_query_params = array_merge(
	array( $shihela_contextual_site_assistant_table_name ),
	$shihela_contextual_site_assistant_params,
	array( $shihela_contextual_site_assistant_limit, $shihela_contextual_site_assistant_offset )
);

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
$shihela_contextual_site_assistant_leads = $wpdb->get_results( $wpdb->prepare( $shihela_contextual_site_assistant_query_sql, $shihela_contextual_site_assistant_query_params ) );

// Build CSV Export URL
$shihela_contextual_site_assistant_export_url = wp_nonce_url(
	admin_url( 'admin.php?page=shihela-contextual-site-assistant-leads&action=export_csv' . 
		( ! empty( $shihela_contextual_site_assistant_search ) ? '&s=' . urlencode( $shihela_contextual_site_assistant_search ) : '' ) .
		( ! empty( $shihela_contextual_site_assistant_start_date ) ? '&start_date=' . urlencode( $shihela_contextual_site_assistant_start_date ) : '' ) .
		( ! empty( $shihela_contextual_site_assistant_end_date ) ? '&end_date=' . urlencode( $shihela_contextual_site_assistant_end_date ) : '' )
	),
	'shihela_contextual_site_assistant_export_leads'
);
?>

<div class="wrap shihela-contextual-site-assistant-admin-wrap">
	<!-- Header Banner -->
	<header class="shihela-contextual-site-assistant-admin-header">
		<div class="shihela-header-left">
			<div class="shihela-brand-badge">
				<span class="dashicons dashicons-id-alt"></span>
			</div>
			<div>
				<h1><?php esc_html_e( 'Captured Customer Leads', 'shihela-contextual-site-assistant' ); ?></h1>
				<p class="shihela-contextual-site-assistant-tagline"><?php esc_html_e( 'View and manage business inquiries captured by the chatbot widget.', 'shihela-contextual-site-assistant' ); ?></p>
			</div>
		</div>
		<div class="shihela-header-actions">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=shihela-contextual-site-assistant' ) ); ?>" class="button button-secondary shihela-leads-btn">
				<span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e( 'Back to Settings', 'shihela-contextual-site-assistant' ); ?>
			</a>
		</div>
	</header>

	<?php
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['deleted'] ) && '1' === $_GET['deleted'] ) :
	?>
		<div class="notice notice-success is-dismissible shihela-leads-notice">
			<p><?php esc_html_e( 'Lead deleted successfully.', 'shihela-contextual-site-assistant' ); ?></p>
		</div>
	<?php endif; ?>

	<!-- Filter Card -->
	<div class="shihela-contextual-site-assistant-card shihela-leads-filter-card">
		<div class="shihela-contextual-site-assistant-card-body shihela-leads-filter-body">
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="shihela-leads-filter-form">
				<input type="hidden" name="page" value="shihela-contextual-site-assistant-leads">

				<div class="shihela-contextual-site-assistant-filter-field shihela-filter-grow">
					<label for="shihela_contextual_site_assistant_search_input"><?php esc_html_e( 'Search Keywords', 'shihela-contextual-site-assistant' ); ?></label>
					<input type="text" name="s" id="shihela_contextual_site_assistant_search_input" value="<?php echo esc_attr( $shihela_contextual_site_assistant_search ); ?>" placeholder="<?php esc_attr_e( 'Search Name, Email, Inquiry...', 'shihela-contextual-site-assistant' ); ?>" class="shihela-contextual-site-assistant-input">
				</div>

				<div class="shihela-contextual-site-assistant-filter-field shihela-filter-date">
					<label for="shihela_contextual_site_assistant_start_date"><?php esc_html_e( 'Start Date', 'shihela-contextual-site-assistant' ); ?></label>
					<input type="date" name="start_date" id="shihela_contextual_site_assistant_start_date" value="<?php echo esc_attr( $shihela_contextual_site_assistant_start_date ); ?>" class="shihela-contextual-site-assistant-input">
				</div>

				<div class="shihela-contextual-site-assistant-filter-field shihela-filter-date">
					<label for="shihela_contextual_site_assistant_end_date"><?php esc_html_e( 'End Date', 'shihela-contextual-site-assistant' ); ?></label>
					<input type="date" name="end_date" id="shihela_contextual_site_assistant_end_date" value="<?php echo esc_attr( $shihela_contextual_site_assistant_end_date ); ?>" class="shihela-contextual-site-assistant-input">
				</div>

				<div class="shihela-contextual-site-assistant-filter-actions">
					<button type="submit" class="button button-primary shihela-filter-submit-btn"><?php esc_html_e( 'Apply Filters', 'shihela-contextual-site-assistant' ); ?></button>
					<?php if ( ! empty( $shihela_contextual_site_assistant_search ) || ! empty( $shihela_contextual_site_assistant_start_date ) || ! empty( $shihela_contextual_site_assistant_end_date ) ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=shihela-contextual-site-assistant-leads' ) ); ?>" class="button button-secondary shihela-filter-clear-btn"><?php esc_html_e( 'Clear', 'shihela-contextual-site-assistant' ); ?></a>
					<?php endif; ?>
				</div>
			</form>
		</div>
	</div>

	<!-- Leads Table Card -->
	<div class="shihela-contextual-site-assistant-card">
		<div class="shihela-card-header shihela-leads-table-header">
			<h2>
				<span class="dashicons dashicons-list-view"></span>
				<?php
				/* translators: %d: count of captured leads */
				printf( esc_html( _n( 'Captured Inquiry (%d)', 'Captured Inquiries (%d)', $shihela_contextual_site_assistant_total_leads, 'shihela-contextual-site-assistant' ) ), esc_html( $shihela_contextual_site_assistant_total_leads ) );
				?>
			</h2>
			<?php if ( ! empty( $shihela_contextual_site_assistant_leads ) ) : ?>
				<a href="<?php echo esc_url( $shihela_contextual_site_assistant_export_url ); ?>" class="button button-secondary shihela-leads-btn shihela-export-csv-btn">
					<span class="dashicons dashicons-download"></span>
					<?php esc_html_e( 'Export CSV', 'shihela-contextual-site-assistant' ); ?>
				</a>
			<?php endif; ?>
		</div>
		<div class="shihela-contextual-site-assistant-card-body shihela-leads-table-body">
			<?php if ( empty( $shihela_contextual_site_assistant_leads ) ) : ?>
				<div class="shihela-leads-empty">
					<span class="dashicons dashicons-info"></span>
					<p><?php esc_html_e( 'No leads found. The chatbot will capture leads when visitors submit their contact details.', 'shihela-contextual-site-assistant' ); ?></p>
				</div>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped posts shihela-contextual-site-assistant-leads-table">
					<thead>
						<tr>
							<th scope="col" class="manage-column column-date shihela-col-date"><?php esc_html_e( 'Date & Time', 'shihela-contextual-site-assistant' ); ?></th>
							<th scope="col" class="manage-column column-title shihela-col-name"><?php esc_html_e( 'Name', 'shihela-contextual-site-assistant' ); ?></th>
							<th scope="col" class="manage-column column-categories shihela-col-contact"><?php esc_html_e( 'Contact Info', 'shihela-contextual-site-assistant' ); ?></th>
							<th scope="col" class="manage-column column-description shihela-col-details"><?php esc_html_e( 'Inquiry Details', 'shihela-contextual-site-assistant' ); ?></th>
							<th scope="col" class="manage-column column-tags shihela-col-origin"><?php esc_html_e( 'Page Origin', 'shihela-contextual-site-assistant' ); ?></th>
							<th scope="col" class="manage-column column-action shihela-col-action"><?php esc_html_e( 'Action', 'shihela-contextual-site-assistant' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						$shihela_dt_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
						$shihela_wp_tz     = wp_timezone();
						foreach ( $shihela_contextual_site_assistant_leads as $shihela_contextual_site_assistant_lead ) :
							$shihela_lead_dt   = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $shihela_contextual_site_assistant_lead->lead_date, $shihela_wp_tz );
							$shihela_lead_date = $shihela_lead_dt
								? wp_date( $shihela_dt_format, $shihela_lead_dt->getTimestamp(), $shihela_wp_tz )
								: $shihela_contextual_site_assistant_lead->lead_date;
						?>
							<tr>
								<td class="shihela-cell-date">
									<strong><?php echo esc_html( $shihela_lead_date ); ?></strong>
								</td>
								<td class="shihela-cell-name">
									<?php echo esc_html( $shihela_contextual_site_assistant_lead->lead_name ); ?>
								</td>
								<td class="shihela-cell-contact">
									<?php
									$shihela_contact_href = strpos( $shihela_contextual_site_assistant_lead->lead_email, '@' ) !== false
										? 'mailto:' . sanitize_email( $shihela_contextual_site_assistant_lead->lead_email )
										: 'tel:' . preg_replace( '/[^0-9+\-\s()]/', '', $shihela_contextual_site_assistant_lead->lead_email );
									?>
									<a href="<?php echo esc_url( $shihela_contact_href, array( 'mailto', 'tel' ) ); ?>" class="shihela-contact-link">
										<?php echo esc_html( $shihela_contextual_site_assistant_lead->lead_email ); ?>
									</a>
								</td>
								<td class="shihela-cell-details">
									<?php echo esc_html( $shihela_contextual_site_assistant_lead->lead_message ); ?>
								</td>
								<td class="shihela-cell-origin">
									<?php if ( ! empty( $shihela_contextual_site_assistant_lead->page_url ) ) : ?>
										<a href="<?php echo esc_url( $shihela_contextual_site_assistant_lead->page_url ); ?>" target="_blank" rel="noopener noreferrer" class="button button-small shihela-origin-btn">
											<span class="dashicons dashicons-external"></span>
											<?php esc_html_e( 'View Page', 'shihela-contextual-site-assistant' ); ?>
										</a>
									<?php else : ?>
										<span class="description shihela-origin-unknown"><?php esc_html_e( 'Unknown', 'shihela-contextual-site-assistant' ); ?></span>
									<?php endif; ?>
								</td>
								<td class="shihela-cell-action">
									<?php
									$shihela_contextual_site_assistant_delete_url = wp_nonce_url(
										admin_url( 'admin.php?page=shihela-contextual-site-assistant-leads&action=delete&id=' . absint( $shihela_contextual_site_assistant_lead->id ) ),
										'delete_lead_' . absint( $shihela_contextual_site_assistant_lead->id )
									);
									?>
									<a href="<?php echo esc_url( $shihela_contextual_site_assistant_delete_url ); ?>" class="button button-link-delete shihela-delete-lead-btn" aria-label="<?php esc_attr_e( 'Delete this lead', 'shihela-contextual-site-assistant' ); ?>">
										<span class="dashicons dashicons-trash"></span>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<?php if ( $shihela_contextual_site_assistant_total_pages > 1 ) : ?>
					<div class="shihela-contextual-site-assistant-pagination">
						<?php
						echo wp_kses_post( paginate_links( array(
							'base'      => add_query_arg( 'paged', '%#%' ),
							'format'    => '',
							'current'   => $shihela_contextual_site_assistant_paged,
							'total'     => $shihela_contextual_site_assistant_total_pages,
							'prev_text' => __( '&laquo; Previous', 'shihela-contextual-site-assistant' ),
							'next_text' => __( 'Next &raquo;', 'shihela-contextual-site-assistant' ),
							'type'      => 'plain',
						) ) );
						?>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
</div>
