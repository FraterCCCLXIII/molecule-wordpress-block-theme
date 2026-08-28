<?php
/**
 * Admin: Connect store to emailreceipts.io (automated Zelle order updates).
 *
 * @package wc-zelle
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $current_user;

$gateway           = new WC_Zelle_Gateway();
$connect_btn_label = sprintf(
	/* translators: %s: site name */
	__( 'Connect %s to emailreceipts.io', WCZELLE_PLUGIN_TEXT_DOMAIN ),
	get_bloginfo( 'name' )
);

$store_name = get_bloginfo( 'name' );
?>
<div class="wrap wc-zelle-email-receipts">
	<h1><?php echo esc_html__( 'Automated Zelle order updates', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?></h1>
	<p class="wc-zelle-email-receipts__lead">
		<?php
		echo esc_html(
			sprintf(
				/* translators: %s: payment method title (e.g. Zelle) */
				__( 'Link %s with emailreceipts.io so payment notifications can move matching WooCommerce orders from on-hold to processing or completed.', WCZELLE_PLUGIN_TEXT_DOMAIN ),
				$gateway->method_title
			)
		);
		?>
	</p>

	<div class="wc-zelle-email-receipts__intro">
		<h2><?php echo esc_html__( 'How it works', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?></h2>
		<ol class="wc-zelle-email-receipts__steps">
			<li>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: gateway title */
						__( 'emailreceipts.io reads Zelle-related emails and pulls order and payment details for your %s payouts.', WCZELLE_PLUGIN_TEXT_DOMAIN ),
						$gateway->method_title
					)
				);
				?>
			</li>
			<li>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: store name */
						__( 'That data is sent securely to %s so WooCommerce can find the right order and verify the amount.', WCZELLE_PLUGIN_TEXT_DOMAIN ),
						$store_name
					)
				);
				?>
			</li>
			<li>
				<?php echo esc_html__( 'Physical orders can move to processing; digital orders can complete. Customers receive the usual WooCommerce emails.', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?>
			</li>
		</ol>
	</div>

	<div class="wc-zelle-email-receipts__panel">
		<div class="wc-zelle-email-receipts__panel-header">
			<h2><?php echo esc_html__( 'Connect to emailreceipts.io', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?></h2>
		</div>

		<div class="notice notice-warning wc-zelle-email-receipts__alert">
			<p>
				<strong><?php echo esc_html__( 'Use your real Zelle bank', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?></strong>
				<?php echo esc_html__( 'Only emails from the financial institution you choose below are used to match payments.', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?>
			</p>
		</div>

		<form
			id="store-connect-form"
			class="wc-zelle-email-receipts__form"
			action="https://emailreceipts.io/store/connect?ref=<?php echo esc_attr( urlencode( WCZELLE_PLUGIN_SLUG ) ); ?>"
			method="post"
			target="_blank"
		>
			<input type="hidden" name="accountid" id="accountid" value="<?php echo esc_attr( $gateway->ReceiverZELLEEmail ); ?>" />

			<div class="wc-zelle-email-receipts__field">
				<label for="accountname">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: Zelle (or gateway title) */
							__( '%s account name', WCZELLE_PLUGIN_TEXT_DOMAIN ),
							$gateway->method_title
						)
					);
					?>
					<span class="description"><?php echo esc_html__( '(from WooCommerce Zelle settings)', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?></span>
				</label>
				<input
					type="text"
					name="accountname"
					id="accountname"
					class="regular-text wc-zelle-field-readonly"
					readonly
					required
					placeholder="<?php esc_attr_e( 'Configure in WooCommerce → Settings → Payments → Zelle', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?>"
					value="<?php echo esc_attr( $gateway->ReceiverZelleOwner ); ?>"
				/>
			</div>

			<div class="wc-zelle-email-receipts__field">
				<label for="accountemail">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: Zelle (or gateway title) */
							__( '%s account email', WCZELLE_PLUGIN_TEXT_DOMAIN ),
							$gateway->method_title
						)
					);
					?>
					<span class="description"><?php echo esc_html__( '(from WooCommerce Zelle settings)', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?></span>
				</label>
				<input
					type="text"
					name="accountemail"
					id="accountemail"
					class="regular-text wc-zelle-field-readonly"
					readonly
					required
					placeholder="<?php esc_attr_e( 'Configure in WooCommerce → Settings → Payments → Zelle', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?>"
					value="<?php echo esc_attr( $gateway->ReceiverZELLEEmail ); ?>"
				/>
			</div>

			<div class="wc-zelle-email-receipts__field">
				<label for="institutionname"><?php echo esc_html__( 'Financial institution name', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?></label>
				<p class="description">
					<?php echo esc_html__( 'The bank or credit union you use for Zelle (e.g. Chase, Bank of America).', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?>
				</p>
				<input type="text" name="institutionname" id="institutionname" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. First National Bank', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?>" required />
			</div>

			<div class="wc-zelle-email-receipts__field">
				<label for="institutionemail"><?php echo esc_html__( 'Institution notification email', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?></label>
				<p class="description">
					<?php echo esc_html__( 'Sender address from your bank’s Zelle receipt or alert emails (open a recent Zelle email and copy the “From” address).', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?>
				</p>
				<input type="email" name="institutionemail" id="institutionemail" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. alerts@yourbank.com', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?>" required />
			</div>

			<input type="hidden" name="fname" id="fname" value="<?php echo esc_attr( get_user_meta( $current_user->ID, 'first_name', true ) ); ?>" />
			<input type="hidden" name="lname" id="lname" value="<?php echo esc_attr( get_user_meta( $current_user->ID, 'last_name', true ) ); ?>" />
			<input type="hidden" name="email" id="email" value="<?php echo esc_attr( get_bloginfo( 'admin_email' ) ); ?>" />
			<input type="hidden" name="phone" id="phone" value="<?php echo esc_attr( get_user_meta( $current_user->ID, 'billing_phone', true ) ); ?>" />
			<input type="hidden" name="name" id="name" value="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" />
			<input type="hidden" name="domain" id="domain" value="<?php echo esc_attr( get_site_url() ); ?>" />
			<input type="hidden" name="thumbnailUrl" id="thumbnailUrl" value="<?php echo esc_attr( get_site_icon_url() ); ?>" />
			<input type="hidden" name="institution" id="institution" value="Zelle" />
			<input type="hidden" name="webhook" id="webhook" value="<?php echo esc_attr( $gateway->ZelleForwardingURL ); ?>" />
			<input type="hidden" name="extension" id="extension" value="<?php echo esc_attr( WCZELLE_PLUGIN_SLUG ); ?>" />
			<input type="hidden" name="key" id="key" value="" />

			<?php wp_nonce_field( 'connect_store_to_emailreceipts' ); ?>

			<p class="wc-zelle-email-receipts__submit">
				<?php
				submit_button(
					$connect_btn_label,
					'primary large',
					'submit',
					false,
					array(
						'data-wait' => __( 'Please wait…', WCZELLE_PLUGIN_TEXT_DOMAIN ),
					)
				);
				?>
			</p>
		</form>
	</div>
</div>

<script>
(function () {
	var form = document.getElementById('store-connect-form');
	if (!form) return;
	form.addEventListener('submit', function (e) {
		var fields = form.querySelectorAll('.wc-zelle-field-readonly');
		var i, v, empty = 0;
		for (i = 0; i < fields.length; i++) {
			v = fields[i].value ? fields[i].value.trim() : '';
			if (!v) empty++;
		}
		if (empty > 0) {
			e.preventDefault();
			window.alert(<?php echo wp_json_encode( __( 'Please set the Zelle name and email in WooCommerce → Settings → Payments → Zelle before connecting.', WCZELLE_PLUGIN_TEXT_DOMAIN ) ); ?>);
		}
	});
})();
</script>
