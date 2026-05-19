<?php
/**
 * Plugin Name: Affiliate Coupon Tracker
 * Description: Track affiliate coupon usage, link customers to affiliates (referral links, coupons, admin), and report monthly totals for payouts.
 * Version: 1.1.8
 * Author: Molecule
 * Requires Plugins: woocommerce
 */

defined( 'ABSPATH' ) || exit;

define( 'ACT_VERSION', '1.1.8' );
define( 'ACT_PLUGIN_FILE', __FILE__ );
define( 'ACT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
if ( ! defined( 'ACT_AFFILIATE_REF_COOKIE_TTL' ) ) {
	define( 'ACT_AFFILIATE_REF_COOKIE_TTL', 90 * ( defined( 'DAY_IN_SECONDS' ) ? DAY_IN_SECONDS : 86400 ) );
}

require_once ACT_PLUGIN_DIR . 'includes/class-act-coupon-affiliate-fields.php';
require_once ACT_PLUGIN_DIR . 'includes/class-act-affiliate-payout-mark.php';
require_once ACT_PLUGIN_DIR . 'includes/class-act-customer-affiliate.php';
require_once ACT_PLUGIN_DIR . 'includes/class-act-order-report-repository.php';
require_once ACT_PLUGIN_DIR . 'includes/class-act-admin-report-page.php';
require_once ACT_PLUGIN_DIR . 'includes/class-act-affiliate-coupon-guard.php';

/**
 * Bootstrap plugin classes.
 */
function act_bootstrap_plugin() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action(
			'admin_notices',
			static function() {
				if ( ! current_user_can( 'activate_plugins' ) ) {
					return;
				}

				echo '<div class="notice notice-error"><p>';
				echo esc_html__( 'Affiliate Coupon Tracker requires WooCommerce to be active.', 'affiliate-coupon-tracker' );
				echo '</p></div>';
			}
		);
		return;
	}

	$repository         = new ACT_Order_Report_Repository();
	$coupon_fields      = new ACT_Coupon_Affiliate_Fields();
	$customer_affiliate = new ACT_Customer_Affiliate( $repository );
	$coupon_guard       = new ACT_Affiliate_Coupon_Guard( $repository );
	$report_page        = new ACT_Admin_Report_Page( $repository );

	$coupon_fields->register_hooks();
	$customer_affiliate->register_hooks();
	$coupon_guard->register_hooks();
	$report_page->register_hooks();
}
add_action( 'plugins_loaded', 'act_bootstrap_plugin' );
