<?php
/**
 * Structured Zelle payment instructions (checkout vs order context).
 *
 * @package wc-zelle
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rows for a definition list (dt/dd).
 *
 * @param array $rows List of [ 'label' => string, 'value' => string ].
 * @param string $extra_class Optional BEM modifier.
 * @return string
 */
function wc_zelle_instructions_render_dl( array $rows, $extra_class = '' ) {
	if ( empty( $rows ) ) {
		return '';
	}
	$class = 'wc-zelle-instructions__list';
	if ( $extra_class !== '' ) {
		$class .= ' ' . sanitize_html_class( $extra_class );
	}
	$html = '<dl class="' . esc_attr( $class ) . '">';
	foreach ( $rows as $row ) {
		if ( empty( $row['label'] ) || ! isset( $row['value'] ) ) {
			continue;
		}
		$html .= '<dt class="wc-zelle-instructions__term">' . esc_html( $row['label'] ) . '</dt>';
		$html .= '<dd class="wc-zelle-instructions__def">' . wp_kses_post( $row['value'] ) . '</dd>';
	}
	$html .= '</dl>';
	return $html;
}

/**
 * HTML emails: table-based layout with inline styles (many clients ignore style blocks).
 *
 * @param array  $rows    Same shape as {@see wc_zelle_instructions_render_dl()}.
 * @param string $footer  HTML (already escaped where needed).
 * @param string $heading Section title.
 * @return string
 */
function wc_zelle_instructions_render_email_block( array $rows, $footer, $heading ) {
	$card = 'margin:16px 0;padding:0;border:1px solid #e5e7eb;border-radius:10px;background-color:#f9fafb;overflow:hidden;';
	$title = 'margin:0 0 14px;padding:0;font-family:Helvetica,Arial,sans-serif;font-size:17px;font-weight:600;line-height:1.3;color:#111827;';
	$table = 'width:100%;border-collapse:collapse;margin:0;font-family:Helvetica,Arial,sans-serif;font-size:14px;line-height:1.45;color:#374151;';
	$th = 'padding:10px 16px 4px 16px;vertical-align:top;font-weight:600;color:#111827;width:38%;';
	$td = 'padding:4px 16px 12px 16px;vertical-align:top;border-bottom:1px solid #e5e7eb;';
	$td_last = 'padding:4px 16px 14px 16px;vertical-align:top;';
	$n = count( $rows );
	$html = '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="' . esc_attr( $card ) . '">';
	$html .= '<tr><td style="padding:18px 16px 0 16px;">';
	$html .= '<h2 style="' . esc_attr( $title ) . '">' . esc_html( $heading ) . '</h2>';
	$html .= '<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="' . esc_attr( $table ) . '">';
	$i = 0;
	foreach ( $rows as $row ) {
		if ( empty( $row['label'] ) || ! isset( $row['value'] ) ) {
			continue;
		}
		++$i;
		$cell = ( $i === $n ) ? $td_last : $td;
		// Second row is always "Amount to send" in this template — emphasize for quick scanning.
		if ( 2 === $i ) {
			$cell .= ' font-size:18px;font-weight:600;color:#111827;';
		}
		$html .= '<tr>';
		$html .= '<th scope="row" style="' . esc_attr( $th ) . '">' . esc_html( $row['label'] ) . '</th>';
		$html .= '<td style="' . esc_attr( $cell ) . '">' . wp_kses_post( $row['value'] ) . '</td>';
		$html .= '</tr>';
	}
	$html .= '</table>';
	if ( $footer !== '' ) {
		$html .= '<div style="padding:0 16px 18px 16px;">' . $footer . '</div>';
	} else {
		$html .= '<div style="padding-bottom:8px;"></div>';
	}
	$html .= '</td></tr></table>';
	return $html;
}

/**
 * Checkout (no order yet): amount, recipient, order # + memo explained.
 *
 * @param WC_Zelle_Gateway $gateway Gateway instance.
 * @return string
 */
