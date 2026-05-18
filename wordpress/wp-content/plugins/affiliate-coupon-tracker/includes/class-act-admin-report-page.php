<?php
/**
 * Admin UI for affiliate monthly reports.
 *
 * @package AffiliateCouponTracker
 */

defined( 'ABSPATH' ) || exit;

class ACT_Admin_Report_Page {
	/**
	 * @var ACT_Order_Report_Repository
	 */
	private $repository;

	/**
	 * @param ACT_Order_Report_Repository $repository Report repository.
	 */
	public function __construct( ACT_Order_Report_Repository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'add_menu' ), 99 );
	}

	/**
	 * Add report page under WooCommerce menu.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Affiliate Coupon Report', 'affiliate-coupon-tracker' ),
			__( 'Affiliate Coupons', 'affiliate-coupon-tracker' ),
			'manage_woocommerce',
			'affiliate-coupon-tracker',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render report page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'affiliate-coupon-tracker' ) );
		}

		$selected_month     = isset( $_GET['report_month'] ) ? sanitize_text_field( wp_unslash( $_GET['report_month'] ) ) : wp_date( 'Y-m' );
		$selected_affiliate = isset( $_GET['affiliate_key'] ) ? sanitize_text_field( wp_unslash( $_GET['affiliate_key'] ) ) : '';

		$affiliates = $this->repository->get_affiliate_options();
		$report     = $this->repository->build_monthly_report( $selected_month, $selected_affiliate );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Affiliate Coupon Report', 'affiliate-coupon-tracker' ); ?></h1>
			<p><?php esc_html_e( 'Orders appear when an affiliate coupon is used, when a logged-in customer was linked at checkout (referral signup, coupon, admin), or both. Affiliate links frozen on each order at purchase are not altered if the customer profile changes later.', 'affiliate-coupon-tracker' ); ?></p>

			<form method="get" style="margin: 16px 0;">
				<input type="hidden" name="page" value="affiliate-coupon-tracker" />
				<label for="act-report-month" style="margin-right: 8px;">
					<strong><?php esc_html_e( 'Month', 'affiliate-coupon-tracker' ); ?></strong>
				</label>
				<input id="act-report-month" type="month" name="report_month" value="<?php echo esc_attr( $report['month'] ); ?>" />

				<label for="act-affiliate-key" style="margin: 0 8px 0 16px;">
					<strong><?php esc_html_e( 'Affiliate', 'affiliate-coupon-tracker' ); ?></strong>
				</label>
				<select id="act-affiliate-key" name="affiliate_key">
					<option value=""><?php esc_html_e( 'All Affiliates', 'affiliate-coupon-tracker' ); ?></option>
					<?php foreach ( $affiliates as $affiliate_key => $affiliate_label ) : ?>
						<option value="<?php echo esc_attr( $affiliate_key ); ?>" <?php selected( $selected_affiliate, $affiliate_key ); ?>>
							<?php echo esc_html( $affiliate_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<button type="submit" class="button button-secondary" style="margin-left: 16px;">
					<?php esc_html_e( 'Run Report', 'affiliate-coupon-tracker' ); ?>
				</button>
			</form>

			<p>
				<strong><?php esc_html_e( 'Orders', 'affiliate-coupon-tracker' ); ?>:</strong>
				<?php echo esc_html( (string) $report['count'] ); ?>
			</p>

			<table class="widefat striped">
				<thead>
				<tr>
					<th><?php esc_html_e( 'Order', 'affiliate-coupon-tracker' ); ?></th>
					<th><?php esc_html_e( 'Date', 'affiliate-coupon-tracker' ); ?></th>
					<th><?php esc_html_e( 'Customer', 'affiliate-coupon-tracker' ); ?></th>
					<th><?php esc_html_e( 'Coupon(s)', 'affiliate-coupon-tracker' ); ?></th>
					<th><?php esc_html_e( 'Affiliate', 'affiliate-coupon-tracker' ); ?></th>
					<th><?php esc_html_e( 'Items', 'affiliate-coupon-tracker' ); ?></th>
					<th><?php esc_html_e( 'Shipping', 'affiliate-coupon-tracker' ); ?></th>
					<th><?php esc_html_e( 'Tax', 'affiliate-coupon-tracker' ); ?></th>
					<th><?php esc_html_e( 'Order Total', 'affiliate-coupon-tracker' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php if ( empty( $report['rows'] ) ) : ?>
					<tr>
						<td colspan="9"><?php esc_html_e( 'No affiliate coupon orders found for this selection.', 'affiliate-coupon-tracker' ); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ( $report['rows'] as $row ) : ?>
						<tr>
							<td>
								<a href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( $row['order_id'] ) . '&action=edit' ) ); ?>">
									<?php echo esc_html( '#' . $row['order_number'] ); ?>
								</a>
							</td>
							<td><?php echo esc_html( $row['created_at'] ); ?></td>
							<td><?php echo esc_html( '' !== $row['customer_name'] ? $row['customer_name'] : __( 'Guest', 'affiliate-coupon-tracker' ) ); ?></td>
							<td><?php echo esc_html( $row['coupon_codes'] ); ?></td>
							<td><?php echo esc_html( $row['affiliate_name'] ); ?></td>
							<td><?php echo wp_kses_post( wc_price( (float) $row['items_total'] ) ); ?></td>
							<td><?php echo wp_kses_post( wc_price( (float) $row['shipping_total'] ) ); ?></td>
							<td><?php echo wp_kses_post( wc_price( (float) $row['tax_total'] ) ); ?></td>
							<td><?php echo wp_kses_post( wc_price( (float) $row['order_total'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
				<tfoot>
				<tr>
					<th colspan="5"><?php esc_html_e( 'Totals', 'affiliate-coupon-tracker' ); ?></th>
					<th><?php echo wp_kses_post( wc_price( (float) $report['totals']['items_total'] ) ); ?></th>
					<th><?php echo wp_kses_post( wc_price( (float) $report['totals']['shipping_total'] ) ); ?></th>
					<th><?php echo wp_kses_post( wc_price( (float) $report['totals']['tax_total'] ) ); ?></th>
					<th><?php echo wp_kses_post( wc_price( (float) $report['totals']['order_total'] ) ); ?></th>
				</tr>
				</tfoot>
			</table>
		</div>
		<?php
	}
}
