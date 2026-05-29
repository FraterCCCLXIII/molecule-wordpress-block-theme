<?php
/**
 * Customer ↔ affiliate binding: referral links, coupons, and admin profile.
 *
 * @package AffiliateCouponTracker
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ACT_Customer_Affiliate
 */
class ACT_Customer_Affiliate {

	const META_KEY         = '_act_customer_affiliate_key';
	const COOKIE_NAME      = 'act_affiliate_ref';
	const QUERY_PARAM      = 'act_ref';
	const NONCE_ACTION     = 'act_save_customer_affiliate';
	const NONCE_NAME       = 'act_customer_affiliate_nonce';
	const PROFILE_FIELD_ID = 'act_customer_affiliate_key';

	/**
	 * Order meta: affiliate key the customer was linked to when this order was placed (immutable for reporting).
	 */
	const ORDER_LINKED_SNAPSHOT_META = '_act_order_linked_affiliate_at_purchase';

	/**
	 * Order meta: affiliate key from referral coupon usage (for reporting until profile linkage runs).
	 * Optional; persisted when deferring marriage until processing.
	 */
	const ORDER_AFFILIATE_KEY_FROM_COUPON_META = '_act_order_affiliate_key_from_coupon';

	/** Order meta: affiliate key captured from referral cookie at checkout (when coupon was not applied). */
	const ORDER_REFERRAL_AFFILIATE_KEY_META = '_act_order_referral_affiliate_key';

	/** Order meta: once post-payment affiliate marriage logic has run for this order. */
	const ORDER_POST_PAYMENT_MARRIAGE_META = '_act_order_post_payment_marriage_done';

	/**
	 * Build the storefront referral URL for a canonical affiliate key.
	 *
	 * @param string $affiliate_key Key such as `id:AFF-123` or `name:Partner`.
	 * @return string Full URL, or empty if the key is blank.
	 */
	public static function build_referral_url( $affiliate_key ) {
		$key = is_string( $affiliate_key ) ? trim( $affiliate_key ) : '';
		if ( '' === $key ) {
			return '';
		}

		return add_query_arg( self::QUERY_PARAM, $key, home_url( '/' ) );
	}

	/**
	 * @var ACT_Order_Report_Repository
	 */
	private $repository;

