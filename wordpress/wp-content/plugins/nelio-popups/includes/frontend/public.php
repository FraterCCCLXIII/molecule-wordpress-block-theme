<?php

namespace Nelio_Popups\Frontend;

use WP_Query;

defined( 'ABSPATH' ) || exit;

function enqueue_popups() {

	if ( is_non_popup_preview() && ! show_popup_in_preview() ) {
		return;
	}

	if ( is_singular( 'nelio_popup' ) ) {
		return;
	}

	nelio_popups_enqueue_style( 'public' );
	nelio_popups_enqueue_style( 'block-customizations', array( 'nelio-popups-public' ) );

	wp_add_inline_style(
		'nelio-popups-public',
		get_style_vars() . "\n" . get_border_radius_css()
	);

	nelio_popups_enqueue_script( 'public' );
	wp_add_inline_script(
		'nelio-popups-public',
		sprintf(
			'NelioPopupsFrontendSettings = %s;',
			wp_json_encode( get_frontend_settings() )
		),
		'before'
	);
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_popups' );

add_action(
	'init',
	function () {
		$popups = array();

		add_action(
			'wp_head',
			function () use ( &$popups ) {
				if ( is_non_popup_preview() && ! show_popup_in_preview() ) {
					return;
				}

				$previewed_popup = get_previewed_popup();
				$active_popups   = empty( $previewed_popup ) ? get_active_popups() : array( $previewed_popup );
				foreach ( $active_popups as $active_popup ) {
					$size  = isset( $active_popup['config']['size'] )
						? $active_popup['config']['size']
						: array(
							'type'  => 'auto',
							'value' => 'normal',
						);
					$width = 'custom' === $size['type']
						? $size['width']['value'] . str_replace( '%', 'vw', $size['width']['unit'] )
						: '';

					$popups[] = sprintf(
						'<div id="%1$s" aria-hidden="true" class="nelio-popup-store %2$s"%3$s><div id="%4$s">%5$s</div></div>',
						esc_attr( 'nelio-popup-store-' . $active_popup['id'] ),
						'auto' === $size['type']
							? esc_attr( "nelio-popup-size--is-auto-{$size['value']}" )
							: esc_attr( "nelio-popup-size--is-{$size['type']}" ),
						empty( $width ) ? '' : " style=\"width:{$width}\"",
						esc_attr( 'nelio-popup-content-' . $active_popup['id'] ),
						do_shortcode( do_blocks( $active_popup['content'] ) )
					);
				}
			}
		);

		add_action(
			'wp_footer',
			function () use ( &$popups ) {
				echo implode( "\n", $popups ); // phpcs:ignore
			}
		);
	}
);

// =======
// HELPERS
// =======
// phpcs:ignore
function get_frontend_settings() {
	$settings = array(
		'context' => get_wordpress_context(),
		'popups'  => array_map( __NAMESPACE__ . '\remove_content', get_active_popups() ),
	);

	/**
	 * Filters the frontend settings.
	 *
	 * @param array $settings frontend settings.
	 *
	 * @since 1.0.0
	 */
	return apply_filters( 'nelio_popups_frontend_settings', $settings );
}

function get_wordpress_context() {
	return array(
		'isSingular'   => is_singular(),
		'postId'       => is_singular() ? get_the_ID() : 0,
		'postPopups'   => is_singular() ? get_post_popups( get_the_ID() ) : 'auto',
		'postType'     => get_post_type(),
		'parents'      => is_singular() ? get_post_ancestors( get_the_ID() ) : array(),
		'previewPopup' => remove_content( get_previewed_popup() ),
		'specialPage'  => get_special_page(),
		'template'     => is_singular() ? get_page_template_slug( get_the_ID() ) : '',
	);
}

function get_active_popups() {
	$popups = array();
	$query  = new WP_Query(
		array(
			'post_type'      => 'nelio_popup',
			'post_status'    => 'publish',
			'meta_key'       => '_nelio_popups_is_enabled', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			/**
			 * Filters the maximum number of popups that our plugin can load on any given page.
			 *
			 * @param number $popups_per_page number of popups to load. Default: 10.
			 */
			'posts_per_page' => apply_filters( 'nelio_popups_per_page', 10 ),
		)
	);
	if ( ! $query->have_posts() ) {
		wp_reset_postdata();
		return array();
	}

	while ( $query->have_posts() ) {
		$query->the_post();
		$popups[] = load_popup();
	}

	wp_reset_postdata();

	$popup_ids = array_column( $popups, 'id' );
	$settings  = array(
		'context' => get_wordpress_context(),
		'popups'  => $popups,
	);
	$settings  = apply_filters( 'nelio_popups_frontend_settings', $settings );
	$context   = $settings['context'];

	/**
	 * Filters the list of active popups.
	 *
	 * @param int[] $popup_ids List of active popup IDs.
	 * @param array $popups    Array of popup objects. (Added in 1.3.2)
	 * @param array $context   The WordPress context information. (Added in 1.3.2)
	 *
	 * @return int[] Filtered list of active popup IDs.
	 *
	 * @since 1.0.0
	 * @since 1.3.2 The $popups and $context parameters were added to this filter.
	 */
	$popup_ids = apply_filters( 'nelio_popups_active_popups', $popup_ids, $popups, $context );

	$popups = array_filter(
		$popups,
		function ( $popup ) use ( $popup_ids ) {
			return in_array( $popup['id'], $popup_ids, true );
		}
	);
	return array_values( $popups );
}

function get_post_popups( $post_id ) {
	$popups = get_post_meta( $post_id, '_nelio_popups_active_popup', true );
	$popups = empty( $popups ) ? 'auto' : $popups;
	if ( 'auto' === $popups ) {
		return 'auto';
	}

	$popups = explode( ',', $popups );
	$popups = array_map( 'absint', $popups );
	$popups = array_values( array_filter( $popups ) );
	return $popups;
}

function get_style_vars() {
	$default = array(
		'animate-delay'    => '1s',
		'animate-duration' => '1s',
	);

	/**
	 * Filters frontend style vars.
	 *
	 * @param array $default frontend style vars.
	 *
	 * @since 1.0.0
	 */
	$values = apply_filters( 'nelio_popups_frontend_style_vars', $default );
	$values = wp_parse_args( $values, $default );

	$vars = '';
	foreach ( $values as $name => $value ) {
		$vars .= "--nelio-popups-{$name}: $value;\n";
	}
	return ":root {\n{$vars}}";
}

/**
 * Returns CSS for popup wrapper border-radius (standalone borderRadius option).
 *
 * @return string
 */
function get_border_radius_css() {
	$previewed_popup = get_previewed_popup();
	$active_popups   = empty( $previewed_popup ) ? get_active_popups() : array( $previewed_popup );
	$css             = '';

	foreach ( $active_popups as $popup ) {
		$wrapper = $popup['config']['wrapper'] ?? array();
		$radius  = $wrapper['borderRadius'] ?? null;

		if ( empty( $radius ) || ! isset( $radius['value'], $radius['unit'] ) ) {
			continue;
		}

		$value = floatval( $radius['value'] );
		$unit  = in_array( $radius['unit'], array( 'px', 'em', 'rem' ), true ) ? $radius['unit'] : 'px';
		$popup_id = absint( $popup['id'] );

		$css .= sprintf(
			".nelio-popup-wrapper-%d .nelio-popup-js-popup { border-radius: %s%s; }\n",
			$popup_id,
			$value,
			$unit
		);
	}

	return $css;
}

function show_popup_in_preview() {
	/**
	 * Filters if popups should be included in previews or not.
	 *
	 * @param boolean $show_in_preview whether popups should be visibles in previews or not. Default: `false`.
	 *
	 * @since 1.0.0
	 */
	return apply_filters( 'nelio_popups_show_in_preview', false );
}

function is_non_popup_preview() {
	if ( ! is_preview() ) {
		return false;
	}
	$popup = get_previewed_popup();
	return empty( $popup );
}

function get_previewed_popup() {
	$popup_id = isset( $_GET['nelio-popup-preview'] )
		? absint( $_GET['nelio-popup-preview'] )
		: 0;

	$valid = isset( $_GET['nonce'] )
		? wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'nelio-popup-preview_' . $popup_id )
		: false;

	if ( ! $valid ) {
		return false;
	}

	$query = new WP_Query(
		array(
			'p'         => $popup_id,
			'post_type' => 'nelio_popup',
		)
	);
	if ( ! $query->have_posts() ) {
		wp_reset_postdata();
		return false;
	}

	$query->the_post();

	$popup = load_popup();

	wp_reset_postdata();
	return $popup;
}

function get_special_page() {
	if ( is_404() ) {
		return '404-page';
	} elseif ( is_home() ) {
		return 'blog-page';
	} elseif ( is_front_page() ) {
		return 'home-page';
	} elseif ( is_search() ) {
		return 'search-result-page';
	} else {
		return 'none';
	}
}

function remove_content( $popup ) {
	if ( empty( $popup ) ) {
		return $popup;
	}

	if ( ! isset( $popup['content'] ) ) {
		return $popup['content'];
	}

	unset( $popup['content'] );
	return $popup;
}
