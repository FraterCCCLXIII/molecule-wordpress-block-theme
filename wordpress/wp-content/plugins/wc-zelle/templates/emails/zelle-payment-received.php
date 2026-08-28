<?php
/**
 * Zelle payment received — HTML email.
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

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p>
<?php
if ( ! empty( $order->get_billing_first_name() ) ) {
	printf(
		/* translators: %s: Customer first name */
		esc_html__( 'Hi %s,', WCZELLE_PLUGIN_TEXT_DOMAIN ),
		esc_html( $order->get_billing_first_name() )
	);
} else {
	esc_html_e( 'Hi,', WCZELLE_PLUGIN_TEXT_DOMAIN );
}
?>
</p>
<p><?php esc_html_e( 'We have received your Zelle payment. Your order is confirmed and will be processed according to our usual timelines.', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?></p>

<?php
/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 */
do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 */
do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

if ( ! empty( $additional_content ) ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );
