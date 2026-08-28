<?php
/**
 * CLI smoke tests for Affiliate Coupon Tracker (WordPress + WooCommerce).
 *
 * Usage (Docker):
 *   docker exec molecule_test2_wp php /var/www/html/wp-content/plugins/affiliate-coupon-tracker/scripts/smoke-test.php
 *
 * @package AffiliateCouponTracker
 */

if ( php_sapi_name() !== 'cli' ) {
	exit( 1 );
}

$wp_load = dirname( __DIR__, 4 ) . '/wp-load.php';
if ( ! is_readable( $wp_load ) ) {
	fwrite( STDERR, "Cannot read wp-load.php at {$wp_load}\n" );
	exit( 1 );
}

require $wp_load;

/**
 * @param bool   $cond Condition.
 * @param string $msg  Message on failure.
 */
function act_assert( $cond, $msg ) {
	if ( ! $cond ) {
		fwrite( STDERR, "ASSERT: {$msg}\n" );
		exit( 1 );
	}
}

// Suppress user-registration emails during the run.
add_filter( 'wp_send_new_user_notification_to_user', '__return_false' );
add_filter( 'wp_send_new_user_notification_to_admin', '__return_false' );
add_filter( 'woocommerce_email_enabled', '__return_false', 9999 );

if ( ! class_exists( 'WooCommerce' ) ) {
	fwrite( STDERR, "WooCommerce is not active.\n" );
	exit( 1 );
}

$suffix = (string) wp_rand( 100000, 999999 );
$ts     = (string) time();

$created = array(
	'users'    => array(),
	'posts'    => array(),
	'orders'   => array(),
);

register_shutdown_function(
	static function () use ( &$created ) {
		foreach ( $created['orders'] as $oid ) {
			$o = wc_get_order( $oid );
			if ( $o ) {
				$o->delete( true );
			}
		}
		foreach ( $created['posts'] as $pid ) {
			wp_delete_post( (int) $pid, true );
		}
		foreach ( array_reverse( $created['users'] ) as $uid ) {
			if ( is_numeric( $uid ) && (int) $uid > 1 ) {
				require_once ABSPATH . 'wp-admin/includes/user.php';
				wp_delete_user( (int) $uid );
			}
		}
	}
);

$coupon_code = 'actsmk' . $suffix;
$coupon      = new WC_Coupon();
$coupon->set_code( $coupon_code );
$coupon->set_discount_type( 'fixed_cart' );
$coupon->set_amount( '5' );
$coupon->set_individual_use( true );
$coupon_id = $coupon->save();
act_assert( $coupon_id > 0, 'Failed to create coupon' );
$created['posts'][] = $coupon_id;

$aff_short = 'SMK-' . $ts;
update_post_meta( $coupon_id, ACT_Coupon_Affiliate_Fields::META_AFFILIATE_ID, $aff_short );
update_post_meta( $coupon_id, ACT_Coupon_Affiliate_Fields::META_AFFILIATE_NAME, 'Smoke Partner ' . $suffix );

$affiliate_key = 'id:' . $aff_short;

// ---- Test: referral URL
$url = ACT_Customer_Affiliate::build_referral_url( $affiliate_key );
act_assert( $url !== '', 'build_referral_url returned empty string' );
act_assert( strpos( $url, 'act_ref=' ) !== false, 'Referral URL missing act_ref' );
$query_q = wp_parse_url( $url, PHP_URL_QUERY );
parse_str( is_string( $query_q ) ? $query_q : '', $qv );
act_assert( isset( $qv['act_ref'] ) && rawurldecode( (string) $qv['act_ref'] ) === $affiliate_key, 'Query param act_ref does not decode to affiliate key' );

$repository = new ACT_Order_Report_Repository();
$opts       = $repository->get_affiliate_options();
act_assert( isset( $opts[ $affiliate_key ] ), 'Affiliate key missing from get_affiliate_options()' );
act_assert( $repository->get_affiliate_label_for_key( $affiliate_key ) !== '', 'get_affiliate_label_for_key empty for valid key' );
act_assert( $repository->get_affiliate_key_for_coupon_id( $coupon_id ) === $affiliate_key, 'Coupon ID should map to affiliate key' );

// ---- Product
$product = new WC_Product_Simple();
$product->set_name( 'ACT Smoke Product ' . $suffix );
$product->set_regular_price( '100' );
$product->set_catalog_visibility( 'hidden' );
$product_id = $product->save();
act_assert( $product_id > 0, 'Failed to create product' );
$created['posts'][] = $product_id;

// ---- Test: registration keeps referral cookie; profile link waits for paid order
$login_cookie  = 'act_smoke_cookie_' . $suffix;
$email_cookie  = $login_cookie . '@example.invalid';
$_COOKIE[ ACT_Customer_Affiliate::COOKIE_NAME ] = $affiliate_key;

$uid_cookie = wp_insert_user(
	array(
		'user_login' => $login_cookie,
		'user_pass'  => wp_generate_password( 24 ),
		'user_email' => $email_cookie,
		'role'       => 'customer',
	)
);
act_assert( ! is_wp_error( $uid_cookie ), 'wp_insert_user (cookie path) failed: ' . ( is_wp_error( $uid_cookie ) ? $uid_cookie->get_error_message() : '' ) );
$created['users'][] = (int) $uid_cookie;

$meta_after_reg = (string) get_user_meta( (int) $uid_cookie, ACT_Customer_Affiliate::META_KEY, true );
act_assert( '' === $meta_after_reg, 'Registration alone must not link customer affiliate meta' );
act_assert(
	isset( $_COOKIE[ ACT_Customer_Affiliate::COOKIE_NAME ] ) && sanitize_text_field( wp_unslash( $_COOKIE[ ACT_Customer_Affiliate::COOKIE_NAME ] ) ) === $affiliate_key,
	'Referral cookie should persist after registration until a qualifying purchase'
);

// ---- Test: checkout hook marries user to affiliate from coupon
$login_marry  = 'act_smoke_marry_' . $suffix;
$email_marry  = $login_marry . '@example.invalid';
$uid_marry    = wp_insert_user(
	array(
		'user_login' => $login_marry,
		'user_pass'  => wp_generate_password( 24 ),
		'user_email' => $email_marry,
		'role'       => 'customer',
	)
);
act_assert( ! is_wp_error( $uid_marry ), 'wp_insert_user (marry path) failed' );
$created['users'][] = (int) $uid_marry;
act_assert( get_user_meta( (int) $uid_marry, ACT_Customer_Affiliate::META_KEY, true ) === '', 'New user should start without affiliate meta' );

$order_coupon = wc_create_order( array( 'customer_id' => (int) $uid_marry ) );
act_assert( $order_coupon instanceof WC_Order, 'wc_create_order failed' );
$order_coupon->add_product( wc_get_product( $product_id ), 1 );
$applied = $order_coupon->apply_coupon( $coupon_code );
act_assert( ! is_wp_error( $applied ), 'apply_coupon failed: ' . ( is_wp_error( $applied ) ? $applied->get_error_message() : '' ) );
$order_coupon->calculate_totals();
$order_coupon->set_status( 'pending' );
$order_coupon->save();
$created['orders'][] = $order_coupon->get_id();

// Snapshot + coupon meta on checkout processed; profile marriage waits for processing (payment-confirmed-ish).
do_action( 'woocommerce_checkout_order_processed', $order_coupon->get_id(), array(), $order_coupon );

$meta_before = (string) get_user_meta( (int) $uid_marry, ACT_Customer_Affiliate::META_KEY, true );
act_assert(
	'' === $meta_before,
	'checkout_order_processed must not marry before order moves to processing'
);

$order_coupon->update_status( 'processing' );

$meta_marry = (string) get_user_meta( (int) $uid_marry, ACT_Customer_Affiliate::META_KEY, true );
act_assert(
	$meta_marry === $affiliate_key,
	'woocommerce_order_status_processing should marry customer from affiliate coupon'
);

// ---- Test: referral cookie on checkout without coupon still snapshots for reporting
$_COOKIE[ ACT_Customer_Affiliate::COOKIE_NAME ] = $affiliate_key;

$order_ref_cookie = wc_create_order( array( 'customer_id' => (int) $uid_cookie ) );
act_assert( $order_ref_cookie instanceof WC_Order, 'wc_create_order (referral cookie) failed' );
$order_ref_cookie->add_product( wc_get_product( $product_id ), 1 );
$order_ref_cookie->calculate_totals();
$order_ref_cookie->set_status( 'pending' );
$order_ref_cookie->save();
$created['orders'][] = $order_ref_cookie->get_id();

do_action( 'woocommerce_checkout_order_processed', $order_ref_cookie->get_id(), array(), $order_ref_cookie );

$order_ref_cookie = wc_get_order( $order_ref_cookie->get_id() );
act_assert(
	trim( (string) $order_ref_cookie->get_meta( ACT_Customer_Affiliate::ORDER_REFERRAL_AFFILIATE_KEY_META ) ) === $affiliate_key,
	'Checkout should persist referral cookie affiliate key on order'
);
act_assert(
	trim( (string) $order_ref_cookie->get_meta( ACT_Customer_Affiliate::ORDER_LINKED_SNAPSHOT_META ) ) === $affiliate_key,
	'Checkout should snapshot referral cookie affiliate on order'
);

$order_ref_cookie->update_status( 'processing' );
act_assert(
	(string) get_user_meta( (int) $uid_cookie, ACT_Customer_Affiliate::META_KEY, true ) === $affiliate_key,
	'Processing should marry customer from referral cookie when no coupon was used'
);

$month_ref  = wp_date( 'Y-m' );
$report_ref = $repository->build_monthly_report( $month_ref, $affiliate_key );
act_assert(
	in_array( $order_ref_cookie->get_id(), wp_list_pluck( $report_ref['rows'], 'order_id' ), true ),
	'Report should include referral-cookie order without coupon'
);

// ---- Test: linked customer order without coupon appears in monthly report
$uid_linked = (int) $uid_cookie;
update_user_meta( $uid_linked, ACT_Customer_Affiliate::META_KEY, $affiliate_key );
$order_bare = wc_create_order( array( 'customer_id' => $uid_linked ) );
act_assert( $order_bare instanceof WC_Order, 'wc_create_order (bare) failed' );
$order_bare->add_product( wc_get_product( $product_id ), 1 );
$order_bare->calculate_totals();
$order_bare->set_status( 'processing' );
$order_bare->save();
$created['orders'][] = $order_bare->get_id();

do_action( 'woocommerce_checkout_order_processed', $order_bare->get_id(), array(), $order_bare );

$bare_refreshed = wc_get_order( $order_bare->get_id() );
act_assert(
	trim( (string) $bare_refreshed->get_meta( ACT_Customer_Affiliate::ORDER_LINKED_SNAPSHOT_META ) ) === $affiliate_key,
	'Checkout hook should freeze linked affiliate snapshot on order'
);

$month  = wp_date( 'Y-m' );
$report = $repository->build_monthly_report( $month, $affiliate_key );
$order_numbers = wp_list_pluck( $report['rows'], 'order_id' );
act_assert( in_array( $order_bare->get_id(), $order_numbers, true ), 'Report should include customer-linked order without coupon' );

delete_user_meta( $uid_linked, ACT_Customer_Affiliate::META_KEY );
act_assert( get_user_meta( $uid_linked, ACT_Customer_Affiliate::META_KEY, true ) === '', 'Smoke test unlink should remove live customer affiliate meta' );

$report_after_unlink = $repository->build_monthly_report( $month, $affiliate_key );
$order_numbers_after = wp_list_pluck( $report_after_unlink['rows'], 'order_id' );
act_assert(
	in_array( $order_bare->get_id(), $order_numbers_after, true ),
	'Report must still attribute order via frozen snapshot after profile unlink'
);

$found_row = null;
foreach ( $report_after_unlink['rows'] as $row ) {
	if ( (int) $row['order_id'] === (int) $order_bare->get_id() ) {
		$found_row = $row;
		break;
	}
}
act_assert( is_array( $found_row ), 'Could not find bare order row' );
act_assert( strpos( (string) $found_row['coupon_codes'], 'Customer profile' ) !== false, 'Bare order should show customer profile attribution in coupon column' );

// ---- Test: GET act_ref stores cookie (simulate storefront)
unset( $_GET[ ACT_Customer_Affiliate::QUERY_PARAM ], $_COOKIE[ ACT_Customer_Affiliate::COOKIE_NAME ] );
$_GET[ ACT_Customer_Affiliate::QUERY_PARAM ] = rawurlencode( $affiliate_key );

$repository2   = new ACT_Order_Report_Repository();
$act_customer2 = new ACT_Customer_Affiliate( $repository2 );
$act_customer2->maybe_store_referral_cookie();
act_assert( isset( $_COOKIE[ ACT_Customer_Affiliate::COOKIE_NAME ] ), 'maybe_store_referral_cookie should set superglobal for tests' );
act_assert( sanitize_text_field( wp_unslash( $_COOKIE[ ACT_Customer_Affiliate::COOKIE_NAME ] ) ) === $affiliate_key, 'Cookie value should match affiliate key' );
unset( $_GET[ ACT_Customer_Affiliate::QUERY_PARAM ], $_COOKIE[ ACT_Customer_Affiliate::COOKIE_NAME ] );

fwrite( STDOUT, "All Affiliate Coupon Tracker smoke checks passed.\n" );
fwrite( STDOUT, "  Affiliate key: {$affiliate_key}\n" );
fwrite( STDOUT, "  Coupon code:   {$coupon_code}\n" );
exit( 0 );
