<?php
/**
 * Front-end scripts/styles for checkout (copy buttons + optional dynamic QR canvas).
 *
 * @package wc-zelle
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( 'no' === $this->enabled ) {
	return;
}
if ( ! is_checkout() ) {
	return;
}

wp_register_style( 'wc_zelle_checkout', WCZELLE_PLUGIN_DIR_URL . 'assets/css/checkout.css' );
wp_enqueue_style( 'wc_zelle_checkout' );

$design = (string) $this->display_zelle;
if ( 'no' === $design ) {
	$design = '1';
} elseif ( 'yes' === $design ) {
	$design = '2';
}
if ( ! in_array( $design, array( '1', '2', '3' ), true ) ) {
	$design = '2';
}

$use_copy_layout = in_array( $design, array( '2', '3' ), true );
$use_qr_canvas = ( 'yes' === $this->enableQRCode ) && ! empty( trim( (string) $this->ZelleQRCode ) ) && $use_copy_layout;

if ( $use_copy_layout ) {
	$copy_js = 'copy.js';
	if ( ! wp_script_is( $copy_js, 'enqueued' ) ) {
		wp_register_script(
			$copy_js,
			WCZELLE_PLUGIN_DIR_URL . 'assets/js/' . $copy_js,
			array( 'jquery' ),
			null,
			true
		);
		wp_enqueue_script( $copy_js );
	}
}

if ( $use_qr_canvas ) {
	$qrcode_styling = 'qr-code-styling.min.js';
	if ( ! wp_script_is( $qrcode_styling, 'enqueued' ) ) {
		wp_register_script( $qrcode_styling, WCZELLE_PLUGIN_DIR_URL . 'assets/js/' . $qrcode_styling );
		wp_enqueue_script( $qrcode_styling );
	}
	$qrcode_generator = 'qr-code-generator.js';
	if ( ! wp_script_is( $qrcode_generator, 'enqueued' ) ) {
		wp_register_script( $qrcode_generator, WCZELLE_PLUGIN_DIR_URL . 'assets/js/' . $qrcode_generator );
		wp_enqueue_script( $qrcode_generator );
	}
	wp_enqueue_script(
		'wc_zelle_qrcode',
		WCZELLE_PLUGIN_DIR_URL . 'assets/js/qrcode.js',
		array( 'jquery', $qrcode_styling, $qrcode_generator ),
		null,
		true
	);
	$payment_url = $this->wc_zelle_url( 1 );
	$wc_zelle_qrcode = array(
		'url' => $payment_url,
	);
	$wc_zelle_qrcode['logo'] = '';
	$wc_zelle_qrcode['width'] = 150;
	$wc_zelle_qrcode['height'] = 150;
	$wc_zelle_qrcode['darkcolor'] = '#000000';
	$wc_zelle_qrcode['lightcolor'] = '#ffffff';
	$wc_zelle_qrcode['backgroundcolor'] = '#ffffff';
	$wc_zelle_qrcode['dotsType'] = 'dots';
	$wc_zelle_qrcode['cornersSquareType'] = 'extra-rounded';
	$wc_zelle_qrcode['cornersDotType'] = 'square';
	wp_localize_script( 'wc_zelle_qrcode', 'wc_zelle_qrcode', $wc_zelle_qrcode );
}
