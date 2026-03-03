<?php

/**
 * Plugin Name: GreenPay™ by Green.Money
 * Description: GreenPay™ gateway for WooCommerce
 * Author: Green.Money
 * Version: 3.3.3
 * Author URI: http://www.green.money/
 * Tested up to: 6.8.3
 * WC requires at least: 4.2.0
 * WC tested up to: 10.3.4
 */

add_action('before_woocommerce_init', function(){

    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );

    }

});

// Exit if accessed directly.
if (! defined('ABSPATH')) {
	exit;
}

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

include_once('includes/gmpg_extra_functions.php');
include_once('includes/gmpg_settings.php');

class WC_GreenPay_Payments
{
	/**
	 * Initialize the plugin.
	 */
	public static function init()
	{
		add_action('plugins_loaded', array(__CLASS__, 'on_plugins_loaded'));
	}

	/**
	 * Runs on the 'plugins_loaded' hook.
	 */
	public static function on_plugins_loaded()
	{
		// Check if WooCommerce is active
		if (!class_exists('WooCommerce')) {
			require_once(ABSPATH . 'wp-admin/includes/plugin.php');

			$plugins = get_plugins();

			foreach ($plugins as $plugin_path => $plugin) {
				if ('WooCommerce' === $plugin['Name']) {
					define('HAS_WOO', true);
					break;
				}
			}
			add_action('admin_notices', array(__CLASS__, 'woocommerce_gateway_green_money_notice'));
		}


       // Declare HPOS (Custom Order Tables) Compatibility
       add_action('before_woocommerce_init', function() {
         if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
         }
       });

		self::includes(); // Directly call the includes method
		add_filter('woocommerce_payment_gateways', array(__CLASS__, 'add_gateway'));
		add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
		add_action('woocommerce_blocks_loaded', array(__CLASS__, 'woocommerce_gateway_woocommerce_block_support'));

