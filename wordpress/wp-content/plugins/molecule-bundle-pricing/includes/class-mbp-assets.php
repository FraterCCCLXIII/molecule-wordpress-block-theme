<?php
/**
 * Front-end and admin asset loading.
 *
 * @package MoleculeBundlePricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MBP_Assets
 */
class MBP_Assets {

	/**
	 * Register asset hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
	}

	/**
	 * Enqueue the selector script + minimal structural CSS on single product pages.
	 *
	 * @return void
	 */
	public function enqueue_frontend() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		wp_enqueue_style(
			'mbp-bundle-pricing',
			MBP_PLUGIN_URL . 'assets/css/bundle-pricing.css',
			array(),
			MBP_VERSION
		);

		wp_enqueue_script(
			'mbp-bundle-pricing',
			MBP_PLUGIN_URL . 'assets/js/bundle-pricing.js',
			array( 'jquery' ),
			MBP_VERSION,
			true
		);

		wp_localize_script(
			'mbp-bundle-pricing',
			'mbpConfig',
			array(
				'priceFormat' => $this->get_price_format(),
				'i18n'        => array(
					'decrease' => __( 'Decrease quantity', 'molecule-bundle-pricing' ),
					'increase' => __( 'Increase quantity', 'molecule-bundle-pricing' ),
				),
			)
		);
	}

	/**
	 * Enqueue a small admin stylesheet for the tier tables.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin( $hook ) {
		$is_product_editor = in_array( $hook, array( 'post.php', 'post-new.php' ), true );
		$is_settings_page  = isset( $_GET['page'] ) && 'mbp-bundle-pricing' === sanitize_key( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $is_product_editor && ! $is_settings_page ) {
			return;
		}

		wp_enqueue_style(
			'mbp-bundle-pricing-admin',
			MBP_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			MBP_VERSION
		);
	}

	/**
	 * Currency formatting parameters for client-side price rendering.
	 *
	 * @return array<string, mixed>
	 */
	private function get_price_format() {
		return array(
			'symbol'             => html_entity_decode( get_woocommerce_currency_symbol() ),
			'decimals'           => wc_get_price_decimals(),
			'decimalSeparator'   => wc_get_price_decimal_separator(),
			'thousandSeparator'  => wc_get_price_thousand_separator(),
			'priceFormat'        => get_woocommerce_price_format(),
		);
	}
}
