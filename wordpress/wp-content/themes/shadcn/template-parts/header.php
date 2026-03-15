<?php
/**
 * Theme header template.
 *
 * Rendered by the pre_render_block filter in Core.php whenever WordPress
 * would render the core/template-part block with slug "header". All FSE
 * page templates continue to work unchanged — this file owns the output.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$logo            = get_custom_logo();
$home_url        = esc_url( home_url( '/' ) );
$search_url      = esc_url( home_url( '/?s=' ) );
$account_url     = function_exists( 'wc_get_page_permalink' )
	? esc_url( wc_get_page_permalink( 'myaccount' ) ?: home_url( '/my-account' ) )
	: esc_url( home_url( '/my-account' ) );
$cart_url        = function_exists( 'wc_get_cart_url' )
	? esc_url( wc_get_cart_url() ?: home_url( '/cart' ) )
	: esc_url( home_url( '/cart' ) );
$cart_count      = ( function_exists( 'WC' ) && WC()->cart )
	? (int) WC()->cart->get_cart_contents_count()
	: 0;
$free_shipping_min_amount = null;

if ( class_exists( 'WC_Shipping_Zones' ) ) {
	$shipping_methods = array();
	$zones            = \WC_Shipping_Zones::get_zones();

	foreach ( $zones as $zone ) {
		if ( empty( $zone['shipping_methods'] ) || ! is_array( $zone['shipping_methods'] ) ) {
			continue;
		}
		$shipping_methods = array_merge( $shipping_methods, $zone['shipping_methods'] );
	}

	$rest_of_world  = new \WC_Shipping_Zone( 0 );
	$shipping_methods = array_merge( $shipping_methods, $rest_of_world->get_shipping_methods( true ) );

	foreach ( $shipping_methods as $method ) {
		if ( ! $method instanceof \WC_Shipping_Method || 'free_shipping' !== $method->id ) {
			continue;
		}

		$is_enabled = (string) $method->get_option( 'enabled', 'no' );
		if ( 'yes' !== $is_enabled ) {
			continue;
		}

		$min_amount = (float) wc_format_decimal( $method->get_option( 'min_amount', 0 ) );
		if ( $min_amount <= 0 ) {
			continue;
		}

		if ( null === $free_shipping_min_amount || $min_amount < $free_shipping_min_amount ) {
			$free_shipping_min_amount = $min_amount;
		}
	}
}

// Fallback: read free-shipping instance settings directly from wp_options.
if ( null === $free_shipping_min_amount ) {
	global $wpdb;
	$option_rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT option_value FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name = %s",
			'woocommerce_free_shipping\_%\_settings',
			'woocommerce_free_shipping_settings'
		)
	);

	if ( is_array( $option_rows ) ) {
		foreach ( $option_rows as $row ) {
			$settings = maybe_unserialize( $row->option_value ?? null );
			if ( ! is_array( $settings ) ) {
				continue;
			}

			$is_enabled = isset( $settings['enabled'] ) ? (string) $settings['enabled'] : 'yes';
			if ( 'yes' !== $is_enabled ) {
				continue;
			}

			$min_amount = (float) wc_format_decimal( $settings['min_amount'] ?? 0 );
			if ( $min_amount <= 0 ) {
				continue;
			}

			if ( null === $free_shipping_min_amount || $min_amount < $free_shipping_min_amount ) {
				$free_shipping_min_amount = $min_amount;
			}
		}
	}
}

$show_shipping_banner = null !== $free_shipping_min_amount;
$shipping_banner_text = '';
if ( $show_shipping_banner ) {
	$formatted_amount    = html_entity_decode( wp_strip_all_tags( wc_price( $free_shipping_min_amount ) ), ENT_QUOTES, get_bloginfo( 'charset' ) );
	$shipping_banner_text = sprintf(
		/* translators: %s: minimum order amount for free shipping */
		__( 'Free shipping on orders of %s or more', 'shadcn' ),
		$formatted_amount
	);
}
?>
<style>
	.molecule-top-nav-announcement {
		display: flex;
		align-items: center;
		justify-content: center;
		background-color: #000000;
		color: #ffffff;
		text-align: center;
		font-size: 0.8125rem;
		line-height: 1.35;
		letter-spacing: 0.01em;
		padding: 0.45rem 1rem;
		max-height: 3rem;
		overflow: hidden;
		transition: max-height var(--default-transition-duration) var(--default-transition-timing-function),
			padding var(--default-transition-duration) var(--default-transition-timing-function),
			opacity var(--default-transition-duration) var(--default-transition-timing-function);
	}

	.molecule-top-nav.is-announcement-hidden .molecule-top-nav-announcement {
		max-height: 0;
		padding-top: 0;
		padding-bottom: 0;
		opacity: 0;
		display: none;
	}