		if (did_action('elementor/loaded')) {
			add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts_elementor'));
		} else {
			add_action('elementor/loaded', function () {
				add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts_elementor'));
			});
		}
	}

	/**
	 * Adds the GreenPay payment gateway to WooCommerce.
	 */
	public static function add_gateway($gateways)
	{
		$gateways[] = 'WC_GreenPay_Gateway';
		return $gateways;
	}

	/**
	 * Plugin includes.
	 */
	public static function includes()
	{
		if (class_exists('WC_Payment_Gateway')) {
			require_once 'includes/class-wc-gateway.php';
		}
	}

	/**
	 * Enqueue a frontend script, pointing at local dev server when available,
	 * and add crossorigin="anonymous" for React dev errors.
	 *
	 * @param string   $script_handle      Script handle, e.g. 'wc-greenpay-payments-script'
	 * @param string   $style_handle       Style handle, e.g. 'wc-greenpay-payments-style'
	 * @param string   $dev_url     Full URL to your dev server bundle, e.g. 'http://localhost:3000/dist/greenpay.js'
	 * @param string   $build_url   Path inside the plugin to the built file, e.g. './plugins/woocommerce-greenpay/dist/greenpay.js'
	 * @param string   $script_path Path to the script js file
	 * @param string   $style_path  Path to the style css file
	 * @param string   $asset_path  Path to the asset php file
	 */
	public static function gmpg_enqueue_dev_script($script_handle, $style_handle, $dev_url, $build_url, $script_path, $style_path, $asset_path)
	{
		$env = get_site_url();
		// Check for test server
		$is_dev = $env == 'http://localhost:8881';

		if ($is_dev) {
			$script_url = $dev_url . $script_path;
			$style_url = $dev_url . $style_path;
			$asset_url = $dev_url . $asset_path;
			wp_script_add_data($script_handle, 'crossorigin', 'anonymous');
		} else {
			$script_url = $build_url . $script_path;
			$style_url = $build_url . $style_path;
			$asset_url = $build_url . $asset_path;
		}

		$asset = file_exists($asset_url)
			? require($asset_url)
			: array(
				'dependencies' => array('wp-element', 'wp-data', 'wp-editor'),
				'version' => time()
			);

		$deps = $asset['dependencies'];
		$version = $asset['version'];

		wp_register_style($style_handle, $style_url, array(), $version);
		wp_enqueue_style($style_handle);

		wp_register_script($script_handle, $script_url, $deps, $version, true);
		wp_enqueue_script($script_handle);
	}

	/**
	 * Helper function to enqueue front end scripts
	 */
	public static function enqueue_scripts_helper()
	{
		$script_handle = 'wc-greenpay-payments-script';
		$style_handle = 'wc-greenpay-payments-style';
		$script_path = '/dist/greenpay.js';
		$style_path = '/dist/greenpay.css';
		$script_deps_path = '/dist/greenpay.asset.php';

		$build_url = self::plugin_url();
		$dev_url = 'http://localhost:3000';

		self::gmpg_enqueue_dev_script($script_handle, $style_handle, $dev_url, $build_url, $script_path, $style_path, $script_deps_path);

		// Localized data passed to frontend
		$settings = get_option('gmpg_settings', []);

		wp_localize_script($script_handle, 'greenmoney_wpApiSettings', [
			'clientId' => $settings['gmpg_client_id'] ?? '',
			'plaidEnabled' => $settings['gmpg_plaid_enabled'] ?? false,
			'endpoint' => $settings['gmpg_api_endpoint'] ?? '',
			'context' => 'classic',
			'description' => $settings['gmpg_gateway_description'] ?? '',
			'extraMessage' => $settings['gmpg_extra_message'] ?? ''
		]);
	}

	/**
	 * Enqueues scripts for the plugin for Classic checkout.
	 */
	public static function enqueue_scripts()
	{
		global $post;
		$is_checkout = false;

		// Detect if the current page is a WooCommerce checkout page
		if (function_exists('is_checkout') && is_checkout()) {
			$is_checkout = true;
		}

		// Detect if the current page contains a WooCommerce block-based checkout
		if (function_exists('has_block') && has_block('woocommerce/checkout')) {
			$is_checkout = true;
		}

		// Detect if the current page contains the WooCommerce checkout shortcode
		if (isset($post->post_content) && has_shortcode($post->post_content, 'woocommerce_checkout')) {
			$is_checkout = true;
		}

		if (!$is_checkout) {
			return;
		}

		self::enqueue_scripts_helper();
	}

	/**
	 * Enqueues scripts for the plugin for Elementor checkout.
	 */
	public static function enqueue_scripts_elementor()
	{
		if (self::is_elementor_checkout()) {
			self::enqueue_scripts_helper();
		}
	}

	/**
	 * Conditional check if the checkout is within the Elementor plugin
	 */
	public static function is_elementor_checkout()
	{
		global $post;

		if (!did_action('elementor/loaded') || !$post) {
			return false;
		}

		$is_elementor = class_exists('\\Elementor\\Plugin') && \Elementor\Plugin::$instance->db->is_built_with_elementor($post->ID);

		if (!$is_elementor) {
			return false;
		}

		return shortcode_exists('woocommerce_checkout');
	}

	public static function woocommerce_gateway_woocommerce_block_support()
	{
		if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
			require_once dirname(__FILE__) . '/includes/blocks/class-wc-payments-blocks.php';
			add_action('woocommerce_blocks_payment_method_type_registration', function (PaymentMethodRegistry $payment_method_registry) {
				$container = Automattic\WooCommerce\Blocks\Package::container();
				$container->register(
					WC_GreenPay_Gateway_Blocks_Support::class,
					function () {
						return new WC_GreenPay_Gateway_Blocks_Support();
					}
				);

				$payment_method_registry->register(
					$container->get(WC_GreenPay_Gateway_Blocks_Support::class)
				);
			});
		}
	}

	/**
	 * Plugin url.
	 *
	 * @return string
	 */
	public static function plugin_url()
	{
		return untrailingslashit(plugins_url('/', __FILE__));
	}

	/**
	 * Plugin url.
	 *
	 * @return string
	 */
	public static function plugin_abspath()
	{
		return trailingslashit(plugin_dir_path(__FILE__));
	}

	/**
	 * Displays an admin notice if WooCommerce is not active.
	 */
	public static function woocommerce_missing_notice()
	{
		echo '<div class="error"><p>' . esc_html__('GreenPay™ by Green.Money requires WooCommerce to be installed and active.', 'woocommerce-gateway-green-money') . '</p></div>';
	}
}

// Initialize the plugin
WC_GreenPay_Payments::init();
