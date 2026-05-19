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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_act_toggle_affiliate_commission_paid', array( $this, 'ajax_toggle_commission_paid' ) );
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
	 * Admin assets for AJAX commission marking (report page only).
	 *
	 * @param string $hook_suffix wp-admin screen suffix.
	 * @return void
	 */
	public function enqueue_scripts( $hook_suffix ) {
		if ( 'woocommerce_page_affiliate-coupon-tracker' !== $hook_suffix ) {
			return;
		}

		wp_register_script(
			'act-affiliate-report-page',
			false,
			array(),
			defined( 'ACT_VERSION' ) ? ACT_VERSION : '1',
			true
		);

		wp_enqueue_script( 'act-affiliate-report-page' );

		wp_localize_script(
			'act-affiliate-report-page',
			'ACT_AffiliateReport',
			array(
				'ajaxUrl'              => admin_url( 'admin-ajax.php' ),
				'toggleCommissionNonce'=> wp_create_nonce( 'act_commission_toggle' ),
				'i18nError'             => __( 'Could not save. Reload and try again.', 'affiliate-coupon-tracker' ),
			)
		);

		$inline = <<<'JS'
(function () {
	document.addEventListener('DOMContentLoaded', function () {
		document.body.addEventListener('change', function (ev) {
			var inp = ev.target;
			if (!inp || !inp.classList || !inp.classList.contains('act-affiliate-commission-toggle')) {
				return;
			}
			var cell = inp.closest('.act-affiliate-commission-paid-cell');
			var noteEl = cell ? cell.querySelector('.act-affiliate-commission-mark-at') : null;
			var want = inp.checked ? '1' : '0';
			inp.disabled = true;
			var fd = new window.FormData();
			fd.append('action', 'act_toggle_affiliate_commission_paid');
			fd.append('nonce', window.ACT_AffiliateReport.toggleCommissionNonce);
			fd.append('order_id', String(inp.dataset.orderId || inp.getAttribute('data-order-id') || ''));
			fd.append('marked', want);
			window.fetch(window.ACT_AffiliateReport.ajaxUrl, {
				method: 'POST',
				body: fd,
				credentials: 'same-origin',
			})
				.then(function (r) { return r.json(); })
				.then(function (payload) {
					if (!payload || payload.success !== true || !payload.data) {
						throw new Error('bad_response');
					}
					window.ACT_AffiliateReport.toggleCommissionNonce = payload.data.nonce;
					inp.checked = !!payload.data.marked;
					if (noteEl) {
						noteEl.textContent = payload.data.marked_at_text || '';
					}
				})
				.catch(function () {
					window.alert(window.ACT_AffiliateReport.i18nError);
					inp.checked = want !== '1';
				})
				.finally(function () {
					inp.disabled = false;
				});
		});
	});
})();
JS;
		wp_add_inline_script( 'act-affiliate-report-page', $inline );
	}

	/**
	 * Save affiliate commission paid bookkeeping flag via AJAX.
	 *
	 * @return void
	 */
	public function ajax_toggle_commission_paid() {
		check_ajax_referer( 'act_commission_toggle', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ) );
		}

		$order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;
		$order    = $order_id ? wc_get_order( $order_id ) : false;

		if ( ! $order ) {
			wp_send_json_error( array( 'message' => __( 'Invalid order.', 'affiliate-coupon-tracker' ) ) );
		}

		if ( ! $this->repository->order_has_affiliate_attribution( $order ) ) {
			wp_send_json_error(
				array( 'message' => __( 'This order has no affiliate attribution.', 'affiliate-coupon-tracker' ) )
			);
		}

		$marked = wc_string_to_bool( isset( $_POST['marked'] ) ? sanitize_text_field( wp_unslash( $_POST['marked'] ) ) : 'no' );

		ACT_Affiliate_Payout_Mark::set_marked( $order, $marked );

		$_order_refreshed = wc_get_order( $order_id );
		$_marked          = ACT_Affiliate_Payout_Mark::is_marked( $_order_refreshed );

		wp_send_json_success(
			array(
				'marked'         => $_marked,
				'marked_at_text' => ACT_Affiliate_Payout_Mark::get_marked_at_display( $_order_refreshed ),
				'nonce'          => wp_create_nonce( 'act_commission_toggle' ),
			)
		);
	}

	/**
	 * Persisted filter/query args plus sort keys for affiliate report URLs.
	 *
	 * @param array<string,string> $overrides Overrides for admin.php GET.
	 * @return string Absolute admin URL with query string (escaped-ready).
	 */
	private function affiliate_report_admin_url_with_query( array $overrides ) {
		$params = wp_parse_args(
			$overrides,
			array(
				'page'               => 'affiliate-coupon-tracker',
				'report_month'       => wp_date( 'Y-m' ),
				'affiliate_key'      => '',
				'amount_basis'       => ACT_Order_Report_Repository::AMOUNT_BASIS_PAID_ONLY,
				'commission_scope'   => ACT_Order_Report_Repository::BOOKKEEPING_ALL,
				'act_orderby'        => '',
				'act_ord'            => 'desc',
			)
		);

		foreach ( array( 'commission_scope', 'act_orderby' ) as $k ) {
			$params[ $k ] = sanitize_key( $params[ $k ] );
		}

		$params['page']          = 'affiliate-coupon-tracker';
		$params['report_month'] = preg_match( '/^\d{4}\-(?:0[1-9]|1[0-2])$/', $params['report_month'] )
			? $params['report_month']
			: wp_date( 'Y-m' );
		$params['affiliate_key'] = sanitize_text_field( $params['affiliate_key'] );

		$basis = $params['amount_basis'];
		if ( ACT_Order_Report_Repository::AMOUNT_BASIS_ALL !== $basis ) {
			$params['amount_basis'] = ACT_Order_Report_Repository::AMOUNT_BASIS_PAID_ONLY;
		}

		$scope = $params['commission_scope'];
		$scopes = array(
			ACT_Order_Report_Repository::BOOKKEEPING_ALL,
			ACT_Order_Report_Repository::BOOKKEEPING_COMMISSION_OPEN,
			ACT_Order_Report_Repository::BOOKKEEPING_COMMISSION_PAID,
		);
		if ( ! in_array( $scope, $scopes, true ) ) {
			$params['commission_scope'] = ACT_Order_Report_Repository::BOOKKEEPING_ALL;
		}

		$dir = strtolower( trim( $params['act_ord'] ) );
		if ( ! in_array( $dir, array( 'asc', 'desc' ), true ) ) {
			$params['act_ord'] = 'desc';
		} else {
			$params['act_ord'] = $dir;
		}

		if ( '' !== $params['act_orderby'] ) {
			$allowed_cols = array(
				'order',
				'date',
				'customer',
				'coupon',
				'affiliate',
				'status',
				'payment',
				'commission',
				'items',
				'shipping',
				'tax',
				'total',
			);
			if ( ! in_array( $params['act_orderby'], $allowed_cols, true ) ) {
				$params['act_orderby'] = '';
				$params['act_ord']    = 'desc';
			}
		}

		if ( '' === $params['act_orderby'] ) {
			unset( $params['act_orderby'], $params['act_ord'] );
		}

		return add_query_arg( $params, admin_url( 'admin.php' ) );
	}

	/**
	 * First sort direction after switching to another column header.
	 *
	 * @param string $orderby_slug Allowed sort slug.
	 * @return string asc|desc.
	 */
	private function default_report_sort_direction( $orderby_slug ) {
		$desc_first = array( 'order', 'date', 'payment', 'commission', 'items', 'shipping', 'tax', 'total' );
		$orderby_slug = sanitize_key( (string) $orderby_slug );

		return in_array( $orderby_slug, $desc_first, true ) ? 'desc' : 'asc';
	}

	/**
	 * Direction applied when activating or toggling a sort column header.
	 *
	 * @param string                $orderby_slug Column slug clicked.
	 * @param array<string, mixed> $report Built report (sort state).
	 * @return string asc|desc.
	 */
	private function next_report_sort_direction( $orderby_slug, array $report ) {
		$orderby_slug = sanitize_key( (string) $orderby_slug );

		if ( isset( $report['report_sort_by'], $report['report_sort_direction'] )
			&& '' !== (string) $report['report_sort_by']
			&& $orderby_slug === (string) $report['report_sort_by'] ) {
			return 'desc' === $report['report_sort_direction'] ? 'asc' : 'desc';
		}

		return $this->default_report_sort_direction( $orderby_slug );
	}

	/**
	 * Prints a clickable sortable `<th>` for the affiliate report table.
	 *
	 * @param string                $orderby_slug Column slug understood by ACT_Order_Report_Repository.
	 * @param string                $heading      Translated column label.
	 * @param array<string, mixed>  $report       Built report bundle.
	 * @param array<string, string> $persist      Current filter selections (report_month, affiliate_key, …).
	 * @return void
	 */
	private function render_sortable_heading( $orderby_slug, $heading, array $report, array $persist ) {
		$url_raw = $this->affiliate_report_admin_url_with_query(
			array_merge(
				$persist,
				array(
					'act_orderby' => $orderby_slug,
					'act_ord'     => $this->next_report_sort_direction( $orderby_slug, $report ),
				)
			)
		);

		$active = isset( $report['report_sort_by'] )
			&& sanitize_key( (string) $report['report_sort_by'] ) === sanitize_key( (string) $orderby_slug );
		$arrow  = '';
		if ( $active ) {
			$d     = strtolower( isset( $report['report_sort_direction'] ) ? (string) $report['report_sort_direction'] : 'desc' );
			$arrow = 'asc' === $d ? __( ' ↑', 'affiliate-coupon-tracker' ) : __( ' ↓', 'affiliate-coupon-tracker' );
		}

		echo '<th scope="col"'
			. ( $active ? ' class="act-affiliate-report-sort-act"' : '' )
			. '><a href="'
			. esc_url( $url_raw )
			. '"><span>'
			. esc_html( $heading )
			. '</span>'
			. esc_html( $arrow )
			. '</a></th>';
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
		$bookkeeping_raw     = isset( $_GET['commission_scope'] ) ? sanitize_key( wp_unslash( $_GET['commission_scope'] ) ) : '';
		$act_orderby_in      = isset( $_GET['act_orderby'] ) ? sanitize_key( wp_unslash( $_GET['act_orderby'] ) ) : '';
		$act_ord_in          = isset( $_GET['act_ord'] ) ? strtolower( trim( sanitize_text_field( wp_unslash( $_GET['act_ord'] ) ) ) ) : '';

		if ( ACT_Order_Report_Repository::AMOUNT_BASIS_ALL === $selected_amount_raw ) {
			$selected_basis = ACT_Order_Report_Repository::AMOUNT_BASIS_ALL;
		} else {
			$selected_basis = ACT_Order_Report_Repository::AMOUNT_BASIS_PAID_ONLY;
		}

		$affiliates = $this->repository->get_affiliate_options();
		$report     = $this->repository->build_monthly_report( $selected_month, $selected_affiliate, $selected_basis, $bookkeeping_raw, $act_orderby_in, $act_ord_in );

		$persist_filters = array(
			'report_month'       => $report['month'],
			'affiliate_key'      => $selected_affiliate,
			'amount_basis'       => $selected_basis,
			'commission_scope'   => $report['bookkeeping_filter'],
		);

		?>
		<div class="wrap">
			<style>
				tr.act-report-row-excluded-from-totals td { opacity: 0.68; }
				th.act-affiliate-report-sort-act a { font-weight: 600; }
			</style>
			<h1><?php esc_html_e( 'Affiliate Coupon Report', 'affiliate-coupon-tracker' ); ?></h1>
			<p><?php esc_html_e( 'Orders appear when an affiliate coupon is used, when a logged-in customer was linked at checkout (referral signup, coupon, admin), or both. Affiliate links frozen on each order at purchase are not altered if the customer profile changes later.', 'affiliate-coupon-tracker' ); ?></p>

			<form method="get" style="margin: 16px 0;">
				<input type="hidden" name="page" value="affiliate-coupon-tracker" />
				<?php if ( isset( $report['report_sort_by'] ) && '' !== (string) $report['report_sort_by'] ) : ?>
					<input type="hidden" name="act_orderby" value="<?php echo esc_attr( (string) $report['report_sort_by'] ); ?>" />
					<input type="hidden" name="act_ord" value="<?php echo esc_attr( (string) $report['report_sort_direction'] ); ?>" />
				<?php endif; ?>
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

				<label for="act-commission-scope" style="margin: 0 8px 0 16px;">
					<strong><?php esc_html_e( 'Commission', 'affiliate-coupon-tracker' ); ?></strong>
				</label>
				<select id="act-commission-scope" name="commission_scope">
					<option value="<?php echo esc_attr( ACT_Order_Report_Repository::BOOKKEEPING_ALL ); ?>" <?php selected( $report['bookkeeping_filter'], ACT_Order_Report_Repository::BOOKKEEPING_ALL ); ?>>
						<?php esc_html_e( 'Show all bookkeeping states', 'affiliate-coupon-tracker' ); ?>
					</option>
					<option value="<?php echo esc_attr( ACT_Order_Report_Repository::BOOKKEEPING_COMMISSION_OPEN ); ?>" <?php selected( $report['bookkeeping_filter'], ACT_Order_Report_Repository::BOOKKEEPING_COMMISSION_OPEN ); ?>>
						<?php esc_html_e( 'Not marked affiliate-paid', 'affiliate-coupon-tracker' ); ?>
					</option>
					<option value="<?php echo esc_attr( ACT_Order_Report_Repository::BOOKKEEPING_COMMISSION_PAID ); ?>" <?php selected( $report['bookkeeping_filter'], ACT_Order_Report_Repository::BOOKKEEPING_COMMISSION_PAID ); ?>>
						<?php esc_html_e( 'Marked affiliate-paid only', 'affiliate-coupon-tracker' ); ?>
					</option>
				</select>

				<button type="submit" class="button button-secondary" style="margin-left: 16px;">
					<?php esc_html_e( 'Run Report', 'affiliate-coupon-tracker' ); ?>
				</button>
			</form>

			<p>
				<?php
				printf(
					/* translators: 1: affiliate-attributed order count for month/filter, 2: WooCommerce paid count, 3: not paid yet count */
					esc_html__( 'Attributed orders this selection: %1$d — %2$d WooCommerce paid, %3$d payment not confirmed yet.', 'affiliate-coupon-tracker' ),
					absint( $report['matched_orders_total_before_bookkeeping_filter'] ),
					absint( $report['matched_paid'] ),
					absint( $report['matched_unpaid'] )
				);
				?>
			</p>

			<p>
				<?php
				printf(
					/* translators: 1: count marked affiliate commission paid, 2: not marked yet */
					esc_html__( 'Affiliate commission bookkeeping: %1$d marked paid out, %2$d not marked yet.', 'affiliate-coupon-tracker' ),
					absint( $report['commission_bookkeeping_marked'] ),
					absint( $report['commission_bookkeeping_open'] )
				);
				?>
			</p>

			<?php if ( ACT_Order_Report_Repository::BOOKKEEPING_ALL !== $report['bookkeeping_filter'] ) : ?>
				<p class="description">
					<?php
					printf(
						/* translators: 1: visible rows, 2: total attributed orders before bookkeeping filter */
						esc_html__( 'Table shows %1$d of %2$d attributed orders (commission bookkeeping filter applied).', 'affiliate-coupon-tracker' ),
						absint( $report['count'] ),
						absint( $report['matched_orders_total_before_bookkeeping_filter'] )
					);
					?>
				</p>
			<?php endif; ?>

			<?php if ( ACT_Order_Report_Repository::AMOUNT_BASIS_PAID_ONLY === $report['amount_basis'] ) : ?>
				<p class="description">
					<?php esc_html_e( 'By default WooCommerce only counts processing and completed as paid unless your site filters woocommerce_order_is_paid_statuses. Unpaid rows stay in the table (faded) but are omitted from totals.', 'affiliate-coupon-tracker' ); ?>
				</p>
			<?php else : ?>
				<p class="description">
					<?php esc_html_e( 'Primary totals include every visible row (including unpaid or pending payment). When some orders are unpaid, a second totals row summarizes amounts for WooCommerce payment completed only.', 'affiliate-coupon-tracker' ); ?>
				</p>
			<?php endif; ?>

			<p class="description">
				<?php esc_html_e( 'Commission paid saves on the WooCommerce order. Use this to remember which payouts you settled; WooCommerce payment status is unrelated.', 'affiliate-coupon-tracker' ); ?>
			</p>

			<p class="description">
				<?php esc_html_e( 'Click column headers to sort.', 'affiliate-coupon-tracker' ); ?>
			</p>

			<table class="widefat striped">
				<thead>
				<tr>
				<?php
				$this->render_sortable_heading( 'order', __( 'Order', 'affiliate-coupon-tracker' ), $report, $persist_filters );
				$this->render_sortable_heading( 'date', __( 'Date', 'affiliate-coupon-tracker' ), $report, $persist_filters );
				$this->render_sortable_heading( 'customer', __( 'Customer', 'affiliate-coupon-tracker' ), $report, $persist_filters );
				$this->render_sortable_heading( 'coupon', __( 'Coupon(s)', 'affiliate-coupon-tracker' ), $report, $persist_filters );
				$this->render_sortable_heading( 'affiliate', __( 'Affiliate', 'affiliate-coupon-tracker' ), $report, $persist_filters );
				$this->render_sortable_heading( 'status', __( 'Status', 'affiliate-coupon-tracker' ), $report, $persist_filters );
				$this->render_sortable_heading( 'payment', __( 'Payment confirmed', 'affiliate-coupon-tracker' ), $report, $persist_filters );
				$this->render_sortable_heading( 'items', __( 'Items', 'affiliate-coupon-tracker' ), $report, $persist_filters );
				$this->render_sortable_heading( 'shipping', __( 'Shipping', 'affiliate-coupon-tracker' ), $report, $persist_filters );
				$this->render_sortable_heading( 'tax', __( 'Tax', 'affiliate-coupon-tracker' ), $report, $persist_filters );
				$this->render_sortable_heading( 'total', __( 'Order Total', 'affiliate-coupon-tracker' ), $report, $persist_filters );
				$this->render_sortable_heading( 'commission', __( 'Commission paid', 'affiliate-coupon-tracker' ), $report, $persist_filters );
				?>
				</tr>
				</thead>
				<tbody>
				<?php if ( empty( $report['rows'] ) ) : ?>
					<tr>
						<td colspan="12"><?php esc_html_e( 'No affiliate coupon orders found for this selection.', 'affiliate-coupon-tracker' ); ?></td>
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
							<td class="act-affiliate-commission-paid-cell">
								<label style="white-space: nowrap;">
									<input
										class="act-affiliate-commission-toggle"
										type="checkbox"
										data-order-id="<?php echo esc_attr( (string) (int) $row['order_id'] ); ?>"
										aria-label="<?php esc_attr_e( 'Mark affiliate commission paid for this order', 'affiliate-coupon-tracker' ); ?>"
										<?php checked( ! empty( $row['commission_mark_paid'] ) ); ?>
									/>
									<span class="act-affiliate-commission-mark-at"><?php echo esc_html( isset( $row['commission_mark_paid_at'] ) ? (string) $row['commission_mark_paid_at'] : '' ); ?></span>
								</label>
							</td>
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
					<th aria-hidden="true">&nbsp;</th>
				</tr>
				<?php if ( ACT_Order_Report_Repository::AMOUNT_BASIS_ALL === $report['amount_basis'] && isset( $report['totals_payment_completed_order_count'], $report['totals_row_count'] ) && isset( $report['totals_payment_completed_only'] ) && (int) $report['totals_payment_completed_order_count'] < (int) $report['totals_row_count'] ) : ?>
				<tr>
					<th colspan="7">
						<?php
						echo esc_html__( 'Totals (WooCommerce payment completed)', 'affiliate-coupon-tracker' );
						if ( (int) $report['totals_payment_completed_order_count'] > 0 ) {
							echo ' ';
							echo esc_html(
								sprintf(
									/* translators: %d order count contributing to payment-completed subtotal */
									__( '(%d orders)', 'affiliate-coupon-tracker' ),
									absint( $report['totals_payment_completed_order_count'] )
								)
							);
						}
						?>
					</th>
					<th><?php echo wp_kses_post( wc_price( (float) $report['totals_payment_completed_only']['items_total'] ) ); ?></th>
					<th><?php echo wp_kses_post( wc_price( (float) $report['totals_payment_completed_only']['shipping_total'] ) ); ?></th>
					<th><?php echo wp_kses_post( wc_price( (float) $report['totals_payment_completed_only']['tax_total'] ) ); ?></th>
					<th><?php echo wp_kses_post( wc_price( (float) $report['totals_payment_completed_only']['order_total'] ) ); ?></th>
					<th aria-hidden="true">&nbsp;</th>
				</tr>
				<?php endif; ?>
				</tfoot>
			</table>
		</div>
		<?php
	}
}
