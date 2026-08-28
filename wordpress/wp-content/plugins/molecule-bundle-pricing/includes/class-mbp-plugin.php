<?php
/**
 * Core plugin wiring.
 *
 * @package MoleculeBundlePricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once MBP_PLUGIN_DIR . 'includes/class-mbp-admin-view.php';

/**
 * Class MBP_Plugin
 */
class MBP_Plugin {

	/**
	 * Initialize hooks and sub-controllers.
	 *
	 * @return void
	 */
	public function init() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>';
					esc_html_e( 'Molecule Bundle Pricing requires WooCommerce to be installed and active.', 'molecule-bundle-pricing' );
					echo '</p></div>';
				}
			);
			return;
		}

		if ( is_admin() ) {
			( new MBP_Settings() )->register();
			( new MBP_Product_Admin() )->register();
		}

		( new MBP_Frontend() )->register();
		( new MBP_Discount() )->register();
		( new MBP_Assets() )->register();
	}
}
