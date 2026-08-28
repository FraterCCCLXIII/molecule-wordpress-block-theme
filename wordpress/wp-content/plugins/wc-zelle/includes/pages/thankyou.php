<?php
/**
 * Thank you page: notices, optional modal, structured payment details, QR.
 *
 * @package wc-zelle
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$amount = $order->get_total();
$total = $order->get_formatted_order_total();

$show_modal = ( 'yes' === $this->show_zelle_modal );

$thankyou_html = '';
$thankyou_html .= '<div id="wc-' . esc_attr( $this->id ) . '-form" class="wc-zelle-thankyou-wrap" data-plugin="' . wp_kses_post( WCZELLE_PLUGIN_VERSION ) . '">';

$notice = $this->zelle_notice ? wp_kses_post( $this->zelle_notice ) : '';

if ( $show_modal ) {
	if ( $notice ) {
		$thankyou_html .= '<div class="wc-zelle-thankyou-notice">' . $notice . '</div>';
	}
	$thankyou_html .= '<p class="wc-zelle-order-summary">' . sprintf(
		/* translators: 1: order number, 2: formatted order total (plain text) */
		esc_html__( 'Order %1$s · Total: %2$s', WCZELLE_PLUGIN_TEXT_DOMAIN ),
		esc_html( $order->get_order_number() ),
		wp_strip_all_tags( $total )
	) . '</p>';
	if ( 'no' === $this->zelle_modal_auto_open ) {
		$thankyou_html .= '<p><button type="button" class="button wc-zelle-modal-open" id="wc-zelle-modal-open-btn">' . esc_html__( 'View Zelle payment instructions', WCZELLE_PLUGIN_TEXT_DOMAIN ) . '</button></p>';
	}
	ob_start();
	require WCZELLE_PLUGIN_DIR . 'includes/pages/thankyou-modal.php';
	$thankyou_html .= ob_get_clean();
} else {
	if ( $notice ) {
		$thankyou_html .= '<div class="wc-zelle-thankyou-notice">' . $notice . '</div>';
	}
	if ( function_exists( 'wc_zelle_instructions_order_section' ) ) {
		$thankyou_html .= wc_zelle_instructions_order_section( $this, $order, 'thankyou' );
	}
}

if ( ! empty( $this->store_instructions ) ) {
	$thankyou_html .= '<p>' . wp_kses_post( $this->store_instructions ) . '</p>';
}

// QR in modal when modal is on; otherwise show below (no duplicate).
if ( 'yes' === $this->enableQRCode && ! empty( trim( (string) $this->ZelleQRCode ) ) && ! $show_modal ) {
	$thankyou_html .= '<div class="text-center my-3 wc-zelle-thankyou-qr-wrap">';
	$thankyou_html .= '<p class="wc-zelle-qr-phone-msg">' . esc_html__( 'Use your phone to pay with Zelle—scan the code below or pay in your bank app with Zelle.', WCZELLE_PLUGIN_TEXT_DOMAIN ) . '</p>';
	$thankyou_html .= $this->wc_zelle_qrcode( $amount, 'advanced' );
	$thankyou_html .= '</div>';
}

$thankyou_html .= '</div><br><hr><br>';
echo $thankyou_html;
