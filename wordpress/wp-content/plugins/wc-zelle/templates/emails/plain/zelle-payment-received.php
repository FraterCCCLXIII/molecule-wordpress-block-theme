<?php
/**
 * Zelle payment received — plain text email.
 *
 * @package wc-zelle
 * @var WC_Order  $order
 * @var string    $email_heading
 * @var string    $additional_content
 * @var bool      $sent_to_admin
 * @var bool      $plain_text
 * @var WC_Email  $email
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo '= ' . wp_strip_all_tags( $email_heading ) . " =\n\n";

if ( ! empty( $order->get_billing_first_name() ) ) {
	echo sprintf(
		/* translators: %s: Customer first name */
		__( 'Hi %s,', WCZELLE_PLUGIN_TEXT_DOMAIN ),
		$order->get_billing_first_name()
	) . "\n\n";
} else {
	echo __( 'Hi,', WCZELLE_PLUGIN_TEXT_DOMAIN ) . "\n\n";
}

echo __( 'We have received your Zelle payment. Your order is confirmed and will be processed according to our usual timelines.', WCZELLE_PLUGIN_TEXT_DOMAIN ) . "\n\n";

echo "----------------------------------------\n\n";

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

if ( $additional_content ) {
	echo wp_strip_all_tags( $additional_content ) . "\n\n";
}

echo "\n----------------------------------------\n\n";
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );
