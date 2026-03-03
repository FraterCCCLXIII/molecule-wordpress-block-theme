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
require_once __DIR__ . '/inc/SearchModal.php';
require_once __DIR__ . '/inc/Integrations.php';

/**
 * Hide Page Title blocks on frontend page views.
 *
 * This prevents duplicate/misaligned page H1 output from template overrides
 * and manually inserted Post Title blocks while preserving post/product titles
 * rendered in loops.
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

		$is_current_page_title = 'page' === $context_post_type
			&& $context_post_id > 0
			&& $context_post_id === (int) $queried_object_id;

		// Some template overrides can render the page title block without context.
		$is_contextless_page_title = '' === $context_post_type && 0 === $context_post_id;

		// Keep query-loop titles (products/posts) untouched by only hiding page-owned
		// or contextless page title blocks on page views.
		if ( $is_current_page_title || $is_contextless_page_title ) {
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
