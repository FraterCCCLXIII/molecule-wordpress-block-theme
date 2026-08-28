<?php
/**
 * Bookkeeping: whether affiliate commission has been marked paid for an order (order meta).
 *
 * @package AffiliateCouponTracker
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ACT_Affiliate_Payout_Mark
 */
class ACT_Affiliate_Payout_Mark {

	/**
	 * Order meta: commission marked paid (yes/no stored as Woo bool string).
	 */
	const META_MARKED = '_act_affiliate_commission_paid';

	/**
	 * Order meta: when marking was toggled on (mysql datetime).
	 */
	const META_MARKED_AT = '_act_affiliate_commission_paid_at';

	/**
	 * @param WC_Order $order WC order.
	 * @return bool
	 */
	public static function is_marked( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return false;
		}

		return wc_string_to_bool( $order->get_meta( self::META_MARKED ) );
	}

	/**
	 * @param WC_Order $order WC order.
	 * @param bool     $marked Whether commission is marked paid.
	 */
	public static function set_marked( $order, $marked ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		if ( $marked ) {
			$order->update_meta_data( self::META_MARKED, 'yes' );
			if ( '' === trim( (string) $order->get_meta( self::META_MARKED_AT ) ) ) {
				$order->update_meta_data( self::META_MARKED_AT, current_time( 'mysql' ) );
			}
		} else {
			$order->delete_meta_data( self::META_MARKED );
			$order->delete_meta_data( self::META_MARKED_AT );
		}

		$order->save();
	}

	/**
	 * @param WC_Order $order WC order.
	 * @return string Stored mysql datetime or empty string.
	 */
	public static function get_marked_at_mysql( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return '';
		}

		return trim( (string) $order->get_meta( self::META_MARKED_AT ) );
	}

	/**
	 * @param WC_Order $order WC order.
	 * @return string Localized datetime for display or empty.
	 */
	public static function get_marked_at_display( $order ) {
		$at = self::get_marked_at_mysql( $order );
		if ( '' === $at ) {
			return '';
		}
		$dt = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $at, wp_timezone() );

		return $dt
			? wp_date(
				get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
				$dt->getTimestamp()
			)
			: '';
	}
}
