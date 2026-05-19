<?php
/**
 * Build affiliate monthly order reports.
 *
 * @package AffiliateCouponTracker
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ACT_Order_Report_Repository
 */
class ACT_Order_Report_Repository {

	/** @var string Only sum rows where WooCommerce considers the order paid. */
	const AMOUNT_BASIS_PAID_ONLY = 'paid_only';

	/** @var string Sum every matched affiliate row regardless of payment / status. */
	const AMOUNT_BASIS_ALL = 'all';

	/** Show matched rows regardless of commission-paid bookkeeping filter. */
	const BOOKKEEPING_ALL = 'all';

	/** Show only orders marked as affiliate commission paid. */
	const BOOKKEEPING_COMMISSION_PAID = 'commission_paid';

	/** Show only orders not marked as affiliate commission paid. */
	const BOOKKEEPING_COMMISSION_OPEN = 'commission_open';

	/**
	 * Build a monthly affiliate report.
	 *
	 * @param string $month               Month in YYYY-MM format.
	 * @param string $affiliate_key       Affiliate key from selector.
	 * @param string $amount_basis        One of self::AMOUNT_BASIS_*.
	 * @param string $bookkeeping_filter One of self::BOOKKEEPING_* (scope rows by commission bookkeeping mark).
	 * @param string $sort_orderby       Report column slug for sorting (whitelist); empty preserves query order.
	 * @param string $sort_direction     asc or desc.
	 * @return array<string, mixed>
	 */
	public function build_monthly_report( $month, $affiliate_key = '', $amount_basis = '', $bookkeeping_filter = '', $sort_orderby = '', $sort_direction = '' ) {
		$month              = $this->normalize_month( $month );
		$range              = $this->get_month_range( $month );
		$amount_basis       = $this->normalize_amount_basis( $amount_basis );
		$bookkeeping_filter = $this->normalize_bookkeeping_filter( $bookkeeping_filter );
		$sort_params        = $this->normalize_report_sort( $sort_orderby, $sort_direction );

		$orders = wc_get_orders(
			array(
				'type'         => 'shop_order',
				'status'       => array_keys( wc_get_order_statuses() ),
				'limit'        => -1,
				'return'       => 'objects',
				'date_created' => $range['start']->getTimestamp() . '...' . ( $range['end']->getTimestamp() - 1 ),
			)
		);

		$rows = array();

		$paid_matches                        = 0;
		$unpaid_matches                      = 0;
		$commission_bookkeeping_marked_count = 0;
		$commission_bookkeeping_open_count   = 0;

		foreach ( $orders as $order ) {
			$match = $this->get_order_affiliate_match( $order, $affiliate_key );
			if ( ! $match['matches_filter'] ) {
				continue;
			}

			$item_total = 0.0;
			foreach ( $order->get_items( 'line_item' ) as $line_item ) {
				$item_total += (float) $line_item->get_total();
			}

			$shipping_total = (float) $order->get_shipping_total();
			$tax_total      = (float) $order->get_total_tax();
			$order_total    = (float) $order->get_total();

			$coupon_display = '';
			if ( ! empty( $match['coupon_codes'] ) ) {
				$coupon_display = implode( ', ', $match['coupon_codes'] );
			} elseif ( ! empty( $match['customer_only'] ) ) {
				$coupon_display = __( 'Customer profile (no coupon)', 'affiliate-coupon-tracker' );
			}

			$status_slug      = $order->get_status();
			$status_label     = function_exists( 'wc_get_order_status_name' )
				? wc_get_order_status_name( $status_slug )
				: $status_slug;
			$payment_confirmed = $order->is_paid();

			if ( $payment_confirmed ) {
				++$paid_matches;
			} else {
				++$unpaid_matches;
			}

			$counts_in_totals = ( self::AMOUNT_BASIS_ALL === $amount_basis )
				? true
				: $payment_confirmed;

			$commission_mark_paid      = ACT_Affiliate_Payout_Mark::is_marked( $order );
			$commission_mark_paid_at   = ACT_Affiliate_Payout_Mark::get_marked_at_display( $order );
			if ( $commission_mark_paid ) {
				++$commission_bookkeeping_marked_count;
			} else {
				++$commission_bookkeeping_open_count;
			}

			$created_obj = $order->get_date_created();
			$created_ts  = $created_obj ? (int) $created_obj->getTimestamp() : 0;

			$rows[] = array(
				'order_id'              => $order->get_id(),
				'order_number'          => $order->get_order_number(),
				'created_at'            => $created_obj ? $created_obj->date_i18n( 'Y-m-d H:i' ) : '',
				'created_sort_ts'       => $created_ts,
				'customer_name'         => trim( $order->get_formatted_billing_full_name() ),
				'coupon_codes'          => $coupon_display,
				'affiliate_name'        => implode( ', ', $match['affiliate_labels'] ),
				'status_slug'           => $status_slug,
				'status_label'          => $status_label,
				'payment_confirmed'     => $payment_confirmed,
				'counts_in_totals'      => $counts_in_totals,
				'commission_mark_paid'  => $commission_mark_paid,
				'commission_mark_paid_at' => $commission_mark_paid_at,
				'items_total'           => $item_total,
				'shipping_total'        => $shipping_total,
				'tax_total'             => $tax_total,
				'order_total'             => $order_total,
			);
		}

		$matched_total_before_bookkeeping_filter = count( $rows );

		$filtered_rows = $this->filter_rows_by_bookkeeping_scope( $rows, $bookkeeping_filter );
		$filtered_rows = $this->sort_report_rows( $filtered_rows, $sort_params['orderby'], $sort_params['direction'] );

		$totals        = $this->sum_totals_from_rows_using_counts_flag( $filtered_rows );

		$totals_row_count                       = $this->count_rows_that_count_into_totals( $filtered_rows );
		$totals_payment_completed_only          = $this->sum_totals_for_completed_payment_rows( $filtered_rows );
		$totals_payment_completed_order_count = $this->count_completed_payment_rows( $filtered_rows );

		return array(
			'month'              => $month,
			'amount_basis'       => $amount_basis,
			'bookkeeping_filter' => $bookkeeping_filter,
			'report_sort_by'      => $sort_params['orderby'],
			'report_sort_direction' => $sort_params['direction'],
			'rows'               => $filtered_rows,
			'totals'             => $totals,
			'totals_payment_completed_only' => $totals_payment_completed_only,
			'totals_payment_completed_order_count' => $totals_payment_completed_order_count,
			'count'              => count( $filtered_rows ),
			'matched_orders_total_before_bookkeeping_filter' => $matched_total_before_bookkeeping_filter,
			'commission_bookkeeping_marked'                 => $commission_bookkeeping_marked_count,
			'commission_bookkeeping_open'                   => $commission_bookkeeping_open_count,
			'matched_paid'                                   => $paid_matches,
			'matched_unpaid'                                 => $unpaid_matches,
			'totals_row_count'                               => $totals_row_count,
		);
	}

