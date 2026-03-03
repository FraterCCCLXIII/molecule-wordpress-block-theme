<?php
/**
 * Shadcn WP Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WP_Shadcn
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/inc/Core.php';
require_once __DIR__ . '/inc/DarkMode.php';
require_once __DIR__ . '/inc/PromoPopup.php';
require_once __DIR__ . '/inc/Integrations.php';

/**
 * Hide the automatic Page Title block on frontend pages.
 *
 * This keeps manually-authored H1 content intact while preventing block
 * template (including DB-saved template overrides) from injecting a duplicate
 * page title above the page content.
 */
add_filter(
	'render_block_core/post-title',
	static function ( $block_content, $block ) {
		if ( is_admin() ) {
			return $block_content;
		}

		if ( ! is_page() ) {
			return $block_content;
		}

		$context_post_type = '';
		if ( isset( $block['context']['postType'] ) && is_string( $block['context']['postType'] ) ) {
			$context_post_type = $block['context']['postType'];
		}

		$context_post_id = 0;
		if ( isset( $block['context']['postId'] ) ) {
			$context_post_id = (int) $block['context']['postId'];
		}

		$queried_object_id = get_queried_object_id();

		// Hide only the current page's own automatic title block. Keep query-loop
		// titles (products/posts), which use different context post IDs.
		if ( 'page' === $context_post_type && $context_post_id > 0 && $context_post_id === (int) $queried_object_id ) {
			return '';
		}

		return $block_content;
	},
	10,
	2
);

/**
 * Map Product Details tabs to ACF fields.
 *
 * Additional Information -> Research
 * Reviews                -> COA
 */
add_filter(
	'shadcn_product_details_tab_acf_sources',
	static function ( $sources ) {
		$sources['additional_information'] = array(
			// Set to the real ACF field key (field_...) when available.
			'field_key'  => '',
			'field_name' => 'research',
			'post_id'    => null, // Current product.
		);

		$sources['reviews'] = array(
			// Set to the real ACF field key (field_...) when available.
			'field_key'  => '',
			'field_name' => 'coa',
			'post_id'    => null, // Current product.
		);

		return $sources;
	}
);

// Development Tools (only load in development environment)
if ( is_admin() ) {
	// JSX to Gutenberg Converter (development tool)
	if ( file_exists( __DIR__ . '/inc/Admin/JSXConverter.php' ) ) {
		require_once __DIR__ . '/inc/Admin/JSXConverter.php';
	}
}
