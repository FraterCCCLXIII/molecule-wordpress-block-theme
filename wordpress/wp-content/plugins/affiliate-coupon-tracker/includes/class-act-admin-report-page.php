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

		$selected_month      = isset( $_GET['report_month'] ) ? sanitize_text_field( wp_unslash( $_GET['report_month'] ) ) : wp_date( 'Y-m' );
		$selected_affiliate  = isset( $_GET['affiliate_key'] ) ? sanitize_text_field( wp_unslash( $_GET['affiliate_key'] ) ) : '';
		$selected_amount_raw = isset( $_GET['amount_basis'] ) ? sanitize_text_field( wp_unslash( $_GET['amount_basis'] ) ) : '';

		if ( ACT_Order_Report_Repository::AMOUNT_BASIS_ALL === $selected_amount_raw ) {
			$selected_basis = ACT_Order_Report_Repository::AMOUNT_BASIS_ALL;
		} else {
			$selected_basis = ACT_Order_Report_Repository::AMOUNT_BASIS_PAID_ONLY;
		}

		$affiliates = $this->repository->get_affiliate_options();
		$report     = $this->repository->build_monthly_report( $selected_month, $selected_affiliate, $selected_basis );

		?>
		<div class="wrap">
			<style>
				tr.act-report-row-excluded-from-totals td { opacity: 0.68; }
			</style>
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

				<label for="act-amount-basis" style="margin: 0 8px 0 16px;">
					<strong><?php esc_html_e( 'Totals', 'affiliate-coupon-tracker' ); ?></strong>
				</label>
				<select id="act-amount-basis" name="amount_basis">
					<option value="<?php echo esc_attr( ACT_Order_Report_Repository::AMOUNT_BASIS_PAID_ONLY ); ?>" <?php selected( $report['amount_basis'], ACT_Order_Report_Repository::AMOUNT_BASIS_PAID_ONLY ); ?>>
						<?php esc_html_e( 'Paid orders only', 'affiliate-coupon-tracker' ); ?>
					</option>
					<option value="<?php echo esc_attr( ACT_Order_Report_Repository::AMOUNT_BASIS_ALL ); ?>" <?php selected( $report['amount_basis'], ACT_Order_Report_Repository::AMOUNT_BASIS_ALL ); ?>>
						<?php esc_html_e( 'All matched orders', 'affiliate-coupon-tracker' ); ?>
					</option>
				</select>

				<button type="submit" class="button button-secondary" style="margin-left: 16px;">
					<?php esc_html_e( 'Run Report', 'affiliate-coupon-tracker' ); ?>
				</button>
			</form>

			<p>
				<?php
				printf(
					/* translators: 1: total matched rows, 2: paid count, 3: unpaid count */
					esc_html__( 'Matched orders: %1$d — %2$d with payment confirmed, %3$d not paid yet (WooCommerce paid-status rules; filterable).', 'affiliate-coupon-tracker' ),
					absint( $report['count'] ),
					absint( $report['matched_paid'] ),
					absint( $report['matched_unpaid'] )
				);
				?>
			</p>

			<?php if ( ACT_Order_Report_Repository::AMOUNT_BASIS_PAID_ONLY === $report['amount_basis'] ) : ?>
				<p class="description">
					<?php esc_html_e( 'By default WooCommerce only counts processing and completed as paid unless your site filters woocommerce_order_is_paid_statuses. Unpaid rows stay in the table (faded) but are omitted from totals.', 'affiliate-coupon-tracker' ); ?>
				</p>
			<?php else : ?>
				<p class="description">
					<?php esc_html_e( 'Totals include every matched row, including unpaid or pending-payment orders.', 'affiliate-coupon-tracker' ); ?>
				</p>
			<?php endif; ?>

			<table class="widefat striped">
				<thead>
				<tr>
					<th><?php esc_html_e( 'Order', 'affiliate-coupon-tracker' ); ?></th>
					<th><?php esc_html_e( 'Date', 'affiliate-coupon-tracker' ); ?></th>
					<th><?php esc_html_e( 'Customer', 'affiliate-coupon-tracker' ); ?></th>
					<th><?php esc_html_e( 'Coupon(s)', 'affiliate-coupon-tracker' ); ?></th>
					<th><?php esc_html_e( 'Affiliate', 'affiliate-coupon-tracker' ); ?></th>
					<th><?php esc_html_e( 'Status', 'affiliate-coupon-tracker' ); ?></th>
					<th><?php esc_html_e( 'Payment confirmed', 'affiliate-coupon-tracker' ); ?></th>
					<th><?php esc_html_e( 'Items', 'affiliate-coupon-tracker' ); ?></th>
					<th><?php esc_html_e( 'Shipping', 'affiliate-coupon-tracker' ); ?></th>
					<th><?php esc_html_e( 'Tax', 'affiliate-coupon-tracker' ); ?></th>
					<th><?php esc_html_e( 'Order Total', 'affiliate-coupon-tracker' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php if ( empty( $report['rows'] ) ) : ?>
					<tr>
						<td colspan="11"><?php esc_html_e( 'No affiliate coupon orders found for this selection.', 'affiliate-coupon-tracker' ); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ( $report['rows'] as $row ) : ?>
						<tr<?php echo $row['counts_in_totals'] ? '' : ' class="act-report-row-excluded-from-totals"'; ?>>
							<td>
								<a href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( $row['order_id'] ) . '&action=edit' ) ); ?>">
									<?php echo esc_html( '#' . $row['order_number'] ); ?>
								</a>
							</td>
							<td><?php echo esc_html( $row['created_at'] ); ?></td>
							<td><?php echo esc_html( '' !== $row['customer_name'] ? $row['customer_name'] : __( 'Guest', 'affiliate-coupon-tracker' ) ); ?></td>
							<td><?php echo esc_html( $row['coupon_codes'] ); ?></td>
							<td><?php echo esc_html( $row['affiliate_name'] ); ?></td>
							<td><?php echo esc_html( $row['status_label'] ); ?></td>
							<td><?php echo $row['payment_confirmed'] ? esc_html__( 'Yes', 'affiliate-coupon-tracker' ) : esc_html__( 'No', 'affiliate-coupon-tracker' ); ?></td>
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
					<th colspan="7">
						<?php
						echo ACT_Order_Report_Repository::AMOUNT_BASIS_ALL === $report['amount_basis']
							? esc_html__( 'Totals (all matched orders)', 'affiliate-coupon-tracker' )
							: esc_html__( 'Totals (paid orders only)', 'affiliate-coupon-tracker' );
						if ( ACT_Order_Report_Repository::AMOUNT_BASIS_PAID_ONLY === $report['amount_basis'] && $report['totals_row_count'] > 0 ) {
							echo ' ';
							echo esc_html(
								sprintf(
									/* translators: %d order count included in totals */
									__( '(%d orders)', 'affiliate-coupon-tracker' ),
									absint( $report['totals_row_count'] )
								)
							);
						}
						if ( ACT_Order_Report_Repository::AMOUNT_BASIS_ALL === $report['amount_basis'] && $report['count'] > 0 ) {
							echo ' ';
							echo esc_html(
								sprintf(
									/* translators: %d order count included in totals */
									__( '(%d orders)', 'affiliate-coupon-tracker' ),
									absint( $report['count'] )
								)
							);
						}
						?>
					</th>
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