	/**
	 * @param array<int,array<string,mixed>> $rows Matched affiliate rows before bookkeeping filter.
	 * @param string                          $scope self::BOOKKEEPING_* scope.
	 * @return array<int,array<string,mixed>>
	 */
	private function filter_rows_by_bookkeeping_scope( array $rows, $scope ) {
		if ( self::BOOKKEEPING_ALL === $scope ) {
			return array_values( $rows );
		}

		$out = array();
		foreach ( $rows as $row ) {
			$commission_mark_paid = ! empty( $row['commission_mark_paid'] );
			if ( self::BOOKKEEPING_COMMISSION_PAID === $scope && $commission_mark_paid ) {
				$out[] = $row;
			}
			if ( self::BOOKKEEPING_COMMISSION_OPEN === $scope && ! $commission_mark_paid ) {
				$out[] = $row;
			}
		}

		return $out;
	}

	/**
	 * Normalize report sort GET parameters (slug + direction).
	 *
	 * @param string $orderby Column slug.
	 * @param string $dir     asc | desc | ''.
	 * @return array{orderby:string,direction:string}
	 */
	private function normalize_report_sort( $orderby, $dir ) {
		$key = sanitize_key( (string) $orderby );

		$allowed = array(
			'order'      => true,
			'date'       => true,
			'customer'   => true,
			'coupon'     => true,
			'affiliate'  => true,
			'status'     => true,
			'payment'    => true,
			'commission' => true,
			'items'      => true,
			'shipping'   => true,
			'tax'        => true,
			'total'      => true,
		);

		if ( '' === $key || ! isset( $allowed[ $key ] ) ) {
			return array(
				'orderby'   => '',
				'direction' => 'desc',
			);
		}

		$d = strtolower( trim( (string) $dir ) );
		if ( ! in_array( $d, array( 'asc', 'desc' ), true ) ) {
			$desc_first = array( 'order', 'date', 'payment', 'commission', 'items', 'shipping', 'tax', 'total' );
			$d           = in_array( $key, $desc_first, true ) ? 'desc' : 'asc';
		}

		return array(
			'orderby'   => $key,
			'direction' => $d,
		);
	}