function wc_zelle_instructions_checkout_section( $gateway ) {
	if ( ! is_object( $gateway ) ) {
		return '';
	}
	$cart = function_exists( 'WC' ) && WC()->cart ? WC()->cart : null;
	$total_fmt = $cart ? $cart->get_total() : '';

	$rows = array(
		array(
			'label' => __( 'Amount to send', WCZELLE_PLUGIN_TEXT_DOMAIN ),
			'value' => $total_fmt ? wp_kses_post( $total_fmt ) : '—',
		),
		array(
			'label' => __( 'Recipient name (Zelle)', WCZELLE_PLUGIN_TEXT_DOMAIN ),
			'value' => $gateway->ReceiverZelleOwner ? esc_html( $gateway->ReceiverZelleOwner ) : '—',
		),
	);
	$checkout_zelle_tag = trim( (string) $gateway->ReceiverZelleTag );
	if ( $checkout_zelle_tag !== '' ) {
		$rows[] = array(
			'label' => __( 'Zelle Tag (handle)', WCZELLE_PLUGIN_TEXT_DOMAIN ),
			'value' => '<strong>' . esc_html( $checkout_zelle_tag ) . '</strong>',
		);
	}
	if ( ! empty( $gateway->ReceiverZELLEEmail ) ) {
		$rows[] = array(
			'label' => __( 'Recipient email', WCZELLE_PLUGIN_TEXT_DOMAIN ),
			'value' => esc_html( $gateway->ReceiverZELLEEmail ),
		);
	}
	if ( ! empty( $gateway->ReceiverZELLENo ) ) {
		$rows[] = array(
			'label' => __( 'Recipient phone', WCZELLE_PLUGIN_TEXT_DOMAIN ),
			'value' => esc_html( $gateway->ReceiverZELLENo ),
		);
	}
	$bank = trim( (string) $gateway->receiver_bank_name );
	if ( $bank !== '' ) {
		$rows[] = array(
			'label' => __( 'Bank / institution (if your app asks)', WCZELLE_PLUGIN_TEXT_DOMAIN ),
			'value' => esc_html( $bank ),
		);
	}
	$rows[] = array(
		'label' => __( 'Order number', WCZELLE_PLUGIN_TEXT_DOMAIN ),
		'value' => __( 'Your order number is created when you complete checkout. It appears on the confirmation page and in your order email—use it in Zelle so we can match your payment.', WCZELLE_PLUGIN_TEXT_DOMAIN ),
	);
	if ( 'yes' === $gateway->memo_order_number ) {
		$rows[] = array(
			'label' => __( 'Zelle memo / note', WCZELLE_PLUGIN_TEXT_DOMAIN ),
			'value' => __( 'After you place your order, the confirmation page shows the exact memo line to copy (it includes your order number). Paste that into the memo field in your bank or Zelle app.', WCZELLE_PLUGIN_TEXT_DOMAIN ),
		);
	} else {
		$rows[] = array(
			'label' => __( 'Payment reference / memo', WCZELLE_PLUGIN_TEXT_DOMAIN ),
			'value' => __( 'Include your order number from the confirmation page in the Zelle memo so we can match your payment.', WCZELLE_PLUGIN_TEXT_DOMAIN ),
		);
	}

	$heading = __( 'Complete your Zelle payment', WCZELLE_PLUGIN_TEXT_DOMAIN );
	$html = '<section class="wc-zelle-instructions wc-zelle-instructions--checkout" aria-label="' . esc_attr( $heading ) . '">';
	$html .= '<h3 class="wc-zelle-instructions__heading">' . esc_html( $heading ) . '</h3>';
	$html .= wc_zelle_instructions_render_dl( $rows, 'wc-zelle-instructions__list--checkout' );
	$html .= '</section>';

	return apply_filters( 'wc_zelle_checkout_instructions_html', $html, $gateway );
}

/**
 * After order: order #, amount, recipient, memo.
 *
 * @param WC_Zelle_Gateway $gateway Gateway.
 * @param WC_Order         $order   Order.
 * @param string           $context thankyou|email|note — minor wording tweaks.
 * @return string
 */
