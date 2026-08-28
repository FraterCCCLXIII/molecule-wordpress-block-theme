<?php
/**
 * Modal markup: Zelle payment steps (structure only; styles in thankyou.css).
 *
 * @package wc-zelle
 * @var WC_Zelle_Gateway $this Gateway instance.
 * @var WC_Order         $order Order.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bank = trim( (string) $this->receiver_bank_name );
$memo_text = method_exists( $this, 'wc_zelle_get_memo_text_resolved' )
	? $this->wc_zelle_get_memo_text_resolved( $order )
	: ( ( 'yes' === $this->memo_order_number ) ? $this->wc_zelle_get_memo_text( $order ) : '' );
if ( $memo_text === '' && is_a( $order, 'WC_Order' ) ) {
	$memo_text = sprintf(
		/* translators: %s: WooCommerce order number */
		__( 'Order %s', WCZELLE_PLUGIN_TEXT_DOMAIN ),
		$order->get_order_number()
	);
}

$phone = trim( (string) $this->ReceiverZELLENo );
$recipient = trim( (string) $this->ReceiverZelleOwner );
$email = trim( (string) $this->ReceiverZELLEEmail );
$zelle_tag = trim( (string) $this->ReceiverZelleTag );

if ( $recipient === '' ) {
	if ( $email !== '' ) {
		$recipient = $email;
	} elseif ( $phone !== '' ) {
		$recipient = $phone;
	} else {
		$recipient = __( '— Configure recipient in WooCommerce → Settings → Payments → Zelle', WCZELLE_PLUGIN_TEXT_DOMAIN );
	}
}
$show_phone_subline = ( $phone !== '' && $phone !== $recipient );

$total_formatted = $order->get_formatted_order_total();
?>
<div id="wc-zelle-payment-modal" class="wc-zelle-modal" role="dialog" aria-modal="true" aria-labelledby="wc-zelle-modal-title" hidden>
	<div class="wc-zelle-modal__backdrop" data-wc-zelle-modal-close tabindex="-1"></div>
	<div class="wc-zelle-modal__panel" role="document">
		<button type="button" class="wc-zelle-modal__close" data-wc-zelle-modal-close aria-label="<?php echo esc_attr__( 'Close', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?>">&times;</button>
		<h2 id="wc-zelle-modal-title" class="wc-zelle-modal__title"><?php echo esc_html__( 'Complete your payment via Zelle', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?></h2>
		<ol class="wc-zelle-modal__steps">
			<li><?php echo esc_html__( 'Open your bank app or Zelle app.', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?></li>
			<li>
				<?php echo esc_html__( 'Send to:', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?>
				<strong><?php echo esc_html( $recipient ); ?></strong>
				<?php if ( $show_phone_subline ) : ?>
					<br /><span class="wc-zelle-modal__sub"><?php echo esc_html__( 'Phone:', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?> <?php echo esc_html( $phone ); ?><?php echo $bank !== '' ? ' (' . esc_html( $bank ) . ')' : ''; ?></span>
				<?php endif; ?>
			</li>
			<?php if ( $zelle_tag !== '' ) : ?>
			<li>
				<?php echo esc_html__( 'Zelle Tag (handle):', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?>
				<strong><?php echo esc_html( $zelle_tag ); ?></strong>
			</li>
			<?php endif; ?>
			<li>
				<?php echo esc_html__( 'Amount:', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?>
				<strong><?php echo wp_kses_post( $total_formatted ); ?></strong>
			</li>
			<li>
				<?php echo esc_html__( 'Memo (required):', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?>
				<strong><?php echo esc_html( $memo_text ); ?></strong>
			</li>
		</ol>
		<?php
		if ( 'yes' === $this->enableQRCode && ! empty( trim( (string) $this->ZelleQRCode ) ) ) {
			$amount_for_qr = is_a( $order, 'WC_Order' ) ? $order->get_total() : 0;
			?>
		<div class="wc-zelle-modal__qr" aria-label="<?php echo esc_attr__( 'Zelle QR code', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?>">
			<p class="wc-zelle-qr-phone-msg"><?php echo esc_html__( 'Use your phone to pay with Zelle—scan the code below or pay in your bank app with Zelle.', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?></p>
			<?php echo $this->wc_zelle_qrcode( $amount_for_qr, 'advanced' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- gateway returns wp_kses_post HTML ?>
		</div>
			<?php
		}
		?>
		<div class="wc-zelle-modal__memo">
			<p class="wc-zelle-modal__memo-label"><?php echo esc_html__( 'Your memo should look like:', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?></p>
			<div class="wc-zelle-modal__memo-row">
				<input type="text" readonly class="wc-zelle-memo-copytxt" value="<?php echo esc_attr( $memo_text ); ?>" aria-label="<?php echo esc_attr__( 'Zelle memo text', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?>" />
				<button type="button" class="wc-zelle-memo-copybtn"><?php echo esc_html__( 'Copy', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?></button>
			</div>
		</div>
		<p class="wc-zelle-modal__footer"><?php echo esc_html__( 'Zelle transfers are typically instant. Your order will be confirmed once payment is received.', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?></p>
		<?php
		$wc_zelle_deadline_notice = method_exists( $this, 'wc_zelle_get_payment_deadline_customer_notice' ) ? $this->wc_zelle_get_payment_deadline_customer_notice() : '';
		if ( $wc_zelle_deadline_notice !== '' ) :
			?>
		<p class="wc-zelle-modal__deadline"><?php echo esc_html( $wc_zelle_deadline_notice ); ?></p>
		<?php endif; ?>
	</div>
</div>
