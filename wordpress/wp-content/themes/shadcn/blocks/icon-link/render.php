<?php
/**
 * Render callback for molecule/icon-link block.
 *
 * @var array  $attributes  Block attributes (type, href).
 * @var string $content     Inner block content (unused — no InnerBlocks).
 * @var object $block       Block object.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$type = isset( $attributes['type'] ) ? $attributes['type'] : 'search';
$type = in_array( $type, array( 'search', 'account', 'cart' ), true ) ? $type : 'search';

$default_hrefs = array(
	'search'  => home_url( '/?s=' ),
	'account' => function_exists( 'wc_get_page_permalink' ) ? ( wc_get_page_permalink( 'myaccount' ) ?: home_url( '/my-account' ) ) : home_url( '/my-account' ),
	'cart'    => function_exists( 'wc_get_cart_url' ) ? ( wc_get_cart_url() ?: home_url( '/cart' ) ) : home_url( '/cart' ),
);

$href = ! empty( $attributes['href'] ) ? $attributes['href'] : $default_hrefs[ $type ];

$labels = array(
	'search'  => __( 'Search', 'shadcn' ),
	'account' => __( 'Account', 'shadcn' ),
	'cart'    => __( 'Cart', 'shadcn' ),
);

$classes = 'molecule-icon-link molecule-header-' . $type;
if ( 'cart' === $type ) {
	$classes .= ' molecule-cart-icon-link';
}

$cart_count = 0;
if ( 'cart' === $type && function_exists( 'WC' ) && WC()->cart ) {
	$cart_count = (int) WC()->cart->get_cart_contents_count();
}

$svgs = array(
	'search'  => '<svg role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">'
		. '<circle cx="11" cy="10" r="7" fill="none" stroke="currentColor"></circle>'
		. '<path d="m16 15 3 3" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>'
		. '</svg>',
	'account' => '<svg role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">'
		. '<circle cx="11" cy="7" r="4" fill="none" stroke="currentColor"></circle>'
		. '<path d="M3.5 19c1.421-2.974 4.247-5 7.5-5s6.079 2.026 7.5 5" fill="none" stroke="currentColor" stroke-linecap="round"></path>'
		. '</svg>',
	'cart'    => '<svg role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">'
		. '<path d="M11 7H3.577A2 2 0 0 0 1.64 9.497l2.051 8A2 2 0 0 0 5.63 19H16.37a2 2 0 0 0 1.937-1.503l2.052-8A2 2 0 0 0 18.422 7H11Zm0 0V1" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>'
		. '</svg>',
);
?>
<a class="<?php echo esc_attr( $classes ); ?>" href="<?php echo esc_url( $href ); ?>" aria-label="<?php echo esc_attr( $labels[ $type ] ); ?>">
	<?php echo $svgs[ $type ]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup ?>
	<?php if ( 'cart' === $type ) : ?>
		<span class="molecule-cart-count" aria-hidden="true"><?php echo esc_html( $cart_count ); ?></span>
	<?php endif; ?>
</a>
