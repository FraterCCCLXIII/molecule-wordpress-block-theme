<?php
/**
 * Render callback for molecule/mobile-drawer block.
 *
 * @var string  $content  Rendered InnerBlocks (navigation link markup).
 * @var array   $block    Block object.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$cart_count = 0;
if ( function_exists( 'WC' ) && WC()->cart ) {
	$cart_count = (int) WC()->cart->get_cart_contents_count();
}
?>
<button
	class="molecule-mobile-menu-button"
	type="button"
	aria-label="<?php esc_attr_e( 'Menu', 'shadcn' ); ?>"
	aria-controls="molecule-mobile-drawer"
	aria-expanded="false"
>
	<svg role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">
		<path d="M1 5h20M1 11h20M1 17h20" stroke="currentColor" stroke-linecap="round"></path>
	</svg>
</button>

<div class="molecule-mobile-drawer-backdrop" hidden></div>

<aside id="molecule-mobile-drawer" class="molecule-mobile-drawer" aria-hidden="true">
	<div class="molecule-mobile-drawer-header">
		<button class="molecule-mobile-drawer-close" type="button" aria-label="<?php esc_attr_e( 'Close menu', 'shadcn' ); ?>">
			<svg role="presentation" stroke-width="2" focusable="false" width="24" height="24" viewBox="0 0 24 24" aria-hidden="true">
				<line x1="18" y1="6" x2="6" y2="18" stroke="currentColor" stroke-linecap="round"></line>
				<line x1="6" y1="6" x2="18" y2="18" stroke="currentColor" stroke-linecap="round"></line>
			</svg>
		</button>
		<div class="molecule-mobile-drawer-quick-icons" aria-label="<?php esc_attr_e( 'Quick actions', 'shadcn' ); ?>">
			<a href="<?php echo esc_url( home_url( '/?s=' ) ); ?>" aria-label="<?php esc_attr_e( 'Search', 'shadcn' ); ?>">
				<svg role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">
					<circle cx="11" cy="10" r="7" fill="none" stroke="currentColor"></circle>
					<path d="m16 15 3 3" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>
				</svg>
			</a>
			<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ?: home_url( '/my-account' ) ); ?>" aria-label="<?php esc_attr_e( 'Account', 'shadcn' ); ?>">
				<svg role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">
					<circle cx="11" cy="7" r="4" fill="none" stroke="currentColor"></circle>
					<path d="M3.5 19c1.421-2.974 4.247-5 7.5-5s6.079 2.026 7.5 5" fill="none" stroke="currentColor" stroke-linecap="round"></path>
				</svg>
			</a>
			<a class="molecule-cart-icon-link" href="<?php echo esc_url( wc_get_cart_url() ?: home_url( '/cart' ) ); ?>" aria-label="<?php esc_attr_e( 'Cart', 'shadcn' ); ?>">
				<svg role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">
					<path d="M11 7H3.577A2 2 0 0 0 1.64 9.497l2.051 8A2 2 0 0 0 5.63 19H16.37a2 2 0 0 0 1.937-1.503l2.052-8A2 2 0 0 0 18.422 7H11Zm0 0V1" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>
				</svg>
				<span class="molecule-cart-count" aria-hidden="true"><?php echo esc_html( $cart_count ); ?></span>
			</a>
		</div>
	</div>
	<nav class="molecule-mobile-drawer-nav" aria-label="<?php esc_attr_e( 'Mobile Navigation', 'shadcn' ); ?>">
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered block content ?>
	</nav>
</aside>
