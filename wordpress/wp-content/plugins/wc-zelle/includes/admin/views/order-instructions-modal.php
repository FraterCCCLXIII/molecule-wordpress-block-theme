<?php
/**
 * Admin order screen: view Zelle payment instructions modal.
 *
 * @package wc-zelle
 * @var WC_Zelle_Gateway $gateway Gateway instance.
 * @var WC_Order         $order   Order.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bank = trim( (string) $gateway->receiver_bank_name );
$memo_text = method_exists( $gateway, 'wc_zelle_get_memo_text_resolved' )
	? $gateway->wc_zelle_get_memo_text_resolved( $order )
	: ( ( 'yes' === $gateway->memo_order_number ) ? $gateway->wc_zelle_get_memo_text( $order ) : '' );
if ( '' === $memo_text && is_a( $order, 'WC_Order' ) ) {
	$memo_text = sprintf(
		/* translators: %s: WooCommerce order number */
		__( 'Order %s', WCZELLE_PLUGIN_TEXT_DOMAIN ),
		$order->get_order_number()
	);
}

$phone     = trim( (string) $gateway->ReceiverZELLENo );
$recipient = trim( (string) $gateway->ReceiverZelleOwner );
$email     = trim( (string) $gateway->ReceiverZELLEEmail );
$zelle_tag = trim( (string) $gateway->ReceiverZelleTag );

if ( '' === $recipient ) {
	if ( '' !== $email ) {
		$recipient = $email;
	} elseif ( '' !== $phone ) {
		$recipient = $phone;
	} else {
		$recipient = __( '— Configure recipient in WooCommerce → Settings → Payments → Zelle', WCZELLE_PLUGIN_TEXT_DOMAIN );
	}
}

$amount_plain = wp_strip_all_tags( $order->get_formatted_order_total() );
$copy_rows    = array(
	array(
		'label' => __( 'Recipient name', WCZELLE_PLUGIN_TEXT_DOMAIN ),
		'value' => $recipient,
	),
);

if ( '' !== $zelle_tag ) {
	$copy_rows[] = array(
		'label' => __( 'Zelle Tag (handle)', WCZELLE_PLUGIN_TEXT_DOMAIN ),
		'value' => $zelle_tag,
	);
}
if ( '' !== $email ) {
	$copy_rows[] = array(
		'label' => __( 'Recipient email', WCZELLE_PLUGIN_TEXT_DOMAIN ),
		'value' => $email,
	);
}
if ( '' !== $phone ) {
	$phone_value = $phone;
	if ( '' !== $bank ) {
		$phone_value .= ' (' . $bank . ')';
	}
	$copy_rows[] = array(
		'label' => __( 'Recipient phone', WCZELLE_PLUGIN_TEXT_DOMAIN ),
		'value' => $phone_value,
	);
}

$copy_rows[] = array(
	'label' => __( 'Amount to send', WCZELLE_PLUGIN_TEXT_DOMAIN ),
	'value' => $amount_plain,
);
$copy_rows[] = array(
	'label' => __( 'Zelle memo', WCZELLE_PLUGIN_TEXT_DOMAIN ),
	'value' => $memo_text,
);
?>
<div id="wc-zelle-admin-instructions-modal" class="wc-zelle-modal wc-zelle-admin-modal" role="dialog" aria-modal="true" aria-labelledby="wc-zelle-admin-instructions-title" hidden>
	<div class="wc-zelle-modal__backdrop" data-wc-zelle-modal-close tabindex="-1"></div>
	<div class="wc-zelle-modal__panel" role="document">
		<button type="button" class="wc-zelle-modal__close" data-wc-zelle-modal-close aria-label="<?php echo esc_attr__( 'Close', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?>">&times;</button>
		<h2 id="wc-zelle-admin-instructions-title" class="wc-zelle-modal__title"><?php echo esc_html__( 'Zelle payment instructions', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?></h2>
		<p class="wc-zelle-admin-modal__intro">
			<?php
			echo esc_html(
				sprintf(
					/* translators: 1: order number, 2: formatted order total */
					__( 'Order #%1$s · %2$s', WCZELLE_PLUGIN_TEXT_DOMAIN ),
					$order->get_order_number(),
					$amount_plain
				)
			);
			?>
		</p>
		<ol class="wc-zelle-modal__steps">
			<li><?php echo esc_html__( 'Open your bank app or Zelle app.', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?></li>
			<li><?php echo esc_html__( 'Send the amount below to the recipient using the memo exactly as shown.', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?></li>
			<li><?php echo esc_html__( 'We will confirm the order when payment is received.', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?></li>
		</ol>
		<div class="wc-zelle-admin-copy-list">
			<?php foreach ( $copy_rows as $row ) : ?>
				<?php if ( '' === trim( (string) $row['value'] ) ) : ?>
					<?php continue; ?>
				<?php endif; ?>
				<div class="wc-zelle-admin-copy-row">
					<label class="wc-zelle-admin-copy-row__label"><?php echo esc_html( $row['label'] ); ?></label>
					<div class="wc-zelle-modal__memo-row wc-zelle-admin-copy-row__controls">
						<input type="text" readonly class="wc-zelle-memo-copytxt" value="<?php echo esc_attr( $row['value'] ); ?>" aria-label="<?php echo esc_attr( $row['label'] ); ?>" />
						<button type="button" class="button wc-zelle-memo-copybtn"><?php echo esc_html__( 'Copy', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?></button>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php if ( function_exists( 'wc_zelle_instructions_order_section' ) ) : ?>
			<div class="wc-zelle-admin-modal__details">
				<?php echo wc_zelle_instructions_order_section( $gateway, $order, 'thankyou' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder ?>
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $gateway->store_instructions ) ) : ?>
			<div class="wc-zelle-admin-modal__store-instructions"><?php echo wp_kses_post( $gateway->store_instructions ); ?></div>
		<?php endif; ?>
	</div>
</div>