	/**
	 * Sort filtered report rows after bookkeeping scope.
	 *
	 * @param array<int,array<string,mixed>> $rows Rows to sort.
	 * @param string                          $orderby Allowed column slug or empty.
	 * @param string                          $direction asc | desc (used only when orderby non-empty).
	 * @return array<int,array<string,mixed>>
	 */
	private function sort_report_rows( array $rows, $orderby, $direction ) {
		$orderby = (string) $orderby;
		if ( '' === $orderby ) {
			return array_values( $rows );
		}

		$flip = ( 'desc' === strtolower( (string) $direction ) ) ? -1 : 1;
		$self = $this;

		usort(
			$rows,
			static function ( $a, $b ) use ( $self, $flip, $orderby ) {
				if ( ! is_array( $a ) || ! is_array( $b ) ) {
					return 0;
				}

				$primary = $self->compare_rows_for_sort_key( $a, $b, $orderby );
				if ( 0 !== $primary ) {
					return $flip * $primary;
				}

				return ( (int) $b['order_id'] <=> (int) $a['order_id'] );
			}
		);

		return $rows;
	}

	/**
	 * @param array<string,mixed> $a Left row.
	 * @param array<string,mixed> $b Right row.
	 */
	private function compare_rows_for_sort_key( array $a, array $b, $orderby ) {
		switch ( $orderby ) {
			case 'order':
				return (int) $a['order_id'] <=> (int) $b['order_id'];
			case 'date':
				$ta = isset( $a['created_sort_ts'] ) ? (int) $a['created_sort_ts'] : 0;
				$tb = isset( $b['created_sort_ts'] ) ? (int) $b['created_sort_ts'] : 0;
				return $ta <=> $tb;

			case 'customer':
				$sa = strtolower( isset( $a['customer_name'] ) ? (string) $a['customer_name'] : '' );
				$sb = strtolower( isset( $b['customer_name'] ) ? (string) $b['customer_name'] : '' );
				return $sa <=> $sb;

			case 'coupon':
				$sa = strtolower( isset( $a['coupon_codes'] ) ? (string) $a['coupon_codes'] : '' );
				$sb = strtolower( isset( $b['coupon_codes'] ) ? (string) $b['coupon_codes'] : '' );
				return $sa <=> $sb;

			case 'affiliate':
				$sa = strtolower( isset( $a['affiliate_name'] ) ? (string) $a['affiliate_name'] : '' );
				$sb = strtolower( isset( $b['affiliate_name'] ) ? (string) $b['affiliate_name'] : '' );
				return $sa <=> $sb;

			case 'status':
				$r = strcasecmp(
					isset( $a['status_slug'] ) ? (string) $a['status_slug'] : '',
					isset( $b['status_slug'] ) ? (string) $b['status_slug'] : ''
				);
				return $r < 0 ? -1 : ( $r > 0 ? 1 : 0 );

			case 'payment':
				return ( (int) ! empty( $a['payment_confirmed'] ) <=> (int) ! empty( $b['payment_confirmed'] ) );

			case 'commission':
				return ( (int) ! empty( $a['commission_mark_paid'] ) <=> (int) ! empty( $b['commission_mark_paid'] ) );

			case 'items':
				return $this->float_compare_row_fields( $a, $b, 'items_total' );

			case 'shipping':
				return $this->float_compare_row_fields( $a, $b, 'shipping_total' );

			case 'tax':
				return $this->float_compare_row_fields( $a, $b, 'tax_total' );

			case 'total':
				return $this->float_compare_row_fields( $a, $b, 'order_total' );

			default:
				return 0;
		}
	}