	/**
	 * @param ACT_Order_Report_Repository $repository Report / resolver.
	 */
	public function __construct( ACT_Order_Report_Repository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'template_redirect', array( $this, 'maybe_store_referral_cookie' ), 1 );
		add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'maybe_apply_referral_coupon_from_cookie' ), 99 );
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'maybe_apply_referral_coupon_from_cookie' ), 5 );
		// Classic checkout: persist snapshot from coupon/profile; marry profile once order is paid-ish (processing+).
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'prepare_order_coupon_affiliate_meta' ), 15, 3 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'persist_order_linked_affiliate_snapshot' ), 30, 3 );
		add_action( 'woocommerce_order_status_processing', array( $this, 'marry_logged_in_customer_from_order_after_payment' ), 20, 3 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'marry_logged_in_customer_from_order_after_payment' ), 20, 3 );
		add_action( 'woocommerce_payment_complete', array( $this, 'marry_logged_in_customer_from_order_on_payment_complete' ), 20, 1 );
		// Block / Store API checkout does not fire woocommerce_checkout_order_processed — use Woo's Store API hook instead.
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'handle_store_api_checkout_order_processed' ), 10, 1 );

		add_action( 'show_user_profile', array( $this, 'render_user_profile_fields' ) );
		add_action( 'edit_user_profile', array( $this, 'render_user_profile_fields' ) );
		add_action( 'personal_options_update', array( $this, 'save_user_profile_fields' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_user_profile_fields' ) );
	}

	/**
	 * Store valid affiliate ref from query string in a cookie.
	 *
	 * @return void
	 */
	public function maybe_store_referral_cookie() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		if ( ! isset( $_GET[ self::QUERY_PARAM ] ) ) {
			return;
		}

		$raw = wp_unslash( $_GET[ self::QUERY_PARAM ] );
		$raw = is_string( $raw ) ? $raw : '';

		$key = $this->normalize_incoming_ref_key( $raw );
		if ( '' === $key ) {
			return;
		}

		$options = $this->repository->get_affiliate_options();
		if ( ! isset( $options[ $key ] ) ) {
			return;
		}

		$ttl = (int) apply_filters( 'act_affiliate_ref_cookie_ttl', ACT_AFFILIATE_REF_COOKIE_TTL );
		setcookie(
			self::COOKIE_NAME,
			$key,
			time() + $ttl,
			COOKIEPATH ? COOKIEPATH : '/',
			COOKIE_DOMAIN,
			is_ssl(),
			true
		);

		$_COOKIE[ self::COOKIE_NAME ] = $key;

		$this->maybe_apply_referral_coupon_from_cookie();
	}

	/**
	 * Apply affiliate coupon automatically when a valid referral cookie exists and the cart has no conflicting profile link.
	 *
	 * @return void
	 */
	public function maybe_apply_referral_coupon_from_cookie() {
		static $applying = false;

		if ( $applying ) {
			return;
		}

		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		if ( wp_doing_cron() ) {
			return;
		}

		if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
			return;
		}

		$ref_key = $this->get_valid_referral_key_from_cookie();
		if ( '' === $ref_key ) {
			return;
		}

		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			if ( ! $user_id ) {
				return;
			}

			$existing_profile = trim( (string) get_user_meta( $user_id, self::META_KEY, true ) );
			if ( '' !== $existing_profile && $existing_profile !== $ref_key ) {
				return;
			}
		}

		$code = $this->repository->find_coupon_code_for_affiliate_key( $ref_key );
		if ( '' === $code ) {
			return;
		}

		if ( WC()->cart->has_discount( $code ) ) {
			return;
		}

		$applying = true;
		WC()->cart->apply_coupon( $code );
		$applying = false;
	}

	/**
	 * Valid affiliate key from the referral cookie, or empty if missing/unknown.
	 *
	 * @return string
	 */
	private function get_valid_referral_key_from_cookie() {
		if ( empty( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return '';
		}

		$ref_key = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );
		if ( '' === $ref_key ) {
			return '';
		}

		$options = $this->repository->get_affiliate_options();
		if ( ! isset( $options[ $ref_key ] ) ) {
			return '';
		}

		return $ref_key;
	}

	/**
	 * @deprecated 1.2.0 Profile linkage is deferred until payment is confirmed on the customer's first qualifying order.
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function assign_affiliate_from_cookie_on_register( $user_id ) {
		unset( $user_id );
	}

	/**
	 * Block / Store API checkout: defer profile marriage until processing; freeze snapshot from coupon/profile.
	 *
	 * @param WC_Order $order Order instance.
	 * @return void
	 */
	public function handle_store_api_checkout_order_processed( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$this->prepare_order_coupon_affiliate_meta_for_order( $order );
		$this->persist_linked_affiliate_snapshot_from_order_customer( $order );
	}

	/**
	 * Persist coupon-backed affiliate meta on order (before snapshot).
	 *
	 * @param int          $order_id   Order ID.
	 * @param array<mixed> $posted_data Posted data.
	 * @param WC_Order     $order      Order object.
	 * @return void
	 */
	public function prepare_order_coupon_affiliate_meta( $order_id, $posted_data, $order ) {
		unset( $posted_data );

		$resolved = self::resolve_checkout_order( $order_id, $order );
		if ( null === $resolved ) {
			return;
		}

		$this->prepare_order_coupon_affiliate_meta_for_order( $resolved );
	}

	/**
	 * Classic checkout handler: freeze affiliate attribution snapshot (coupon-backed first, then profile).
	 *
	 * Changing or removing the profile link later does not alter snapshots on past orders.
	 *
	 * @param int          $order_id   Order ID.
	 * @param array<mixed> $posted_data Posted data.
	 * @param WC_Order     $order      Order object.
	 * @return void
	 */
	public function persist_order_linked_affiliate_snapshot( $order_id, $posted_data, $order ) {
		unset( $posted_data );

		$resolved = self::resolve_checkout_order( $order_id, $order );
		if ( null === $resolved ) {
			return;
		}

		$this->persist_linked_affiliate_snapshot_from_order_customer( $resolved );
	}

	/**
	 * Store `_act_order_affiliate_key_from_coupon` from the order's affiliate-backed coupons (if any).
	 *
	 * @param WC_Order $order Order.
	 * @return void
	 */
	private function prepare_order_coupon_affiliate_meta_for_order( WC_Order $order ) {
		$coupon_key = $this->repository->get_first_affiliate_key_from_order_coupons( $order );

		if ( '' !== $coupon_key ) {
			$order->update_meta_data( self::ORDER_AFFILIATE_KEY_FROM_COUPON_META, $coupon_key );
		} else {
			$order->delete_meta_data( self::ORDER_AFFILIATE_KEY_FROM_COUPON_META );
		}

		$referral_key = $this->get_valid_referral_key_from_cookie();
		if ( '' !== $referral_key ) {
			$order->update_meta_data( self::ORDER_REFERRAL_AFFILIATE_KEY_META, $referral_key );
		} else {
			$order->delete_meta_data( self::ORDER_REFERRAL_AFFILIATE_KEY_META );
		}

		$order->save();
	}

	/**
	 * Write/remove order `_act_order_linked_affiliate_at_purchase`: coupon attribution first (for reporting before profile link), else profile snapshot.
	 *
	 * @param WC_Order $order Order.
	 * @return void
	 */
	private function persist_linked_affiliate_snapshot_from_order_customer( WC_Order $order ) {
		$snapshot_key = $this->resolve_order_affiliate_attribution_key( $order );

		if ( '' !== $snapshot_key ) {
			$order->update_meta_data( self::ORDER_LINKED_SNAPSHOT_META, $snapshot_key );
		} else {
			$order->delete_meta_data( self::ORDER_LINKED_SNAPSHOT_META );
		}

		$order->save();
	}

	/**
	 * Resolve affiliate attribution for an order: coupon first, then referral cookie, then linked customer profile.
	 *
	 * @param WC_Order $order Order.
	 * @return string Canonical affiliate key or empty string.
	 */
	private function resolve_order_affiliate_attribution_key( WC_Order $order ) {
		$coupon_key = $this->repository->get_first_affiliate_key_from_order_coupons( $order );
		if ( '' === $coupon_key ) {
			$coupon_key = trim( (string) $order->get_meta( self::ORDER_AFFILIATE_KEY_FROM_COUPON_META ) );
		}

		if ( '' !== $coupon_key ) {
			return $coupon_key;
		}

		$referral_key = trim( (string) $order->get_meta( self::ORDER_REFERRAL_AFFILIATE_KEY_META ) );
		if ( '' !== $referral_key ) {
			return $referral_key;
		}

		$user_id = (int) $order->get_user_id();
		if ( ! $user_id ) {
			return '';
		}

		return trim( (string) get_user_meta( $user_id, self::META_KEY, true ) );
	}

	/**
	 * WooCommerce payment_complete passes only the order ID.
	 *
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public function marry_logged_in_customer_from_order_on_payment_complete( $order_id ) {
		$this->marry_logged_in_customer_from_order_after_payment( (int) $order_id, null, null );
	}

	/**
	 * After payment is confirmed (processing or completed), link the customer profile from the affiliate coupon once.
	 *
	 * WooCommerce fires `woocommerce_order_status_{processing|completed}` with order id and order instance.
	 *
	 * @param int              $order_id           Order ID.
	 * @param WC_Order|null    $order              Order instance (preferred).
	 * @param array<mixed>|null $status_transition Optional transition payload.
	 * @return void
	 */
	public function marry_logged_in_customer_from_order_after_payment( $order_id, $order = null, $status_transition = null ) {
		unset( $status_transition );

		if ( ! $order instanceof WC_Order ) {
			$order_id = absint( $order_id );
			if ( ! $order_id ) {
				return;
			}
			$fetched = wc_get_order( $order_id );
			$order   = $fetched instanceof WC_Order ? $fetched : null;
		}

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		if ( '1' === (string) $order->get_meta( self::ORDER_POST_PAYMENT_MARRIAGE_META ) ) {
			return;
		}

		$user_id = (int) $order->get_user_id();
		if ( ! $user_id ) {
			return;
		}

		$coupon_key = trim( (string) $order->get_meta( self::ORDER_AFFILIATE_KEY_FROM_COUPON_META ) );
		if ( '' === $coupon_key ) {
			$coupon_key = $this->repository->get_first_affiliate_key_from_order_coupons( $order );
		}

		$affiliate_key = $coupon_key;
		if ( '' === $affiliate_key ) {
			$affiliate_key = trim( (string) $order->get_meta( self::ORDER_REFERRAL_AFFILIATE_KEY_META ) );
		}

		if ( '' === $affiliate_key ) {
			return;
		}

		$existing = trim( (string) get_user_meta( $user_id, self::META_KEY, true ) );

		if ( '' === $existing || $existing === $affiliate_key ) {
			if ( '' === $existing ) {
				$this->set_user_affiliate_key( $user_id, $affiliate_key );
				$this->clear_referral_cookie();
			}
		}

		$order->update_meta_data( self::ORDER_POST_PAYMENT_MARRIAGE_META, '1' );
		$order->save();
	}

	/**
	 * Normalize order from classic checkout callback args.
	 *
	 * @param int             $order_id Order ID from hook.
	 * @param WC_Order|false|null $order   Order passed to hook if any.
	 * @return WC_Order|null WooCommerce order or null.
	 */
	private static function resolve_checkout_order( $order_id, $order ) {
		if ( $order instanceof WC_Order ) {
			return $order;
		}

		$order_id = absint( $order_id );
		if ( ! $order_id ) {
			return null;
		}

		$fetched = wc_get_order( $order_id );

		return $fetched instanceof WC_Order ? $fetched : null;
	}

	/**
	 * Admin / profile: show affiliate assignment.
	 *
	 * @param WP_User $user User being edited.
	 * @return void
	 */
	public function render_user_profile_fields( $user ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( ! $user instanceof WP_User ) {
			return;
		}

		$options  = $this->repository->get_affiliate_options();
		$current  = (string) get_user_meta( $user->ID, self::META_KEY, true );
		$headline = __( 'Affiliate assignment', 'affiliate-coupon-tracker' );

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		?>
		<h2 id="act-affiliate-profile"><?php echo esc_html( $headline ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="<?php echo esc_attr( self::PROFILE_FIELD_ID ); ?>"><?php esc_html_e( 'Linked affiliate', 'affiliate-coupon-tracker' ); ?></label>
				</th>
				<td>
					<select name="<?php echo esc_attr( self::PROFILE_FIELD_ID ); ?>" id="<?php echo esc_attr( self::PROFILE_FIELD_ID ); ?>">
						<option value=""><?php esc_html_e( '— None —', 'affiliate-coupon-tracker' ); ?></option>
						<?php foreach ( $options as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current, $key ); ?>>
								<?php echo esc_html( $label . ' (' . $key . ')' ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description">
						<?php esc_html_e( 'Current linkage controls future checkout rules; past orders keep the affiliate frozen on each order when it was placed (see Affiliate Coupons report).', 'affiliate-coupon-tracker' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save profile affiliate field.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function save_user_profile_fields( $user_id ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$nonce = isset( $_POST[ self::NONCE_NAME ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::PROFILE_FIELD_ID ] ) ) {
			return;
		}

		$selected = sanitize_text_field( wp_unslash( $_POST[ self::PROFILE_FIELD_ID ] ) );
		if ( '' === $selected ) {
			delete_user_meta( $user_id, self::META_KEY );
			return;
		}

		$options = $this->repository->get_affiliate_options();
		if ( ! isset( $options[ $selected ] ) ) {
			return;
		}

		$this->set_user_affiliate_key( (int) $user_id, $selected );
	}

	/**
	 * Persist user meta.
	 *
	 * @param int    $user_id WP user ID.
	 * @param string $key     Canonical affiliate key.
	 * @return void
	 */
	public function set_user_affiliate_key( $user_id, $key ) {
		$user_id = absint( $user_id );
		$key     = is_string( $key ) ? trim( $key ) : '';

		if ( ! $user_id || '' === $key ) {
			return;
		}

		update_user_meta( $user_id, self::META_KEY, $key );
	}

	/**
	 * Turn query value into canonical key (may be URL-encoded id:key).
	 *
	 * @param string $raw Raw GET value.
	 * @return string
	 */
	private function normalize_incoming_ref_key( $raw ) {
		$raw = trim( (string) $raw );
		if ( '' === $raw ) {
			return '';
		}

		$decoded = rawurldecode( $raw );
		$decoded = trim( $decoded );

		return sanitize_text_field( $decoded );
	}

	/**
	 * Clear referral cookie after successful profile linkage.
	 *
	 * @return void
	 */
	private function clear_referral_cookie() {
		if ( headers_sent() ) {
			unset( $_COOKIE[ self::COOKIE_NAME ] );
			return;
		}

		setcookie(
			self::COOKIE_NAME,
			'',
			time() - 3600,
			COOKIEPATH ? COOKIEPATH : '/',
			COOKIE_DOMAIN,
			is_ssl(),
			true
		);

		unset( $_COOKIE[ self::COOKIE_NAME ] );
	}
}
