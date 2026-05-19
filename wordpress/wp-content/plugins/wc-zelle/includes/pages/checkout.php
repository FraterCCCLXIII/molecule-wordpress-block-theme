<?php
/**
 * Checkout payment fields (PRO-style layouts: compact, full width, or two columns).
 *
 * @package wc-zelle
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$checkout_html = '';
global $woocommerce;

$amount = $woocommerce->cart->total;
$total = $woocommerce->cart->get_total();

$checkout_html .= '<fieldset id="wc-' . esc_attr( $this->id ) . '-form" data-plugin="' . wp_kses_post( WCZELLE_PLUGIN_VERSION ) . '">';
do_action( 'woocommerce_form_start', $this->id );

if ( $this->display_zelle === 'no' ) {
	$this->update_option( 'display_zelle', '1' );
} elseif ( $this->display_zelle === 'yes' ) {
	$this->update_option( 'display_zelle', '2' );
}

$design = (string) $this->display_zelle;
if ( ! in_array( $design, array( '1', '2', '3' ), true ) ) {
	$design = '2';
}

if ( empty( $this->ReceiverZELLEEmail ) && empty( $this->ReceiverZELLENo ) ) {
	$checkout_html .= '<p>' . wp_kses_post( __( 'Please finish setting up this payment method or contact the admin to do so.', WCZELLE_PLUGIN_TEXT_DOMAIN ) ) . '</p>';
	do_action( 'woocommerce_form_end', $this->id );
	$checkout_html .= '<input name="do_not_checkout" type="hidden" value="true"><div class="clear"></div></fieldset>';
	echo $checkout_html;
	return;
}

$checkout_intro = sprintf(
	wp_kses_post( __( 'Send %s via %s or from your bank', WCZELLE_PLUGIN_TEXT_DOMAIN ) ),
	$total,
	'<a style="color: #6d1fd4" href="https://zellepay.com/" target="_blank">Zelle</a>'
);

$notice_top = $this->checkout_description ? wp_kses_post( $this->checkout_description ) : '';

$structured_checkout = function_exists( 'wc_zelle_instructions_checkout_section' ) ? wc_zelle_instructions_checkout_section( $this ) : '';

$qr_block = '';
if ( 'yes' === $this->enableQRCode && ! empty( trim( (string) $this->ZelleQRCode ) ) ) {
	$qr_block .= '<div class="text-center my-3">' . $this->wc_zelle_qrcode( $amount, 'advanced' ) . '</div>';
}

$zelle_block = '';
$zelle_block .= '<p class="fw-bold">' . esc_html__( 'Zelle recipient details', WCZELLE_PLUGIN_TEXT_DOMAIN ) . '</p>';

$receiver_zelle_tag = trim( (string) $this->ReceiverZelleTag );

if ( '3' === $design ) {
	$zelle_block .= '<div class="row d-flex flex-wrap justify-content-between">';
	$zelle_block .= '<div class="col-half">';
	$zelle_block .= wc_zelle_render_copy_field( sprintf( esc_html__( '%s name', WCZELLE_PLUGIN_TEXT_DOMAIN ), 'Zelle' ), $this->ReceiverZelleOwner );
	if ( $receiver_zelle_tag !== '' ) {
		$zelle_block .= wc_zelle_render_copy_field( esc_html__( 'Zelle Tag (handle)', WCZELLE_PLUGIN_TEXT_DOMAIN ), $receiver_zelle_tag );
	}
	$zelle_block .= wc_zelle_render_copy_field( sprintf( esc_html__( '%s email', WCZELLE_PLUGIN_TEXT_DOMAIN ), 'Zelle' ), $this->ReceiverZELLEEmail );
	$zelle_block .= '</div><div class="col-half">';
	$zelle_block .= wc_zelle_render_copy_field( sprintf( esc_html__( '%s phone', WCZELLE_PLUGIN_TEXT_DOMAIN ), 'Zelle' ), $this->ReceiverZELLENo );
	if ( $qr_block ) {
		$zelle_block .= $qr_block;
	}
	$zelle_block .= '</div></div>';
} else {
	$zelle_block .= wc_zelle_render_copy_field( sprintf( esc_html__( '%s name', WCZELLE_PLUGIN_TEXT_DOMAIN ), 'Zelle' ), $this->ReceiverZelleOwner );
	if ( $receiver_zelle_tag !== '' ) {
		$zelle_block .= wc_zelle_render_copy_field( esc_html__( 'Zelle Tag (handle)', WCZELLE_PLUGIN_TEXT_DOMAIN ), $receiver_zelle_tag );
	}
	$zelle_block .= wc_zelle_render_copy_field( sprintf( esc_html__( '%s email', WCZELLE_PLUGIN_TEXT_DOMAIN ), 'Zelle' ), $this->ReceiverZELLEEmail );
	$zelle_block .= wc_zelle_render_copy_field( sprintf( esc_html__( '%s phone', WCZELLE_PLUGIN_TEXT_DOMAIN ), 'Zelle' ), $this->ReceiverZELLENo );
	if ( '2' === $design && $qr_block ) {
		$zelle_block .= $qr_block;
	}
}

if ( '1' === $design ) {
	$checkout_html .= $notice_top ? '<div class="wc-zelle-checkout-notice">' . $notice_top . '</div>' : '';
	$checkout_html .= '<p>' . $checkout_intro . '.</p>';
	$checkout_html .= $structured_checkout;
	if ( ! empty( $this->store_instructions ) ) {
		$checkout_html .= '<p>' . wp_kses_post( $this->store_instructions ) . '</p>';
	}
} else {
	$checkout_html .= $notice_top ? '<div class="wc-zelle-checkout-notice">' . $notice_top . '</div>' : '';
	$checkout_html .= '<p>' . $checkout_intro . '.</p>';
	$checkout_html .= $zelle_block;
	$checkout_html .= $structured_checkout;
	if ( ! empty( $this->store_instructions ) ) {
		$checkout_html .= '<p>' . wp_kses_post( $this->store_instructions ) . '</p>';
	}
}

if ( ! empty( $this->ReceiverZELLENo ) ) {
	$call = esc_html__( 'please call', WCZELLE_PLUGIN_TEXT_DOMAIN ) . ' <a href="tel:' . esc_attr( $this->ReceiverZELLENo ) . '" target="_blank">' . esc_html( $this->ReceiverZELLENo ) . '</a>.';
} else {
	$call = '';
}
if ( ! empty( $this->ReceiverZELLEEmail ) ) {
	$email = ' ' . esc_html__( 'You can also email', WCZELLE_PLUGIN_TEXT_DOMAIN ) . ' <a href="mailto:' . esc_attr( $this->ReceiverZELLEEmail ) . '" target="_blank">' . esc_html( $this->ReceiverZELLEEmail ) . '</a>';
} else {
	$email = '';
}

if ( 'yes' === $this->toggleSupport && ! ( empty( $this->ReceiverZELLEEmail ) && empty( $this->ReceiverZELLENo ) ) ) {
	$checkout_html .= '<p>' . esc_html__( 'If you are having an issue', WCZELLE_PLUGIN_TEXT_DOMAIN ) . ', ' . wp_kses_post( ( $call ? $call : '' ) ) . wp_kses_post( ( $email ? $email : '' ) ) . '</p>';
}
if ( 'yes' === $this->toggleTutorial ) {
	$checkout_html .= '<p><a href="https://theafricanboss.com/zelledemo" style="text-decoration: underline" target="_blank">' . esc_html__( 'See this 1min video demo explaining how this works', WCZELLE_PLUGIN_TEXT_DOMAIN ) . '.</a></p>';
}
if ( 'yes' === $this->toggleCredits ) {
	$checkout_html .= '<p><a href="https://theafricanboss.com/zelle" style="text-decoration: underline;" target="_blank">' . sprintf( esc_html__( 'Powered by %s', WCZELLE_PLUGIN_TEXT_DOMAIN ), 'The African Boss' ) . '</a></p>';
}

do_action( 'woocommerce_form_end', $this->id );
$checkout_html .= '<div class="clear"></div></fieldset>';
echo $checkout_html;
