<?php
/**
 * Feature flags for this fork: PRO-tier behavior without a separate PRO license.
 *
 * @package wc-zelle
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wc_zelle_is_pro' ) ) {
	/**
	 * Whether “PRO” gateway features should be active (checkout templates, automation, notes, debug, etc.).
	 *
	 * @return bool
	 */
	function wc_zelle_is_pro() {
		return (bool) apply_filters( 'wc_zelle_is_pro', true );
	}
}

if ( ! function_exists( 'wc_zelle_render_copy_field' ) ) {
	/**
	 * Markup for label + readonly field + copy button (used on checkout).
	 *
	 * @param string $label Field label.
	 * @param string $value Field value.
	 * @return string
	 */
	function wc_zelle_render_copy_field( $label, $value ) {
		if ( $value === null || $value === '' ) {
			return '';
		}
		$safe = esc_attr( $value );
		return '<div class="my-2"><label class="form-label fw-bold">' . esc_html( $label ) . '</label><div class="d-flex align-items-center flex-wrap"><input type="text" readonly class="form-control copytxt me-2" value="' . $safe . '" /><button type="button" class="btn btn-dark copybtn">' . esc_html__( 'Copy', WCZELLE_PLUGIN_TEXT_DOMAIN ) . '</button></div></div>';
	}
}

/**
 * When the Zelle “payment received” email is enabled, skip WooCommerce’s standard
 * “Processing order” email for Zelle orders — both would send on payment_complete.
 */
add_filter( 'woocommerce_email_enabled_customer_processing_order', 'wc_zelle_suppress_duplicate_processing_email', 10, 3 );
/**
 * @param bool            $enabled Whether WC’s processing email is enabled in settings.
 * @param WC_Order|false  $order   Order (set when the email is triggered).
 * @param WC_Email        $email   Email instance.
 * @return bool
 */
function wc_zelle_suppress_duplicate_processing_email( $enabled, $order, $email ) {
	if ( ! $enabled || ! $order instanceof WC_Order ) {
		return $enabled;
	}
	if ( $order->get_payment_method() !== 'zelle' ) {
		return $enabled;
	}
	if ( false === apply_filters( 'wc_zelle_suppress_processing_email_when_zelle_received_email', true, $order ) ) {
		return $enabled;
	}
	if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
		return $enabled;
	}
	$gateways = WC()->payment_gateways()->payment_gateways();
	$gw       = $gateways['zelle'] ?? null;
	if ( ! $gw || 'yes' !== $gw->get_option( 'zelle_payment_received_email', 'no' ) ) {
		return $enabled;
	}
	return false;
}
