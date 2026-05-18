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

	/**
	 * Build a monthly affiliate report.
	 *
	 * @param string $month         Month in YYYY-MM format.
	 * @param string $affiliate_key Affiliate key from selector.
	 * @return array<string, mixed>
	 */
	public function build_monthly_report( $month, $affiliate_key = '' ) {
		$month = $this->normalize_month( $month );
		$range = $this->get_month_range( $month );

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

		$totals = array(
			'items_total'    => 0.0,
			'shipping_total' => 0.0,
			'tax_total'      => 0.0,
			'order_total'    => 0.0,
		);

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

			$rows[] = array(
				'order_id'       => $order->get_id(),
				'order_number'   => $order->get_order_number(),
				'created_at'     => $order->get_date_created() ? $order->get_date_created()->date_i18n( 'Y-m-d H:i' ) : '',
				'customer_name'  => trim( $order->get_formatted_billing_full_name() ),
				'coupon_codes'   => $coupon_display,
				'affiliate_name' => implode( ', ', $match['affiliate_labels'] ),
				'items_total'    => $item_total,
				'shipping_total' => $shipping_total,
				'tax_total'      => $tax_total,
				'order_total'    => $order_total,
			);

			$totals['items_total']    += $item_total;
			$totals['shipping_total'] += $shipping_total;
			$totals['tax_total']      += $tax_total;
			$totals['order_total']    += $order_total;
		}

		return array(
			'month'  => $month,
			'rows'   => $rows,
			'totals' => $totals,
			'count'  => count( $rows ),
		);
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
