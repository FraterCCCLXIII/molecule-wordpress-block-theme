<?php
/**
 * Product archive (main shop + inherited taxonomy archives): PHP-driven catalog.
 *
 * Used when block templates for the catalog are disabled so the Site Editor
 * product archive template is not required.
 *
 * @package Shadcn
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

$molecule_shadcn_block_theme = function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();

if ( $molecule_shadcn_block_theme ) {
	?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'woocommerce molecule-php-product-catalog' ); ?>>
	<?php wp_body_open(); ?>
	<div class="wp-site-blocks">
	<?php
	// Match the rest of the theme: Core::render_php_header only runs for core/template-part
	// blocks; block_header_area() would render parts/header.html and a different nav structure.
	get_template_part( 'template-parts/header' );
	?>
	<div class="molecule-php-catalog-main">
	<?php
} else {
	get_header( 'shop' );
}

/**
 * Hook: woocommerce_before_main_content.
 *
 * @hooked woocommerce_output_content_wrapper - 10
 */
do_action( 'woocommerce_before_main_content' );

/**
 * Hook: woocommerce_shop_loop_header.
 */
do_action( 'woocommerce_shop_loop_header' );

if ( woocommerce_product_loop() ) {
	/**
	 * Hook: woocommerce_before_shop_loop.
	 */
	do_action( 'woocommerce_before_shop_loop' );

	woocommerce_product_loop_start();

	if ( wc_get_loop_prop( 'total' ) ) {
		while ( have_posts() ) {
			the_post();

			/**
			 * Hook: woocommerce_shop_loop.
			 */
			do_action( 'woocommerce_shop_loop' );

			wc_get_template_part( 'content', 'product' );
		}
	}

	woocommerce_product_loop_end();

	/**
	 * Hook: woocommerce_after_shop_loop.
	 */
	do_action( 'woocommerce_after_shop_loop' );
} else {
	/**
	 * Hook: woocommerce_no_products_found.
	 */
	do_action( 'woocommerce_no_products_found' );
}

/**
 * Hook: woocommerce_after_main_content.
 */
do_action( 'woocommerce_after_main_content' );

if ( $molecule_shadcn_block_theme ) {
	?>
	</div>
	<?php
	if ( function_exists( 'block_footer_area' ) ) {
		block_footer_area();
	}
	?>
	</div>
	<?php wp_footer(); ?>
</body>
</html>
	<?php
} else {
	get_footer( 'shop' );
}
