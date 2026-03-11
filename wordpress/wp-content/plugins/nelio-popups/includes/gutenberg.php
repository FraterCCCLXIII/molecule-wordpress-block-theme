<?php

namespace Nelio_Popups\Gutenberg;

defined( 'ABSPATH' ) || exit;

function enqueue_assets() {
	if ( empty( get_post_type() ) ) {
		return;
	}

	if ( get_post_type() === 'nelio_popup' ) {
		return;
	}

	$post_types = array_keys(
		array_filter(
			get_post_types(),
			function ( $key ) {
				return strpos( $key, 'wp_' ) !== 0;
			},
			ARRAY_FILTER_USE_KEY
		)
	);

	/**
	 * Filters the post types extended with popup editor settings.
	 *
	 * @param array $post_types Post types.
	 *
	 * @since 1.0.16
	 */
	$filtered_post_types = apply_filters( 'nelio_popups_extended_post_types', $post_types );

	$settings = array(
		'postTypes'       => $filtered_post_types,
		'popupOpenBlocks' => get_popup_blocks_with_open_control(),
	);

	/**
	 * Filters the popup editor settings.
	 *
	 * @param array $settings Popup editor settings.
	 *
	 * @since 1.0.15
	 */
	$settings = apply_filters( 'nelio_popups_settings', $settings );

	nelio_popups_register_script( 'gutenberg' );
	nelio_popups_enqueue_script( 'gutenberg' );
	wp_add_inline_script(
		'nelio-popups-gutenberg',
		sprintf(
			'NelioPopupsSettings = %s;',
			wp_json_encode( $settings )
		),
		'before'
	);
}
add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\enqueue_assets' );

function register_active_popup_meta() {
	$post_types = array_filter(
		get_post_types(),
		function ( $key ) {
			return strpos( $key, 'wp_' ) !== 0;
		},
		ARRAY_FILTER_USE_KEY
	);
	$field      = 'nelio_popups_active_popup';
	register_rest_field(
		$post_types,
		$field,
		array(
			'get_callback'    => function ( $params ) use ( $field ) {
				return get_post_meta( $params['id'], "_{$field}", true );
			},
			'update_callback' => function ( $value, $object ) use ( $field ) { // phpcs:ignore
				if ( 'auto' === $value ) {
					delete_post_meta( $object->ID, "_{$field}" );
				} else {
					update_post_meta( $object->ID, "_{$field}", $value );
				}
			},
		)
	);
}
add_action( 'init', __NAMESPACE__ . '\register_active_popup_meta', 5 );

function get_popup_blocks_with_open_control() {
	/**
	 * Filters the block types where the control to open a popup appears.
	 *
	 * @param array $blocks block names.
	 *
	 * @since 1.0.15
	 */
	return apply_filters( 'nelio_popups_blocks_with_open_control', array( 'core/button' ) );
}