</style>
<header class="molecule-top-nav" role="banner">
	<?php if ( $show_shipping_banner ) : ?>
		<div class="molecule-top-nav-announcement" aria-label="<?php esc_attr_e( 'Shipping announcement', 'shadcn' ); ?>">
			<?php echo esc_html( $shipping_banner_text ); ?>
		</div>
	<?php endif; ?>
	<div class="molecule-top-nav-inner">

		<?php /* ── Mobile row (3-column grid: hamburger | logo | icons) ── */ ?>
		<div class="molecule-top-nav-mobile">

			<?php /* Left: hamburger + slide-in drawer */ ?>
			<div class="molecule-top-nav-mobile-menu">
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
							<a href="<?php echo $search_url; ?>" aria-label="<?php esc_attr_e( 'Search', 'shadcn' ); ?>">
								<svg role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">
									<circle cx="11" cy="10" r="7" fill="none" stroke="currentColor"></circle>
									<path d="m16 15 3 3" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>
								</svg>
							</a>
							<a href="<?php echo $account_url; ?>" aria-label="<?php esc_attr_e( 'Account', 'shadcn' ); ?>">
								<svg role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">
									<circle cx="11" cy="7" r="4" fill="none" stroke="currentColor"></circle>
									<path d="M3.5 19c1.421-2.974 4.247-5 7.5-5s6.079 2.026 7.5 5" fill="none" stroke="currentColor" stroke-linecap="round"></path>
								</svg>
							</a>
							<a class="molecule-cart-icon-link" href="<?php echo $cart_url; ?>" aria-label="<?php esc_attr_e( 'Cart', 'shadcn' ); ?>">
								<svg role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">
									<path d="M11 7H3.577A2 2 0 0 0 1.64 9.497l2.051 8A2 2 0 0 0 5.63 19H16.37a2 2 0 0 0 1.937-1.503l2.052-8A2 2 0 0 0 18.422 7H11Zm0 0V1" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>
								</svg>
								<span class="molecule-cart-count" aria-hidden="true"><?php echo esc_html( $cart_count ); ?></span>
							</a>
						</div>
					</div>
					<nav class="molecule-mobile-drawer-nav" aria-label="<?php esc_attr_e( 'Mobile Navigation', 'shadcn' ); ?>">
						<a href="<?php echo $home_url; ?>"><?php esc_html_e( 'Home', 'shadcn' ); ?></a>
						<a href="<?php echo esc_url( home_url( '/shop' ) ); ?>"><?php esc_html_e( 'Catalog', 'shadcn' ); ?></a>
						<a href="<?php echo esc_url( home_url( '/peptide-guide' ) ); ?>"><?php esc_html_e( 'Peptide Guide', 'shadcn' ); ?></a>
						<a href="<?php echo esc_url( home_url( '/research' ) ); ?>"><?php esc_html_e( 'Research', 'shadcn' ); ?></a>
					</nav>
				</aside>
			</div>

			<?php /* Center: logo */ ?>
			<div class="molecule-top-nav-logo">
				<?php if ( $logo ) : ?>
					<?php echo $logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_custom_logo() is safe ?>
				<?php else : ?>
					<a href="<?php echo $home_url; ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
				<?php endif; ?>
			</div>

			<?php /* Right: icon links (search + account hidden <640px via existing CSS) */ ?>
			<div class="molecule-top-nav-icons">
				<a class="molecule-icon-link molecule-header-search" href="<?php echo $search_url; ?>" aria-label="<?php esc_attr_e( 'Search', 'shadcn' ); ?>">
					<svg role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">
						<circle cx="11" cy="10" r="7" fill="none" stroke="currentColor"></circle>
						<path d="m16 15 3 3" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>
					</svg>
				</a>
				<a class="molecule-icon-link molecule-header-account" href="<?php echo $account_url; ?>" aria-label="<?php esc_attr_e( 'Account', 'shadcn' ); ?>">
					<svg role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">
						<circle cx="11" cy="7" r="4" fill="none" stroke="currentColor"></circle>
						<path d="M3.5 19c1.421-2.974 4.247-5 7.5-5s6.079 2.026 7.5 5" fill="none" stroke="currentColor" stroke-linecap="round"></path>
					</svg>
				</a>
				<a class="molecule-icon-link molecule-cart-icon-link molecule-header-cart" href="<?php echo $cart_url; ?>" aria-label="<?php esc_attr_e( 'Cart', 'shadcn' ); ?>">
					<svg role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">
						<path d="M11 7H3.577A2 2 0 0 0 1.64 9.497l2.051 8A2 2 0 0 0 5.63 19H16.37a2 2 0 0 0 1.937-1.503l2.052-8A2 2 0 0 0 18.422 7H11Zm0 0V1" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>
					</svg>
					<span class="molecule-cart-count" aria-hidden="true"><?php echo esc_html( $cart_count ); ?></span>
				</a>
			</div>
		</div>
		<?php /* ── End mobile row ── */ ?>

		<?php /* ── Desktop row (flex space-between: logo | nav | icons) ── */ ?>
		<div class="molecule-top-nav-desktop">

			<div class="molecule-top-nav-logo">
				<?php if ( $logo ) : ?>
					<?php echo $logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php else : ?>
					<a href="<?php echo $home_url; ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
				<?php endif; ?>
			</div>

			<nav class="molecule-desktop-navigation" aria-label="<?php esc_attr_e( 'Desktop Navigation', 'shadcn' ); ?>">
				<a href="<?php echo $home_url; ?>"><?php esc_html_e( 'Home', 'shadcn' ); ?></a>
				<a href="<?php echo esc_url( home_url( '/shop' ) ); ?>"><?php esc_html_e( 'Catalog', 'shadcn' ); ?></a>
				<div class="molecule-desktop-nav-dropdown">
					<button
						class="molecule-desktop-dropdown-toggle"
						type="button"
						aria-expanded="false"
						aria-haspopup="true"
					>
						<?php esc_html_e( 'Research', 'shadcn' ); ?>
						<svg role="presentation" stroke-width="1.5" focusable="false" width="12" height="12" viewBox="0 0 12 12" aria-hidden="true">
							<path d="M1.5 4L6 8L10.5 4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>
						</svg>
					</button>
					<div class="molecule-desktop-dropdown-panel" hidden>
						<a href="<?php echo esc_url( home_url( '/peptide-guide' ) ); ?>"><?php esc_html_e( 'Peptide Guide', 'shadcn' ); ?></a>
						<a href="<?php echo esc_url( home_url( '/research' ) ); ?>"><?php esc_html_e( 'Research', 'shadcn' ); ?></a>
					</div>
				</div>
			</nav>

			<div class="molecule-top-nav-icons">
				<a class="molecule-icon-link molecule-header-search" href="<?php echo $search_url; ?>" aria-label="<?php esc_attr_e( 'Search', 'shadcn' ); ?>">
					<svg role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">
						<circle cx="11" cy="10" r="7" fill="none" stroke="currentColor"></circle>
						<path d="m16 15 3 3" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>
					</svg>
				</a>
				<a class="molecule-icon-link molecule-header-account" href="<?php echo $account_url; ?>" aria-label="<?php esc_attr_e( 'Account', 'shadcn' ); ?>">
					<svg role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">
						<circle cx="11" cy="7" r="4" fill="none" stroke="currentColor"></circle>
						<path d="M3.5 19c1.421-2.974 4.247-5 7.5-5s6.079 2.026 7.5 5" fill="none" stroke="currentColor" stroke-linecap="round"></path>
					</svg>
				</a>
				<a class="molecule-icon-link molecule-cart-icon-link molecule-header-cart" href="<?php echo $cart_url; ?>" aria-label="<?php esc_attr_e( 'Cart', 'shadcn' ); ?>">
					<svg role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">
						<path d="M11 7H3.577A2 2 0 0 0 1.64 9.497l2.051 8A2 2 0 0 0 5.63 19H16.37a2 2 0 0 0 1.937-1.503l2.052-8A2 2 0 0 0 18.422 7H11Zm0 0V1" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>
					</svg>
					<span class="molecule-cart-count" aria-hidden="true"><?php echo esc_html( $cart_count ); ?></span>
				</a>
			</div>
		</div>
		<?php /* ── End desktop row ── */ ?>

	</div>
</header>
