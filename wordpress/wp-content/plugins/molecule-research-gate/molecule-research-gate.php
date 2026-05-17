<?php
/**
 * Plugin Name:       Molecule Research Gate
 * Description:       Gates WooCommerce catalog access with research compliance modals, user meta, and WooCommerce-native auth.
 * Version:           1.0.4
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Molecule
 * Text Domain:       molecule-research-gate
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 *
 * @package MoleculeResearchGate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MRG_VERSION', '1.0.4' );
define( 'MRG_PLUGIN_FILE', __FILE__ );
define( 'MRG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MRG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MRG_OPTION_KEY', 'mrg_settings' );

require_once MRG_PLUGIN_DIR . 'includes/class-mrg-user-profile.php';
require_once MRG_PLUGIN_DIR . 'includes/class-mrg-brevo-newsletter.php';
require_once MRG_PLUGIN_DIR . 'includes/class-mrg-gate.php';
require_once MRG_PLUGIN_DIR . 'includes/class-mrg-rest-controller.php';
require_once MRG_PLUGIN_DIR . 'includes/class-mrg-assets.php';
require_once MRG_PLUGIN_DIR . 'includes/class-mrg-admin-settings.php';
require_once MRG_PLUGIN_DIR . 'includes/class-mrg-admin-user-profile-display.php';
require_once MRG_PLUGIN_DIR . 'includes/class-mrg-plugin.php';

register_activation_hook(
	__FILE__,
	static function () {
		$defaults = MRG_Admin_Settings::get_defaults();
		if ( false === get_option( MRG_OPTION_KEY, false ) ) {
			add_option( MRG_OPTION_KEY, $defaults );
		}
	}
);

/**
 * Bootstrap plugin.
 */
function mrg_plugin(): MRG_Plugin {
	static $plugin = null;
	if ( null === $plugin ) {
		$plugin = new MRG_Plugin();
		$plugin->init();
	}
	return $plugin;
}

add_action( 'plugins_loaded', 'mrg_plugin' );
