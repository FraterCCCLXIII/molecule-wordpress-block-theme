<?php
/**
 * Admin order screen: resend Zelle payment instructions confirmation modal.
 *
 * @package wc-zelle
 * @var WC_Order $order Order.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$default_email = $order->get_billing_email();
?>
<div id="wc-zelle-admin-resend-modal" class="wc-zelle-modal wc-zelle-admin-modal" role="dialog" aria-modal="true" aria-labelledby="wc-zelle-admin-resend-title" hidden>
	<div class="wc-zelle-modal__backdrop" data-wc-zelle-modal-close tabindex="-1"></div>
	<div class="wc-zelle-modal__panel wc-zelle-admin-modal__panel--compact" role="document">
		<button type="button" class="wc-zelle-modal__close" data-wc-zelle-modal-close aria-label="<?php echo esc_attr__( 'Close', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?>">&times;</button>
		<h2 id="wc-zelle-admin-resend-title" class="wc-zelle-modal__title"><?php echo esc_html__( 'Resend Zelle payment instructions', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?></h2>
		<p class="wc-zelle-admin-modal__intro">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: order number */
					__( 'Send payment instructions for order #%s to the email below.', WCZELLE_PLUGIN_TEXT_DOMAIN ),
					$order->get_order_number()
				)
			);
			?>
		</p>
		<form id="wc-zelle-admin-resend-form" class="wc-zelle-admin-resend-form" novalidate>
			<p class="form-field form-field-wide">
				<label for="wc-zelle-admin-resend-email"><?php echo esc_html__( 'Email address', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?></label>
				<input type="email" id="wc-zelle-admin-resend-email" name="email" class="regular-text" value="<?php echo esc_attr( $default_email ); ?>" required autocomplete="email" />
			</p>
			<p class="wc-zelle-admin-resend-form__actions">
				<button type="button" class="button" data-wc-zelle-modal-close><?php echo esc_html__( 'Cancel', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?></button>
				<button type="submit" class="button button-primary" id="wc-zelle-admin-resend-submit"><?php echo esc_html__( 'Send instructions', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?></button>
			</p>
			<p class="wc-zelle-admin-resend-form__status" id="wc-zelle-admin-resend-status" role="status" aria-live="polite" hidden></p>
		</form>
	</div>
</div>
