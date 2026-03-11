<?php

namespace Nelio_Popups\Popup_Editor;

defined( 'ABSPATH' ) || exit;

function enqueue_block_editor_assets() {
	if ( get_post_type() !== 'nelio_popup' ) {
		return;
	}

	$settings = array(
		'popupCloseBlocks' => get_popup_blocks_with_close_control(),
		'activePlugins'    => get_active_plugins(),
	);

	/**
	 * Filters the popup editor settings.
	 *
	 * @param array $settings Popup editor settings.
	 *
	 * @since 1.0.13
	 */
	$settings = apply_filters( 'nelio_popups_editor_settings', $settings );

	nelio_popups_enqueue_script( 'popup-editor' );
	wp_add_inline_script(
		'nelio-popups-popup-editor',
		sprintf(
			'NelioPopupsEditorSettings = %s;',
			wp_json_encode( $settings )
		),
		'before'
	);
}
add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\enqueue_block_editor_assets' );

function enqueue_block_assets() {
	if ( ! is_admin() ) {
		return;
	}

	nelio_popups_enqueue_style( 'block-customizations' );
	nelio_popups_enqueue_script( 'block-customizations' );
}
add_action( 'enqueue_block_assets', __NAMESPACE__ . '\enqueue_block_assets' );

// ======
// HELPERS
// ======
// phpcs:ignore
function get_popup_blocks_with_close_control() {
	/**
	 * Filters the block types where the control to close a popup appears.
	 *
	 * @param array $blocks block names.
	 *
	 * @since 1.0.0
	 */
	return apply_filters( 'nelio_popups_blocks_with_close_control', array( 'core/button' ) );
}

function get_active_plugins() {
	$clean_extension = function ( $plugin ) {
		return substr( $plugin, 0, -4 );
	};

	$plugins = array_keys( get_plugins() );
	$actives = array_map( 'is_plugin_active', $plugins );
	$plugins = array_combine( $plugins, $actives );
	$plugins = array_keys( array_filter( $plugins ) );
	$plugins = array_map( $clean_extension, $plugins );

	return $plugins;
}
