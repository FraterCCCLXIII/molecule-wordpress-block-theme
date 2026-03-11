<?php
/**
 * Plugin Name: Molecule Popups
 * Plugin URI:  https://github.com/molecule/popups
 * Description: A plugin to create beautiful popups. Fork of Nelio Popups with premium features enabled.
 *
 * Author:      Molecule
 * Author URI:  https://github.com/molecule
 * Version:           1.3.6
 * Text Domain: nelio-popups
 *
 * Requires at least: 6.6
 * Requires PHP:      7.4
 *
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

defined( 'ABSPATH' ) || exit;

define( 'MOLECULE_POPUPS', true );
define( 'NELIO_POPUPS', true ); // Backward compatibility.

function nelio_popups_path() {
	return untrailingslashit( plugin_dir_path( __FILE__ ) );
}

function nelio_popups_url() {
	return untrailingslashit( plugin_dir_url( __FILE__ ) );
}

function nelio_popups_version() {
	$data = get_file_data( __FILE__, array( 'Version' ), 'plugin' );
	return $data[0];
}

function nelio_popups_init() {

	require_once nelio_popups_path() . '/includes/lib/nelio/helpers/index.php';
	require_once nelio_popups_path() . '/includes/lib/nelio/zod/index.php';

	require_once nelio_popups_path() . '/includes/popups.php';
	require_once nelio_popups_path() . '/includes/utils.php';

	require_once nelio_popups_path() . '/includes/compat.php';
	require_once nelio_popups_path() . '/includes/gutenberg.php';
	require_once nelio_popups_path() . '/includes/menu.php';
	require_once nelio_popups_path() . '/includes/popup-editor.php';
	require_once nelio_popups_path() . '/includes/popup-capabilities.php';
	require_once nelio_popups_path() . '/includes/rest.php';
	require_once nelio_popups_path() . '/includes/update.php';

	if ( ! is_admin() ) {
		require_once nelio_popups_path() . '/includes/frontend/index.php';
	}
}
nelio_popups_init();
