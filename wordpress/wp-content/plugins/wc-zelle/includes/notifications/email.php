<?php
/**
 * Zelle instructions in customer email (on-hold).
 *
 * @package wc-zelle
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email_html = '';

if ( function_exists( 'wc_zelle_instructions_order_section' ) ) {
	$email_html .= wc_zelle_instructions_order_section( $this, $order, 'email' );
} else {
	$total = $order->get_formatted_order_total();
	$email_html .= '<p>' . sprintf( wp_kses_post( __( 'Send the requested total via %s or from your bank', WCZELLE_PLUGIN_TEXT_DOMAIN ) ), '<a style="color: #6d1fd4" href="https://zellepay.com/" target="_blank">Zelle</a>' ) . '.</p>';
	$email_html .= '<p>' . esc_html__( 'Here are the Zelle details you should know for the transfer', WCZELLE_PLUGIN_TEXT_DOMAIN ) . ':</p>';
	$email_html .= '<p>' . sprintf( esc_html__( '%s Name', WCZELLE_PLUGIN_TEXT_DOMAIN ), 'Zelle' ) . ': <strong>' . esc_html( $this->ReceiverZelleOwner ) . '</strong><br>';
	$fallback_zelle_tag = trim( (string) $this->ReceiverZelleTag );
	if ( $fallback_zelle_tag !== '' ) {
		$email_html .= esc_html__( 'Zelle Tag (handle)', WCZELLE_PLUGIN_TEXT_DOMAIN ) . ': <strong>' . esc_html( $fallback_zelle_tag ) . '</strong><br>';
	}
	$email_html .= sprintf( esc_html__( '%s Email', WCZELLE_PLUGIN_TEXT_DOMAIN ), 'Zelle' ) . ': <strong>' . esc_html( $this->ReceiverZELLEEmail ) . '</strong><br>';
	$email_html .= sprintf( esc_html__( '%s Phone', WCZELLE_PLUGIN_TEXT_DOMAIN ), 'Zelle' ) . ': <strong>' . esc_html( $this->ReceiverZELLENo ) . '</strong></p>';
	if ( method_exists( $this, 'wc_zelle_get_memo_text_resolved' ) ) {
		$memo_line = $this->wc_zelle_get_memo_text_resolved( $order );
		if ( $memo_line !== '' ) {
			$email_html .= '<p><strong>' . esc_html__( 'Zelle memo (use exactly):', WCZELLE_PLUGIN_TEXT_DOMAIN ) . '</strong> ' . esc_html( $memo_line ) . '</p>';
		}
	}
}

if ( ! empty( $this->store_instructions ) ) {
	$email_html .= '<p>' . wp_kses_post( $this->store_instructions ) . '</p>';
}
echo $email_html;
