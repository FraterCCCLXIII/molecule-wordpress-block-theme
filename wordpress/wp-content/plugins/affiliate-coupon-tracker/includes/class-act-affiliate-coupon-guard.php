<?php
/**
 * Limit affiliate coupons per order and prevent conflicting referral coupons vs linked affiliate.
 *
 * @package AffiliateCouponTracker
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ACT_Affiliate_Coupon_Guard
 */
class ACT_Affiliate_Coupon_Guard {

	/**
	 * Custom message when this guard rejects a coupon (consumed by woocommerce_coupon_error).
	 *
	 * @var string
	 */
	private static $coupon_error_detail = '';

	/**
	 * @var ACT_Order_Report_Repository
	 */
	private $repository;

	/**
	 * @param ACT_Order_Report_Repository $repository Affiliate lookup.
	 */
	public function __construct( ACT_Order_Report_Repository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Register WooCommerce hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'woocommerce_coupon_is_valid', array( $this, 'filter_coupon_is_valid' ), 20, 3 );
		add_filter( 'woocommerce_coupon_error', array( $this, 'filter_coupon_error_message' ), 20, 3 );
	}

	/**
	 * Replace generic “coupon not valid” when this guard invalidated the coupon.
	 *
	 * @param string    $error_message Existing message.
	 * @param int       $error_code    Error code.
	 * @param WC_Coupon $coupon        Coupon instance.
	 * @return string
	 */
	public function filter_coupon_error_message( $error_message, $error_code, $coupon ) {
		unset( $coupon );

		if ( '' === self::$coupon_error_detail ) {
			return $error_message;
		}

		$detail = self::$coupon_error_detail;
		self::$coupon_error_detail = '';

		return $detail;
	}

	/**
	 * Enforce: at most one affiliate-backed coupon per cart/order; block coupons that conflict with customer’s linked affiliate.
	 *
	 * @param bool                    $valid     Whether the coupon is valid so far.
	 * @param WC_Coupon               $coupon    Coupon being validated.
	 * @param WC_Discounts|mixed|null $discounts Discounts context (cart or order).
	 * @return bool
	 */
	public function filter_coupon_is_valid( $valid, $coupon, $discounts ) {
		if ( ! $valid || ! $coupon instanceof WC_Coupon ) {
			return $valid;
		}

		self::$coupon_error_detail = '';

		$new_key = $this->repository->get_affiliate_key_for_coupon_id( $coupon->get_id() );
		if ( '' === $new_key ) {
			return $valid;
		}

		if ( ! is_object( $discounts ) || ! method_exists( $discounts, 'get_object' ) ) {
			return $valid;
		}

		$object    = $discounts->get_object();
		$candidate = wc_format_coupon_code( $coupon->get_code() );

		if ( $object instanceof WC_Cart ) {
			foreach ( $object->get_applied_coupons() as $code ) {
				if ( wc_format_coupon_code( $code ) === $candidate ) {
					continue;
				}
				$other = new WC_Coupon( $code );
				if ( ! $other->get_id() ) {
					continue;
				}
				$other_key = $this->repository->get_affiliate_key_for_coupon_id( $other->get_id() );
				if ( '' !== $other_key ) {
					self::$coupon_error_detail = __( 'Only one affiliate referral coupon can be used per order. Remove the other affiliate coupon first.', 'affiliate-coupon-tracker' );
					return false;
				}
			}
		} elseif ( $object instanceof WC_Order ) {
			foreach ( $object->get_coupon_codes() as $code ) {
				if ( wc_format_coupon_code( $code ) === $candidate ) {
					continue;
				}
				$other = new WC_Coupon( $code );
				if ( ! $other->get_id() ) {
					continue;
				}
				$other_key = $this->repository->get_affiliate_key_for_coupon_id( $other->get_id() );
				if ( '' !== $other_key ) {
					self::$coupon_error_detail = __( 'Only one affiliate referral coupon can be used per order. Remove the other affiliate coupon first.', 'affiliate-coupon-tracker' );
					return false;
				}
			}
		}

		$user_id = 0;
		if ( $object instanceof WC_Cart && is_user_logged_in() ) {
			$user_id = get_current_user_id();
		} elseif ( $object instanceof WC_Order ) {
			$user_id = (int) $object->get_user_id();
		}

		if ( $user_id > 0 ) {
			$linked = trim( (string) get_user_meta( $user_id, ACT_Customer_Affiliate::META_KEY, true ) );
			if ( '' !== $linked && $linked !== $new_key ) {
				self::$coupon_error_detail = __( 'Your account is already linked to a different affiliate partner, so this affiliate referral coupon cannot be applied.', 'affiliate-coupon-tracker' );
				return false;
			}
		}

		return $valid;
	}
}