	/**
	 * Numeric compare helper for totals columns.
	 *
	 * @param array<string,mixed> $a    Row left.
	 * @param array<string,mixed> $b    Row right.
	 * @param string              $key  Row numeric key.
	 */
	private function float_compare_row_fields( array $a, array $b, $key ) {
		return ( (float) ( $a[ $key ] ?? 0 ) ) <=> ( (float) ( $b[ $key ] ?? 0 ) );
	}

	/**
	 * Sum money columns using the same Woo paid / all basis encoded in counts_in_totals.
	 *
	 * @param array<int,array<string,mixed>> $rows Visible rows after bookkeeping scope.
	 * @return array{items_total:float,shipping_total:float,tax_total:float,order_total:float}
	 */
	private function sum_totals_from_rows_using_counts_flag( array $rows ) {
		$totals = array(
			'items_total'    => 0.0,
			'shipping_total' => 0.0,
			'tax_total'      => 0.0,
			'order_total'    => 0.0,
		);

		foreach ( $rows as $row ) {
			if ( empty( $row['counts_in_totals'] ) ) {
				continue;
			}
			$totals['items_total']    += (float) $row['items_total'];
			$totals['shipping_total'] += (float) $row['shipping_total'];
			$totals['tax_total']      += (float) $row['tax_total'];
			$totals['order_total']    += (float) $row['order_total'];
		}

		return $totals;
	}

	/**
	 * @param array<int,array<string,mixed>> $rows Rows after bookkeeping scope.
	 * @return int
	 */
	private function count_rows_that_count_into_totals( array $rows ) {
		$n = 0;
		foreach ( $rows as $row ) {
			if ( ! empty( $row['counts_in_totals'] ) ) {
				++$n;
			}
		}

		return $n;
	}

	/**
	 * Sum money columns only for WooCommerce-paid rows (among current table scope).
	 *
	 * @param array<int,array<string,mixed>> $rows Visible rows after bookkeeping scope.
	 * @return array{items_total:float,shipping_total:float,tax_total:float,order_total:float}
	 */
	private function sum_totals_for_completed_payment_rows( array $rows ) {
		$totals = array(
			'items_total'    => 0.0,
			'shipping_total' => 0.0,
			'tax_total'      => 0.0,
			'order_total'    => 0.0,
		);

		foreach ( $rows as $row ) {
			if ( empty( $row['payment_confirmed'] ) ) {
				continue;
			}
			$totals['items_total']    += (float) $row['items_total'];
			$totals['shipping_total'] += (float) $row['shipping_total'];
			$totals['tax_total']      += (float) $row['tax_total'];
			$totals['order_total']    += (float) $row['order_total'];
		}

		return $totals;
	}

	/**
	 * Rows in scope with WooCommerce payment completed.
	 *
	 * @param array<int,array<string,mixed>> $rows Visible rows.
	 * @return int
	 */
	private function count_completed_payment_rows( array $rows ) {
		$n = 0;
		foreach ( $rows as $row ) {
			if ( ! empty( $row['payment_confirmed'] ) ) {
				++$n;
			}
		}

		return $n;
	}

	/**
	 * Get affiliate options sourced from coupon metadata.
	 *
	 * @return array<string, string>
	 */
	public function get_affiliate_options() {
		$options = array();
		$coupons = array();

		if ( function_exists( 'wc_get_coupons' ) ) {
			$coupons = wc_get_coupons(
				array(
					'limit' => -1,
				)
			);
		} else {
			$coupon_ids = get_posts(
				array(
					'post_type'      => 'shop_coupon',
					'post_status'    => array( 'publish', 'draft', 'private', 'pending' ),
					'fields'         => 'ids',
					'posts_per_page' => -1,
					'no_found_rows'  => true,
				)
			);

			foreach ( $coupon_ids as $coupon_id ) {
				$coupon = new WC_Coupon( $coupon_id );
				if ( $coupon->get_id() ) {
					$coupons[] = $coupon;
				}
			}
		}

		foreach ( $coupons as $coupon ) {
			$affiliate = $this->get_affiliate_for_coupon_id( $coupon->get_id() );
			if ( '' === $affiliate['key'] ) {
				continue;
			}

			$options[ $affiliate['key'] ] = $affiliate['label'];
		}

		asort( $options, SORT_NATURAL | SORT_FLAG_CASE );
		return $options;
	}

