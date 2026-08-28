<?php
/**
 * Webhook handler: mark paid when emailreceipts.io confirms a Zelle receipt (replaces free-tier “upgrade required” stub).
 *
 * @package wc-zelle
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$update_order = '';
$response_code = 200;

if ( ! empty( $order ) && is_a( $order, 'WC_Order' ) && $order->get_payment_method() === 'zelle' ) {
	if ( $order->has_status( array( 'on-hold', 'pending' ) ) ) {
		$txn_ref = ! empty( $note ) ? sanitize_text_field( $note ) : 'zelle-emailreceipt';
		$order->payment_complete( $txn_ref );
		$order->add_order_note(
			sprintf(
				/* translators: 1: amount string, 2: transaction note */
				__( 'Zelle payment confirmed via email receipt. %1$s Note: %2$s', WCZELLE_PLUGIN_TEXT_DOMAIN ),
				isset( $money ) ? wp_kses_post( $money ) : '',
				isset( $note ) ? wp_kses_post( $note ) : ''
			),
			false
		);
		$update_order .= sprintf(
			/* translators: %d: order ID */
			__( 'Order #%d marked as paid.', WCZELLE_PLUGIN_TEXT_DOMAIN ) . "\n",
			(int) $order->get_id()
		);
	} else {
		$update_order .= sprintf(
			/* translators: 1: order id, 2: order status */
			__( 'Order #%1$d skipped (already %2$s).', WCZELLE_PLUGIN_TEXT_DOMAIN ) . "\n",
			(int) $order->get_id(),
			$order->get_status()
		);
	}
} else {
	$response_code = 404;
	$update_order .= __( "No matching Zelle order found to update.\n", WCZELLE_PLUGIN_TEXT_DOMAIN );
}

$message .= wp_kses_post( $update_order );
$message_array['update_order'] = wp_kses_post( $update_order );

if ( ! empty( $receipt_post_id ) ) {
	$post_dump = print_r( $body, true );
	$receipt_post = get_post( $receipt_post_id );
	if ( $receipt_post ) {
		$receipt_post->post_title .= wp_kses_post( ' — ' . trim( $update_order ) );
		$receipt_post->post_content .= wp_kses_post( '<br>' . $update_order . '<br><br>' . ( isset( $email_subject ) ? $email_subject : '' ) . '<br><br>' . $post_dump );
		wp_update_post( $receipt_post );
	}
}

http_response_code( $response_code );
