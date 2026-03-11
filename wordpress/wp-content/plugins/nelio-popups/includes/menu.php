<?php

namespace Nelio_Popups\Menu;

defined( 'ABSPATH' ) || exit;

function register_submenus() {
	// phpcs:ignore WordPress.WP.Capabilities.Unknown
	if ( ! current_user_can( 'edit_others_nelio_popups' ) ) {
		return;
	}

	// Molecule Popups fork: Premium upsell and Nelio support links removed.
}
add_action( 'admin_menu', __NAMESPACE__ . '\register_submenus' );