	/**
	 * Resolve affiliate key for a coupon post ID.
	 *
	 * @param int $coupon_id Coupon ID.
	 * @return string Canonical key or empty string.
	 */
	public function get_affiliate_key_for_coupon_id( $coupon_id ) {
		$affiliate = $this->get_affiliate_for_coupon_id( (int) $coupon_id );
		return $affiliate['key'];
	}

	/**
	 * Human label for a canonical affiliate key (from coupon configuration).
	 *
	 * @param string $key Affiliate key.
	 * @return string Empty if unknown / stale.
	 */
	public function get_affiliate_label_for_key( $key ) {
		$key = is_string( $key ) ? trim( $key ) : '';
		if ( '' === $key ) {
			return '';
		}

		$options = $this->get_affiliate_options();
		return isset( $options[ $key ] ) ? $options[ $key ] : '';
	}

	/**
	 * Whether order appears on affiliate report when Affiliate filter is "all".
	 *
	 * @param WC_Order $order WooCommerce order object.
	 * @return bool
	 */
	public function order_has_affiliate_attribution( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return false;
		}

		$m = $this->get_order_affiliate_match( $order, '' );

		return true === $m['matches_filter'];
	}

	/**
	 * Coupon codes, affiliate labels, and filter match for an order.
	 *
	 * @param WC_Order $order         Order.
	 * @param string   $affiliate_key Selected affiliate filter (empty = all).
	 * @return array<string, mixed>
	 */
	private function get_order_affiliate_match( $order, $affiliate_key ) {
		$coupon_codes          = $order->get_coupon_codes();
		$matched_codes         = array();
		$affiliate_labels      = array();
		$coupon_affiliate_keys = array();

		foreach ( $coupon_codes as $coupon_code ) {
			$coupon = new WC_Coupon( $coupon_code );
			if ( ! $coupon->get_id() ) {
				continue;
			}

			$affiliate = $this->get_affiliate_for_coupon_id( $coupon->get_id() );
			if ( '' === $affiliate['key'] ) {
				continue;
			}

			$matched_codes[]         = $coupon_code;
			$affiliate_labels[]      = $affiliate['label'];
			$coupon_affiliate_keys[] = $affiliate['key'];
		}

		$matched_codes          = array_values( array_unique( $matched_codes ) );
		$coupon_affiliate_keys  = array_values( array_unique( $coupon_affiliate_keys ) );
		$has_coupon_affiliate   = ! empty( $matched_codes );
		$customer_key_effective = '';

		$user_id = (int) $order->get_user_id();
		if ( $user_id ) {
			$key_from_snapshot = trim( (string) $order->get_meta( ACT_Customer_Affiliate::ORDER_LINKED_SNAPSHOT_META ) );

			$linked_key_at_purchase = $key_from_snapshot;
			if ( '' === $linked_key_at_purchase ) {
				/**
				 * Legacy orders placed before snapshots existed: opt-in fallback to live user meta.
				 *
				 * @param bool     $fallback Whether to fall back when snapshot meta is missing.
				 * @param WC_Order $order    Order.
				 */
				if ( apply_filters( 'act_reports_use_live_user_meta_when_order_snapshot_missing', false, $order ) ) {
					$linked_key_at_purchase = trim( (string) get_user_meta( $user_id, ACT_Customer_Affiliate::META_KEY, true ) );
				}
			}

			if ( '' !== $linked_key_at_purchase ) {
				$label = $this->get_affiliate_label_for_key( $linked_key_at_purchase );
				if ( '' !== $label ) {
					$customer_key_effective = $linked_key_at_purchase;
					$affiliate_labels[]     = $label;
				}
			}
		}

		$affiliate_labels = array_values( array_unique( $affiliate_labels ) );

		if ( ! $has_coupon_affiliate && '' === $customer_key_effective ) {
			return array(
				'matches_filter'  => false,
				'coupon_codes'    => array(),
				'affiliate_labels'=> array(),
				'customer_only'   => false,
			);
		}

		$customer_only = ( ! $has_coupon_affiliate ) && ( '' !== $customer_key_effective );

		$matches_filter = ( '' === $affiliate_key );
		if ( '' !== $affiliate_key ) {
			$matches_filter = in_array( $affiliate_key, $coupon_affiliate_keys, true ) || ( $affiliate_key === $customer_key_effective );
		}

		return array(
			'matches_filter'   => $matches_filter,
			'coupon_codes'     => $matched_codes,
			'affiliate_labels' => $affiliate_labels,
			'customer_only'    => $customer_only,
		);
	}

	/**
	 * Get affiliate data for a coupon ID.
	 *
	 * @param int $coupon_id Coupon post ID.
	 * @return array{key:string,label:string}
	 */
	protected function get_affiliate_for_coupon_id( $coupon_id ) {
		$affiliate_id   = trim( (string) get_post_meta( $coupon_id, ACT_Coupon_Affiliate_Fields::META_AFFILIATE_ID, true ) );
		$affiliate_name = trim( (string) get_post_meta( $coupon_id, ACT_Coupon_Affiliate_Fields::META_AFFILIATE_NAME, true ) );

		if ( '' === $affiliate_id && '' === $affiliate_name ) {
			return array(
				'key'   => '',
				'label' => '',
			);
		}

		$key   = '' !== $affiliate_id ? 'id:' . $affiliate_id : 'name:' . $affiliate_name;
		$label = '' !== $affiliate_name ? $affiliate_name : $affiliate_id;

		return array(
			'key'   => $key,
			'label' => $label,
		);
	}

	/**
	 * Normalize month input to YYYY-MM.
	 *
	 * @param string $month Input month.
	 * @return string
	 */
	private function normalize_month( $month ) {
		$month = trim( (string) $month );
		if ( preg_match( '/^\d{4}\-(0[1-9]|1[0-2])$/', $month ) ) {
			return $month;
		}

		return wp_date( 'Y-m' );
	}

	/**
	 * Normalize report amount basis GET parameter.
	 *
	 * @param string $amount_basis Raw basis.
	 * @return string self::AMOUNT_BASIS_PAID_ONLY or self::AMOUNT_BASIS_ALL.
	 */
	private function normalize_amount_basis( $amount_basis ) {
		$amount_basis = trim( (string) $amount_basis );

		return self::AMOUNT_BASIS_ALL === $amount_basis ? self::AMOUNT_BASIS_ALL : self::AMOUNT_BASIS_PAID_ONLY;
	}

	/**
	 * Normalize commission bookkeeping visibility filter GET parameter.
	 *
	 * @param string $filter Raw GET value.
	 * @return string self::BOOKKEEPING_* constants.
	 */
	private function normalize_bookkeeping_filter( $filter ) {
		$filter = sanitize_key( (string) $filter );

		$allowed = array(
			self::BOOKKEEPING_ALL,
			self::BOOKKEEPING_COMMISSION_PAID,
			self::BOOKKEEPING_COMMISSION_OPEN,
		);

		return in_array( $filter, $allowed, true ) ? $filter : self::BOOKKEEPING_ALL;
	}

	/**
	 * Get month date range from normalized month.
	 *
	 * @param string $month Month in YYYY-MM.
	 * @return array{start:DateTimeImmutable,end:DateTimeImmutable}
	 */
	private function get_month_range( $month ) {
		$timezone = wp_timezone();
		$start    = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $month . '-01 00:00:00', $timezone );

		if ( ! $start ) {
			$start = new DateTimeImmutable( 'first day of this month 00:00:00', $timezone );
		}

		return array(
			'start' => $start,
			'end'   => $start->modify( '+1 month' ),
		);
	}
}
