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
		add_action( 'user_register', array( $this, 'assign_affiliate_from_cookie_on_register' ), 20, 1 );
		// Classic shortcode checkout: marry coupon → user first, then freeze snapshot (so first purchase with affiliate coupon records that affiliate).
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'marry_customer_from_order_coupons' ), 15, 3 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'persist_order_linked_affiliate_snapshot' ), 30, 3 );
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
	}

	/**
	 * On registration, bind stored ref cookie to the new user.
	 *
	 * @param int $user_id User ID.
	 * @return void
	 */
	public function assign_affiliate_from_cookie_on_register( $user_id ) {
		$user_id = absint( $user_id );
		if ( ! $user_id ) {
			return;
		}

		if ( ! isset( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return;
		}

		$key = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) );
		if ( '' === $key ) {
			return;
		}

		$options = $this->repository->get_affiliate_options();
		if ( ! isset( $options[ $key ] ) ) {
			return;
		}

		$this->set_user_affiliate_key( $user_id, $key );

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

	/**
	 * Block / Store API checkout: replicate classic checkout affiliate logic (coupon marriage then snapshot).
	 *
	 * @param WC_Order $order Order instance.
	 * @return void
	 */
	public function handle_store_api_checkout_order_processed( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$this->maybe_marry_customer_from_order_coupons( $order );
		$this->persist_linked_affiliate_snapshot_from_order_customer( $order );
	}

	/**
	 * Classic checkout handler: freeze the customer's affiliate link onto the order for reporting.
	 *
	 * Runs after {@see marry_customer_from_order_coupons()} so the snapshot can include linkage just applied from a coupon on this order.
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
	 * Write/remove order meta `_act_order_linked_affiliate_at_purchase` from the placing customer's current affiliate meta.
	 *
	 * @param WC_Order $order Order.
	 * @return void
	 */
	private function persist_linked_affiliate_snapshot_from_order_customer( WC_Order $order ) {
		$user_id = (int) $order->get_user_id();
		if ( ! $user_id ) {
			return;
		}

		$key = trim( (string) get_user_meta( $user_id, self::META_KEY, true ) );

		if ( '' !== $key ) {
			$order->update_meta_data( self::ORDER_LINKED_SNAPSHOT_META, $key );
		} else {
			$order->delete_meta_data( self::ORDER_LINKED_SNAPSHOT_META );
		}

		$order->save();
	}

	/**
	 * First affiliate coupon on the order binds the customer for future orders when they are not already linked,
	 * or when they are linked to the same affiliate. A different affiliate never overwrites an existing link.
	 *
	 * @param int          $order_id   Order ID.
	 * @param array<mixed> $posted_data Posted checkout data.
	 * @param WC_Order     $order      Order object.
	 * @return void
	 */
	public function marry_customer_from_order_coupons( $order_id, $posted_data, $order ) {
		unset( $posted_data );

		$resolved = self::resolve_checkout_order( $order_id, $order );
		if ( null === $resolved ) {
			return;
		}

		$this->maybe_marry_customer_from_order_coupons( $resolved );
	}

	/**
	 * If the customer is not locked to another affiliate, link them from the first affiliate coupon on the order.
	 *
	 * @param WC_Order $order Order object.
	 * @return void
	 */
	private function maybe_marry_customer_from_order_coupons( WC_Order $order ) {
		$user_id = (int) $order->get_user_id();
		if ( ! $user_id ) {
			return;
		}

		$existing = trim( (string) get_user_meta( $user_id, self::META_KEY, true ) );

		foreach ( $order->get_coupon_codes() as $code ) {
			$coupon = new WC_Coupon( $code );
			if ( ! $coupon->get_id() ) {
				continue;
			}

			$key = $this->repository->get_affiliate_key_for_coupon_id( $coupon->get_id() );
			if ( '' !== $key ) {
				if ( '' === $existing || $existing === $key ) {
					$this->set_user_affiliate_key( $user_id, $key );
				}
				return;
			}
		}
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
}