function wc_zelle_instructions_order_section( $gateway, $order, $context = 'thankyou' ) {
	if ( ! is_object( $gateway ) || ! is_a( $order, 'WC_Order' ) ) {
		return '';
	}

	$total = $order->get_formatted_order_total();
	$amount_value = wp_kses_post( $total );

	$rows = array(
		array(
			'label' => __( 'Order number', WCZELLE_PLUGIN_TEXT_DOMAIN ),
			'value' => '<strong>' . esc_html( $order->get_order_number() ) . '</strong>',
		),
		array(
			'label' => __( 'Amount to send', WCZELLE_PLUGIN_TEXT_DOMAIN ),
			'value' => $amount_value,
		),
		array(
			'label' => __( 'Recipient name (Zelle)', WCZELLE_PLUGIN_TEXT_DOMAIN ),
			'value' => $gateway->ReceiverZelleOwner ? esc_html( $gateway->ReceiverZelleOwner ) : '—',
		),
	);
	$order_zelle_tag = trim( (string) $gateway->ReceiverZelleTag );
	if ( $order_zelle_tag !== '' ) {
		$rows[] = array(
			'label' => __( 'Zelle Tag (handle)', WCZELLE_PLUGIN_TEXT_DOMAIN ),
			'value' => '<strong>' . esc_html( $order_zelle_tag ) . '</strong>',
		);
	}
	if ( ! empty( $gateway->ReceiverZELLEEmail ) ) {
		$rows[] = array(
			'label' => __( 'Recipient email', WCZELLE_PLUGIN_TEXT_DOMAIN ),
			'value' => esc_html( $gateway->ReceiverZELLEEmail ),
		);
	}
	if ( ! empty( $gateway->ReceiverZELLENo ) ) {
		$phone_line = esc_html( $gateway->ReceiverZELLENo );
		$bank = trim( (string) $gateway->receiver_bank_name );
		if ( $bank !== '' ) {
			$phone_line .= ' <span class="wc-zelle-instructions__sub">(' . esc_html( $bank ) . ')</span>';
		}
		$rows[] = array(
			'label' => __( 'Recipient phone', WCZELLE_PLUGIN_TEXT_DOMAIN ),
			'value' => $phone_line,
		);
	}

	if ( method_exists( $gateway, 'wc_zelle_get_memo_text_resolved' ) ) {
		$memo = $gateway->wc_zelle_get_memo_text_resolved( $order );
		if ( $memo !== '' ) {
			$rows[] = array(
				'label' => __( 'Zelle memo (paste exactly)', WCZELLE_PLUGIN_TEXT_DOMAIN ),
				'value' => '<strong>' . esc_html( $memo ) . '</strong>',
			);
		}
	}

	$footer = '';
	$deadline = ( is_object( $gateway ) && method_exists( $gateway, 'wc_zelle_get_payment_deadline_customer_notice' ) )
		? $gateway->wc_zelle_get_payment_deadline_customer_notice()
		: '';

	$heading = __( 'Your Zelle payment details', WCZELLE_PLUGIN_TEXT_DOMAIN );

	if ( 'email' === $context ) {
		$foot_style = 'margin:12px 0 0;padding:0;font-family:Helvetica,Arial,sans-serif;font-size:13px;line-height:1.5;color:#4b5563;';
		$deadline_style = 'margin:10px 0 0;padding:0;font-family:Helvetica,Arial,sans-serif;font-size:13px;line-height:1.5;color:#92400e;';
		$footer = '<p style="' . esc_attr( $foot_style ) . '">' . esc_html__( 'Include the order number and memo above in your Zelle payment so we can confirm your order quickly.', WCZELLE_PLUGIN_TEXT_DOMAIN ) . '</p>';
		if ( $deadline !== '' ) {
			$footer .= '<p style="' . esc_attr( $deadline_style ) . '">' . esc_html( $deadline ) . '</p>';
		}
		return apply_filters( 'wc_zelle_order_instructions_html', wc_zelle_instructions_render_email_block( $rows, $footer, $heading ), $gateway, $order, $context );
	}

	if ( 'note' === $context ) {
		$footer = '';
	} else {
		$footer = '<p class="wc-zelle-instructions__footer">' . esc_html__( 'Zelle transfers are usually fast. We will update your order when payment is received.', WCZELLE_PLUGIN_TEXT_DOMAIN ) . '</p>';
		if ( $deadline !== '' ) {
			$footer .= '<p class="wc-zelle-instructions__footer wc-zelle-instructions__deadline">' . esc_html( $deadline ) . '</p>';
		}
	}

	$htag = ( 'note' === $context ) ? 'h4' : 'h3';
	$html = '<section class="wc-zelle-instructions wc-zelle-instructions--order wc-zelle-instructions--' . esc_attr( $context ) . '" aria-label="' . esc_attr( $heading ) . '">';
	$html .= '<' . $htag . ' class="wc-zelle-instructions__heading">' . esc_html( $heading ) . '</' . $htag . '>';
	$html .= wc_zelle_instructions_render_dl( $rows, 'wc-zelle-instructions__list--order' );
	$html .= $footer;
	$html .= '</section>';

	return apply_filters( 'wc_zelle_order_instructions_html', $html, $gateway, $order, $context );
}

/**
 * Placeholders for admin order note template.
 *
 * @param WC_Order         $order   Order.
 * @param WC_Zelle_Gateway $gateway Gateway.
 * @return array<string, string>
 */
function wc_zelle_order_note_replacements( $order, $gateway ) {
	if ( ! is_a( $order, 'WC_Order' ) ) {
		return array();
	}
	$total = $order->get_formatted_order_total();
	$memo = ( is_object( $gateway ) && method_exists( $gateway, 'wc_zelle_get_memo_text_resolved' ) )
		? $gateway->wc_zelle_get_memo_text_resolved( $order )
		: '';

	return array(
		'**order_id**'       => (string) $order->get_id(),
		'**order_number**'   => $order->get_order_number(),
		'**order_total**'    => wp_strip_all_tags( $total ),
		'**order_memo**'     => $memo !== '' ? $memo : __( '—', WCZELLE_PLUGIN_TEXT_DOMAIN ),
		'**shop_name**'      => get_bloginfo( 'name' ),
		'**shop_email**'     => get_bloginfo( 'admin_email' ),
		'**shop_url**'       => get_site_url(),
		'**recipient_name**' => is_object( $gateway ) ? (string) $gateway->ReceiverZelleOwner : '',
		'**recipient_zelle_tag**' => is_object( $gateway ) ? trim( (string) $gateway->ReceiverZelleTag ) : '',
		'**recipient_phone**' => is_object( $gateway ) ? (string) $gateway->ReceiverZELLENo : '',
		'**recipient_email**' => is_object( $gateway ) ? (string) $gateway->ReceiverZELLEEmail : '',
	);
}
