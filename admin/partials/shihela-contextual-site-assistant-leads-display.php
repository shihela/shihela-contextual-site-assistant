<?php
/**
 * Render the Leads list page in the WordPress admin panel.
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
$shihela_contextual_site_assistant_table_name = esc_sql( $wpdb->prefix . 'shihela_contextual_site_assistant_leads' );
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
$shihela_contextual_site_assistant_total_sql   = "SELECT COUNT(*) FROM " . esc_sql( $shihela_contextual_site_assistant_table_name ) . $shihela_contextual_site_assistant_where_sql;
// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
$shihela_contextual_site_assistant_total_sql   = $wpdb->prepare( $shihela_contextual_site_assistant_total_sql, $shihela_contextual_site_assistant_params );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
$shihela_contextual_site_assistant_total_leads = (int) $wpdb->get_var( $shihela_contextual_site_assistant_total_sql );
$shihela_contextual_site_assistant_total_pages = ceil( $shihela_contextual_site_assistant_total_leads / $shihela_contextual_site_assistant_limit );

// Get the paginated results
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
$shihela_contextual_site_assistant_query_sql    = "SELECT * FROM " . esc_sql( $shihela_contextual_site_assistant_table_name ) . $shihela_contextual_site_assistant_where_sql . " ORDER BY lead_date DESC LIMIT %d OFFSET %d";
$shihela_contextual_site_assistant_query_params = array_merge( $shihela_contextual_site_assistant_params, array( $shihela_contextual_site_assistant_limit, $shihela_contextual_site_assistant_offset ) );
// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
$shihela_contextual_site_assistant_query_sql    = $wpdb->prepare( $shihela_contextual_site_assistant_query_sql, $shihela_contextual_site_assistant_query_params );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter
$shihela_contextual_site_assistant_leads = $wpdb->get_results( $shihela_contextual_site_assistant_query_sql );

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
	<header class="shihela-contextual-site-assistant-admin-header">
		<div class="shihela-contextual-site-assistant-logo-area">
			<span class="shihela-contextual-site-assistant-dashicon dashicons dashicons-id-alt"></span>
			<h1><?php esc_html_e( 'Shihela Contextual Site Assistant Captured Leads', 'shihela-contextual-site-assistant' ); ?></h1>
		</div>
		<p class="shihela-contextual-site-assistant-tagline"><?php esc_html_e( 'View and manage business inquiries captured by the chatbot widget.', 'shihela-contextual-site-assistant' ); ?></p>
	</header>

	<?php
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['deleted'] ) && '1' === $_GET['deleted'] ) :
	?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Lead deleted successfully.', 'shihela-contextual-site-assistant' ); ?></p>
		</div>
	<?php endif; ?>

	<!-- Filter bar -->
	<div class="shihela-contextual-site-assistant-filter-bar" style="margin-bottom: 20px; background: #fff; padding: 15px 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; margin: 0;">
			<input type="hidden" name="page" value="shihela-contextual-site-assistant-leads">

			<div class="shihela-contextual-site-assistant-filter-field" style="display: flex; flex-direction: column; gap: 6px; min-width: 200px; flex: 1;">
				<label for="shihela_contextual_site_assistant_search_input" style="font-weight: 600; font-size: 13px; color: #1e293b;"><?php esc_html_e( 'Search Keywords', 'shihela-contextual-site-assistant' ); ?></label>
				<input type="text" name="s" id="shihela_contextual_site_assistant_search_input" value="<?php echo esc_attr( $shihela_contextual_site_assistant_search ); ?>" placeholder="<?php esc_attr_e( 'Search Name, Email, Inquiry...', 'shihela-contextual-site-assistant' ); ?>" style="height: 30px; border: 1px solid #cbd5e1; border-radius: 4px; padding: 0 12px; width: 100%;">
			</div>

			<div class="shihela-contextual-site-assistant-filter-field" style="display: flex; flex-direction: column; gap: 6px; width: 150px;">
				<label for="shihela_contextual_site_assistant_start_date" style="font-weight: 600; font-size: 13px; color: #1e293b;"><?php esc_html_e( 'Start Date', 'shihela-contextual-site-assistant' ); ?></label>
				<input type="date" name="start_date" id="shihela_contextual_site_assistant_start_date" value="<?php echo esc_attr( $shihela_contextual_site_assistant_start_date ); ?>" style="height: 30px; border: 1px solid #cbd5e1; border-radius: 4px; padding: 0 12px; width: 100%;">
			</div>

			<div class="shihela-contextual-site-assistant-filter-field" style="display: flex; flex-direction: column; gap: 6px; width: 150px;">
				<label for="shihela_contextual_site_assistant_end_date" style="font-weight: 600; font-size: 13px; color: #1e293b;"><?php esc_html_e( 'End Date', 'shihela-contextual-site-assistant' ); ?></label>
				<input type="date" name="end_date" id="shihela_contextual_site_assistant_end_date" value="<?php echo esc_attr( $shihela_contextual_site_assistant_end_date ); ?>" style="height: 30px; border: 1px solid #cbd5e1; border-radius: 4px; padding: 0 12px; width: 100%;">
			</div>

			<div class="shihela-contextual-site-assistant-filter-actions" style="display: flex; gap: 10px;">
				<button type="submit" class="button button-primary" style="height: 30px; line-height: 28px; padding: 0 20px; font-weight: 600; font-size: 13px;"><?php esc_html_e( 'Apply Filters', 'shihela-contextual-site-assistant' ); ?></button>
				<?php if ( ! empty( $shihela_contextual_site_assistant_search ) || ! empty( $shihela_contextual_site_assistant_start_date ) || ! empty( $shihela_contextual_site_assistant_end_date ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=shihela-contextual-site-assistant-leads' ) ); ?>" class="button button-secondary" style="height: 30px; line-height: 28px; padding: 0 15px; font-size: 13px;"><?php esc_html_e( 'Clear', 'shihela-contextual-site-assistant' ); ?></a>
				<?php endif; ?>
			</div>
		</form>
	</div>

	<div class="shihela-contextual-site-assistant-admin-container" style="display: block;">
		<div class="shihela-contextual-site-assistant-card">
			<h2 class="shihela-contextual-site-assistant-card-title" style="display: flex; justify-content: space-between; align-items: center; width: 100%; box-sizing: border-box; padding-right: 20px;">
				<span style="display: flex; align-items: center; gap: 8px;">
					<span class="dashicons dashicons-list-view"></span>
					<?php
					/* translators: %d: count of captured leads */
					printf( esc_html( _n( 'Captured Inquiry (%d)', 'Captured Inquiries (%d)', $shihela_contextual_site_assistant_total_leads, 'shihela-contextual-site-assistant' ) ), esc_html( $shihela_contextual_site_assistant_total_leads ) );
					?>
				</span>
				<?php if ( ! empty( $shihela_contextual_site_assistant_leads ) ) : ?>
					<a href="<?php echo esc_url( $shihela_contextual_site_assistant_export_url ); ?>" class="button button-secondary shihela-contextual-site-assistant-export-btn" style="font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; height: 32px;">
						<span class="dashicons dashicons-download" style="font-size: 16px; width: 16px; height: 16px; margin-top: 4px;"></span>
						<?php esc_html_e( 'Export CSV', 'shihela-contextual-site-assistant' ); ?>
					</a>
				<?php endif; ?>
			</h2>
			<div class="shihela-contextual-site-assistant-card-body" style="padding: 0;">
				<?php if ( empty( $shihela_contextual_site_assistant_leads ) ) : ?>
					<div style="padding: 40px; text-align: center; color: #64748b;">
						<span class="dashicons dashicons-info" style="font-size: 48px; width: 48px; height: 48px; margin-bottom: 10px; color: #cbd5e1;"></span>
						<p style="font-size: 15px; margin: 0;"><?php esc_html_e( 'No leads found. The chatbot will capture leads when visitors submit their contact details.', 'shihela-contextual-site-assistant' ); ?></p>
					</div>
				<?php else : ?>
					<table class="wp-list-table widefat fixed striped posts shihela-contextual-site-assistant-leads-table" style="border: none; box-shadow: none;">
						<thead>
							<tr>
								<th scope="col" class="manage-column column-date" style="width: 15%; padding-left: 20px; font-weight: 600;"><?php esc_html_e( 'Date & Time', 'shihela-contextual-site-assistant' ); ?></th>
								<th scope="col" class="manage-column column-title" style="width: 20%; font-weight: 600;"><?php esc_html_e( 'Name', 'shihela-contextual-site-assistant' ); ?></th>
								<th scope="col" class="manage-column column-categories" style="width: 20%; font-weight: 600;"><?php esc_html_e( 'Contact Info', 'shihela-contextual-site-assistant' ); ?></th>
								<th scope="col" class="manage-column column-description" style="width: 30%; font-weight: 600;"><?php esc_html_e( 'Inquiry Details', 'shihela-contextual-site-assistant' ); ?></th>
								<th scope="col" class="manage-column column-tags" style="width: 15%; font-weight: 600;"><?php esc_html_e( 'Page Origin', 'shihela-contextual-site-assistant' ); ?></th>
								<th scope="col" class="manage-column column-action" style="width: 80px; text-align: center; font-weight: 600;"><?php esc_html_e( 'Action', 'shihela-contextual-site-assistant' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $shihela_contextual_site_assistant_leads as $shihela_contextual_site_assistant_lead ) : ?>
								<tr>
									<td style="padding-left: 20px; vertical-align: middle;">
										<strong><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $shihela_contextual_site_assistant_lead->lead_date ) ) ); ?></strong>
									</td>
									<td style="vertical-align: middle; font-weight: 500; color: #1e293b;">
										<?php echo esc_html( $shihela_contextual_site_assistant_lead->lead_name ); ?>
									</td>
									<td style="vertical-align: middle;">
										<a href="<?php echo esc_attr( strpos( $shihela_contextual_site_assistant_lead->lead_email, '@' ) !== false ? 'mailto:' . $shihela_contextual_site_assistant_lead->lead_email : 'tel:' . $shihela_contextual_site_assistant_lead->lead_email ); ?>" style="text-decoration: none; font-weight: 500;">
											<?php echo esc_html( $shihela_contextual_site_assistant_lead->lead_email ); ?>
										</a>
									</td>
									<td style="vertical-align: middle; line-height: 1.5; color: #475569;">
										<?php echo esc_html( $shihela_contextual_site_assistant_lead->lead_message ); ?>
									</td>
									<td style="vertical-align: middle;">
										<?php if ( ! empty( $shihela_contextual_site_assistant_lead->page_url ) ) : ?>
											<a href="<?php echo esc_url( $shihela_contextual_site_assistant_lead->page_url ); ?>" target="_blank" class="button button-small" style="font-size: 11px; padding: 0 8px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px;">
												<span class="dashicons dashicons-external" style="font-size: 12px; width: 12px; height: 12px; margin-top: 4px;"></span>
												<?php esc_html_e( 'View Page', 'shihela-contextual-site-assistant' ); ?>
											</a>
										<?php else : ?>
											<span class="description" style="font-style: italic;"><?php esc_html_e( 'Unknown', 'shihela-contextual-site-assistant' ); ?></span>
										<?php endif; ?>
									</td>
									<td style="text-align: center; vertical-align: middle;">
										<?php
										$shihela_contextual_site_assistant_delete_url = wp_nonce_url(
											admin_url( 'admin.php?page=shihela-contextual-site-assistant-leads&action=delete&id=' . $shihela_contextual_site_assistant_lead->id ),
											'delete_lead_' . $shihela_contextual_site_assistant_lead->id
										);
										?>
										<a href="<?php echo esc_url( $shihela_contextual_site_assistant_delete_url ); ?>" class="button button-link-delete" style="color: #b91c1c; text-decoration: none;" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this lead?', 'shihela-contextual-site-assistant' ); ?>');" aria-label="<?php esc_attr_e( 'Delete this lead', 'shihela-contextual-site-assistant' ); ?>">
											<span class="dashicons dashicons-trash" style="margin-top: 3px;"></span>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<?php if ( $shihela_contextual_site_assistant_total_pages > 1 ) : ?>
						<div class="shihela-contextual-site-assistant-pagination" style="padding: 20px; display: flex; justify-content: flex-end; border-top: 1px solid #e2e8f0; background: #fff; gap: 5px;">
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
</div>
