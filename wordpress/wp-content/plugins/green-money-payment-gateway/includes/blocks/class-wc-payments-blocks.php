<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;

/**
 * GreenPay Blocks integration
 *
 * @since 1.0.3
 */
final class WC_GreenPay_Gateway_Blocks_Support extends AbstractPaymentMethodType
{

	/**
	 * The gateway instance.
	 *
	 * @var WC_GreenPay_Gateway
	 */
	private $gateway;

	/**
	 * Holds the settings set by gmpg_settings.php
	 * 
	 */
	protected $settings = [];

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'greenmoney';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize()
	{
		$this->settings = get_option('gmpg_settings', []);
		$gateways       = WC()->payment_gateways->payment_gateways();
		$this->gateway  = isset($gateways[$this->name]) ? $gateways[$this->name] : null;
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active()
	{
		return $this->gateway && $this->gateway->is_available();
	}

	private static function get_dev_script($script_handle, $style_handle, $dev_url, $build_url, $script_path, $style_path, $asset_path)
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
				'dependencies' => array('wp-blocks', 'wp-element', 'wp-data', 'wp-editor', 'wp-api-fetch', 'wc-blocks-checkout'),
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
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles()
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
			return [];
		}

		$script_handle = 'wc-greenpay-payments-script';
		$style_handle = 'wc-greenpay-payments-style';
		$script_path = '/dist/greenpay.js';
		$style_path = '/dist/greenpay.css';
		$asset_path = '/dist/greenpay.asset.php';

		$build_url = WC_GreenPay_Payments::plugin_url();
		$dev_url = 'http://localhost:3000';

		$this->get_dev_script($script_handle, $style_handle, $dev_url, $build_url, $script_path, $style_path, $asset_path);

		if (function_exists('wp_set_script_translations')) {
			wp_set_script_translations($script_handle, 'woocommerce-greenpay-gateway', WC_GreenPay_Payments::plugin_abspath() . 'languages/');
		}

		return [$script_handle];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data()
	{
		$is_order_pay = function_exists('is_checkout_pay_page') && is_checkout_pay_page();
		$is_block_checkout = CartCheckoutUtils::is_checkout_block_default();

		$context = '';

		if ($is_order_pay) {
			$context = 'classic';
		} else if ($is_block_checkout) {
			$context = 'blocks';
		}

		return [
			'clientId'      => $this->settings['gmpg_client_id'] ?? '',
			'plaidEnabled'  => $this->settings['gmpg_plaid_enabled'] ?? false,
			'endpoint'      => $this->settings['gmpg_api_endpoint'] ?? '',
			'context'		=> $context,
			'title'         => $this->settings['gmpg_title'] ?? 'GreenPay',
			'description'   => $this->settings['gmpg_gateway_description'] ?? '',
			'extraMessage'  => $this->settings['gmpg_extra_message'] ?? '',
			'supports'      => ($this->gateway && isset($this->gateway->supports))
				? array_filter($this->gateway->supports, [$this->gateway, 'supports'])
				: []
		];
	}
}

add_filter('woocommerce_blocks_register_payment_method_type', function ($payment_methods) {
	$payment_methods[] = 'greenmoney';
	return $payment_methods;
});

add_filter('woocommerce_get_country_locale', function ($locale) {
	foreach ($locale as $key => $value) {
		$locale[$key]['phone'] = [
			'required' => true,
			'hidden'   => false,
		];
	}

	return $locale;
});
