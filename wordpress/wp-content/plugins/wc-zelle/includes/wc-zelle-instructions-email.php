<?php
/**
 * Send Zelle payment instructions by email (admin resend, etc.).
 *
 * @package wc-zelle
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Build HTML body for Zelle payment instructions email.
 *
 * @param WC_Zelle_Gateway $gateway Gateway instance.
 * @param WC_Order         $order   Order.
 * @return string
 */
function wc_zelle_build_payment_instructions_email_html( $gateway, WC_Order $order ) {
	if ( ! is_object( $gateway ) || ! is_a( $order, 'WC_Order' ) ) {
		return '';
	}

	$html = '';

	if ( function_exists( 'wc_zelle_instructions_order_section' ) ) {
		$html .= wc_zelle_instructions_order_section( $gateway, $order, 'email' );
	}

	if ( ! empty( $gateway->store_instructions ) ) {
		$html .= '<p>' . wp_kses_post( $gateway->store_instructions ) . '</p>';
	}

	/**
	 * Filter HTML body for manually sent Zelle instruction emails.
	 *
	 * @param string           $html    Email body HTML.
	 * @param WC_Order         $order   Order.
	 * @param WC_Zelle_Gateway $gateway Gateway.
	 */
	return apply_filters( 'wc_zelle_payment_instructions_email_html', $html, $order, $gateway );
}

/**
 * Send Zelle payment instructions to a customer email address.
 *
 * @param WC_Order $order    Order.
 * @param string   $to_email Recipient email.
 * @return true|WP_Error
 */
function wc_zelle_send_payment_instructions_email( WC_Order $order, $to_email ) {
	if ( ! is_a( $order, 'WC_Order' ) ) {
		return new WP_Error( 'wc_zelle_invalid_order', __( 'Invalid order.', WCZELLE_PLUGIN_TEXT_DOMAIN ) );
	}

	if ( 'zelle' !== $order->get_payment_method() ) {
		return new WP_Error( 'wc_zelle_not_zelle', __( 'This order was not paid with Zelle.', WCZELLE_PLUGIN_TEXT_DOMAIN ) );
	}

	$to_email = sanitize_email( $to_email );
	if ( ! is_email( $to_email ) ) {
		return new WP_Error( 'wc_zelle_invalid_email', __( 'Please enter a valid email address.', WCZELLE_PLUGIN_TEXT_DOMAIN ) );
	}

	if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
		return new WP_Error( 'wc_zelle_no_wc', __( 'WooCommerce is not available.', WCZELLE_PLUGIN_TEXT_DOMAIN ) );
	}

	$gateways = WC()->payment_gateways()->payment_gateways();
	$gateway  = isset( $gateways['zelle'] ) ? $gateways['zelle'] : null;
	if ( ! is_object( $gateway ) ) {
		return new WP_Error( 'wc_zelle_no_gateway', __( 'Zelle gateway is not configured.', WCZELLE_PLUGIN_TEXT_DOMAIN ) );
	}

	$body_html = wc_zelle_build_payment_instructions_email_html( $gateway, $order );
	if ( '' === trim( wp_strip_all_tags( $body_html ) ) ) {
		return new WP_Error( 'wc_zelle_empty_body', __( 'Could not build Zelle payment instructions.', WCZELLE_PLUGIN_TEXT_DOMAIN ) );
	}

	$subject = sprintf(
		/* translators: %s: order number */
		__( 'Zelle payment instructions for order #%s', WCZELLE_PLUGIN_TEXT_DOMAIN ),
		$order->get_order_number()
	);

	/**
	 * Filter subject line for manually sent Zelle instruction emails.
	 *
	 * @param string   $subject Subject.
	 * @param WC_Order $order   Order.
	 */
	$subject = apply_filters( 'wc_zelle_payment_instructions_email_subject', $subject, $order );

	$mailer  = WC()->mailer();
	$message = $mailer->wrap_message( $subject, $body_html );
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );

	/**
	 * Filter headers for manually sent Zelle instruction emails.
	 *
	 * @param array    $headers Email headers.
	 * @param WC_Order $order   Order.
	 * @param string   $to_email Recipient.
	 */
	$headers = apply_filters( 'wc_zelle_payment_instructions_email_headers', $headers, $order, $to_email );

	$sent = $mailer->send( $to_email, $subject, $message, $headers, array() );

	if ( ! $sent ) {
		return new WP_Error( 'wc_zelle_send_failed', __( 'The email could not be sent. Check your site mail settings and try again.', WCZELLE_PLUGIN_TEXT_DOMAIN ) );
	}

	$order->add_order_note(
		sprintf(
			/* translators: %s: customer email address */
			__( 'Zelle payment instructions resent to %s.', WCZELLE_PLUGIN_TEXT_DOMAIN ),
			$to_email
		),
		false,
		true
	);

	/**
	 * Fires after Zelle payment instructions are sent manually from admin.
	 *
	 * @param WC_Order $order    Order.
	 * @param string   $to_email Recipient email.
	 */
	do_action( 'wc_zelle_payment_instructions_email_sent', $order, $to_email );

	return true;
}
