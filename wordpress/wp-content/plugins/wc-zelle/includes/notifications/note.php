<?php
/**
 * Customer-visible order note after Zelle checkout.
 *
 * @package wc-zelle
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$total = $order->get_formatted_order_total();

$use_custom_note = $this->order_note && trim( wp_strip_all_tags( $this->order_note ) ) !== '';

if ( $use_custom_note && function_exists( 'wc_zelle_order_note_replacements' ) ) {
	$replacements = wc_zelle_order_note_replacements( $order, $this );
	uksort(
		$replacements,
		static function ( $a, $b ) {
			return strlen( $b ) - strlen( $a );
		}
	);
	$note = wp_kses_post(
		str_replace(
			array_keys( $replacements ),
			array_values( $replacements ),
			$this->order_note
		)
	);
} elseif ( function_exists( 'wc_zelle_instructions_order_section' ) ) {
	$note = wp_kses_post( wc_zelle_instructions_order_section( $this, $order, 'note' ) );
} else {
	$note = esc_html__( 'Your order was received!', WCZELLE_PLUGIN_TEXT_DOMAIN ) . '<br><br>' .
		sprintf(
			/* translators: %s: formatted order total */
			__( 'We are checking our Zelle to confirm that we received the %s you sent so we can start processing your order.', WCZELLE_PLUGIN_TEXT_DOMAIN ),
			'<strong style="text-transform:uppercase;">' . wp_kses_post( $total ) . '</strong>'
		) . '<br><br>' .
		esc_html__( 'Thank you for doing business with us', WCZELLE_PLUGIN_TEXT_DOMAIN ) . '!<br> ' .
		esc_html__( 'You will be updated regarding your order details soon', WCZELLE_PLUGIN_TEXT_DOMAIN ) . '<br><br>' .
		esc_html__( 'Kindest Regards', WCZELLE_PLUGIN_TEXT_DOMAIN ) . ',<br>' .
		wp_kses_post( get_bloginfo( 'name' ) ) . '<br>' .
		wp_kses_post( get_bloginfo( 'admin_email' ) ) . '<br>' .
		wp_kses_post( get_site_url() ) . '<br>';
}

$order->add_order_note( $note, true );
