<?php
/**
 * One-off seed script for Affiliate Coupon Tracker testing.
 *
 * Usage:
 * php /var/www/html/wp-content/plugins/affiliate-coupon-tracker/scripts/seed-dummy-data.php
 */

define( 'WP_USE_THEMES', false );
require_once dirname( __DIR__, 4 ) . '/wp-load.php';

if ( ! function_exists( 'wc_create_order' ) ) {
	fwrite( STDERR, "WooCommerce is not active.\n" );
	exit( 1 );
}

/**
 * Get or create a simple product by SKU.
 *
 * @param string $name Product name.
 * @param string $sku Product SKU.
 * @param float  $price Product price.
 * @return int
 */
function act_seed_get_or_create_product( $name, $sku, $price ) {
	$product_id = wc_get_product_id_by_sku( $sku );
	if ( $product_id ) {
		return (int) $product_id;
	}

	$product = new WC_Product_Simple();
	$product->set_name( $name );
	$product->set_sku( $sku );
	$product->set_regular_price( (string) $price );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->set_manage_stock( false );
	$product->set_virtual( false );
	$product->set_tax_status( 'taxable' );

	return (int) $product->save();
}

/**
 * Get or create a coupon and attach affiliate metadata.
 *
 * @param string $code Coupon code.
 * @param string $affiliate_id Affiliate ID.
 * @param string $affiliate_name Affiliate name.
 * @return int
 */
function act_seed_get_or_create_coupon( $code, $affiliate_id, $affiliate_name ) {
	$coupon = new WC_Coupon( $code );

	if ( ! $coupon->get_id() ) {
		$coupon->set_code( $code );
		$coupon->set_discount_type( 'percent' );
		$coupon->set_amount( '10' );
		$coupon->set_individual_use( false );
		$coupon->set_usage_limit( 0 );
		$coupon->set_date_expires( null );
		$coupon->set_description( 'Seeded test coupon for affiliate report testing.' );
		$coupon->save();
	}

	$coupon_id = $coupon->get_id();
	update_post_meta( $coupon_id, '_act_affiliate_id', $affiliate_id );
	update_post_meta( $coupon_id, '_act_affiliate_name', $affiliate_name );

	return (int) $coupon_id;
}

/**
 * Create a test order for a coupon.
 *
 * @param string $coupon_code Coupon code.
 * @param array  $items Product item rows.
 * @param float  $shipping_total Shipping amount.
 * @param float  $tax_total Tax amount.
 * @param string $created_date Date string parseable by WC_DateTime.
 * @return int
 */
function act_seed_create_order( $coupon_code, $items, $shipping_total, $tax_total, $created_date ) {
	$order = wc_create_order();

	foreach ( $items as $item ) {
		$product = wc_get_product( $item['product_id'] );
		if ( ! $product ) {
			continue;
		}

		$order->add_product( $product, (int) $item['quantity'] );
	}

	$shipping_item = new WC_Order_Item_Shipping();
	$shipping_item->set_method_title( 'Flat rate' );
	$shipping_item->set_method_id( 'flat_rate:1' );
	$shipping_item->set_total( (float) $shipping_total );
	$order->add_item( $shipping_item );

	$order->set_address(
		array(
			'first_name' => 'Test',
			'last_name'  => 'Customer',
			'email'      => 'affiliate-test@example.com',
			'phone'      => '555-0100',
			'address_1'  => '123 Demo Ave',
			'city'       => 'Phoenix',
			'state'      => 'AZ',
			'postcode'   => '85001',
			'country'    => 'US',
		),
		'billing'
	);

	$order->set_address(
		array(
			'first_name' => 'Test',
			'last_name'  => 'Customer',
			'address_1'  => '123 Demo Ave',
			'city'       => 'Phoenix',
			'state'      => 'AZ',
			'postcode'   => '85001',
			'country'    => 'US',
		),
		'shipping'
	);

	$order->apply_coupon( $coupon_code );
	$order->calculate_totals( false );

	$tax_total = (float) $tax_total;
	$order->set_cart_tax( $tax_total );
	$order->set_shipping_tax( 0.0 );
	$order->set_total( (float) $order->get_total() + $tax_total );

	$order->set_status( 'completed' );
	$order->set_date_created( new WC_DateTime( $created_date ) );
	$order->set_date_paid( new WC_DateTime( $created_date ) );
	$order->save();

	return (int) $order->get_id();
}

$month_start = new DateTimeImmutable( 'first day of this month 10:00:00', wp_timezone() );
$month_mid   = $month_start->modify( '+9 days +3 hours' );
$month_late  = $month_start->modify( '+18 days +5 hours' );

$product_a = act_seed_get_or_create_product( 'Affiliate Test Product A', 'ACT-PROD-A', 45.00 );
$product_b = act_seed_get_or_create_product( 'Affiliate Test Product B', 'ACT-PROD-B', 32.00 );
$product_c = act_seed_get_or_create_product( 'Affiliate Test Product C', 'ACT-PROD-C', 19.00 );

act_seed_get_or_create_coupon( 'AFF-JANE-10', 'AFF-1001', 'Jane Partner' );
act_seed_get_or_create_coupon( 'AFF-BOB-10', 'AFF-1002', 'Bob Media' );

$created_orders = array();

$created_orders[] = act_seed_create_order(
	'AFF-JANE-10',
	array(
		array(
			'product_id' => $product_a,
			'quantity'   => 1,
		),
		array(
			'product_id' => $product_b,
			'quantity'   => 2,
		),
	),
	9.95,
	7.12,
	$month_start->format( 'Y-m-d H:i:s' )
);

$created_orders[] = act_seed_create_order(
	'AFF-BOB-10',
	array(
		array(
			'product_id' => $product_c,
			'quantity'   => 3,
		),
	),
	6.50,
	3.88,
	$month_mid->format( 'Y-m-d H:i:s' )
);

$created_orders[] = act_seed_create_order(
	'AFF-JANE-10',
	array(
		array(
			'product_id' => $product_a,
			'quantity'   => 2,
		),
		array(
			'product_id' => $product_c,
			'quantity'   => 1,
		),
	),
	12.00,
	6.44,
	$month_late->format( 'Y-m-d H:i:s' )
);

echo "Seed complete.\n";
echo 'Products: ' . implode( ', ', array( $product_a, $product_b, $product_c ) ) . "\n";
echo "Coupons: AFF-JANE-10, AFF-BOB-10\n";
echo 'Orders: ' . implode( ', ', $created_orders ) . "\n";
