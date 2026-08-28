<?php
/**
 * Plugin Name:       Protocol Grade Bundle Pricing
 * Description:       Quantity-tier bundle pricing for WooCommerce products (Buy 1 / 3 / 6+ style) with global defaults, per-product and per-variation overrides, and server-side discount enforcement.
 * Version:           1.0.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Protocol Grade
 * Text Domain:       molecule-bundle-pricing
 * Requires Plugins:  woocommerce
 *
 * Markup/class-name contract shared with the active theme's styling layer:
 *   .molecule-bundle-tiers              Wrapper (radiogroup)
 *   .molecule-bundle-tier               Single tier card (role=radio)
 *   .molecule-bundle-tier--active       Selected card
 *   .molecule-bundle-tier--open-ended   Card that reveals the quantity stepper
 *   .molecule-bundle-tier__badge        Optional "Popular" badge
 *   .molecule-bundle-tier__indicator    Radio dot indicator
 *   .molecule-bundle-tier__label        Primary label (e.g. "Buy 3")
 *   .molecule-bundle-tier__sublabel     Secondary label (e.g. "Save 5%")
 *   .molecule-bundle-tier__price        Computed discounted price
 *   .molecule-bundle-tier__price-was    Strikethrough original price
 *   .molecule-bundle-tier__stepper      Quantity stepper inside the open-ended card
 * The plugin ships only minimal structural CSS; the theme paints the rest.
 *
 * @package MoleculeBundlePricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MBP_VERSION', '1.0.1' );
define( 'MBP_PLUGIN_FILE', __FILE__ );
define( 'MBP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MBP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MBP_OPTION_KEY', 'mbp_settings' );
define( 'MBP_META_ENABLED', '_mbp_enabled' );
define( 'MBP_META_TIERS', '_mbp_tiers' );

require_once MBP_PLUGIN_DIR . 'includes/class-mbp-tiers.php';
require_once MBP_PLUGIN_DIR . 'includes/class-mbp-settings.php';
require_once MBP_PLUGIN_DIR . 'includes/class-mbp-product-admin.php';
require_once MBP_PLUGIN_DIR . 'includes/class-mbp-frontend.php';
require_once MBP_PLUGIN_DIR . 'includes/class-mbp-discount.php';
require_once MBP_PLUGIN_DIR . 'includes/class-mbp-assets.php';
require_once MBP_PLUGIN_DIR . 'includes/class-mbp-plugin.php';

register_activation_hook(
	__FILE__,
	static function () {
		if ( false === get_option( MBP_OPTION_KEY, false ) ) {
			add_option( MBP_OPTION_KEY, MBP_Settings::get_defaults() );
		}
	}
);

/**
 * Bootstrap the plugin once WordPress and plugins are loaded.
 *
 * @return MBP_Plugin
 */
function mbp_plugin() {
	static $plugin = null;
	if ( null === $plugin ) {
		$plugin = new MBP_Plugin();
		$plugin->init();
	}
	return $plugin;
}

add_action( 'plugins_loaded', 'mbp_plugin' );
