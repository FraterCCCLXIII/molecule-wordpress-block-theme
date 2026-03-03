<?php

/**
 * Settings:
 *
 * Add main menu settings page for GreenPay™
 */
function gmpg_add_admin_menu()
{
	error_log('gmpg_add_admin_menu triggered');
	add_menu_page('GreenPay™ Settings', 'GreenPay™', 'manage_options', 'greenpay_payment_gateway', 'gmpg_options_page');
}

/**
 * Settings:
 *
 * Initialize GreenPay™ settings and values
 */
function gmpg_settings_init()
{
	register_setting('greenpay_payment_gateway', 'gmpg_settings', array(
		"sanitize_callback" => "gmpg_settings_validate"
	));

	add_settings_section(
		'gmpg_settings_main_page',
		__("General Settings:", 'woocommerce-gateway-green-money'),
		'gmpg_settings_section_callback',
		'greenpay_payment_gateway'
	);

	add_settings_field(
		'gmpg_client_id',
		__('Client ID*', 'woocommerce-gateway-green-money'),
		'gmpg_client_id_render',
		'greenpay_payment_gateway',
		'gmpg_settings_main_page',
		array("class" => "gmpg_required gmpg_validation")
	);

	add_settings_field(
		'gmpg_api_password',
		__('API password*', 'woocommerce-gateway-green-money'),
		'gmpg_api_password_render',
		'greenpay_payment_gateway',
		'gmpg_settings_main_page',
		array("class" => "gmpg_required gmpg_validation")
	);

	add_settings_field(
		'gmpg_woo_rest_client_id',
		__('WooCommerce Rest Client ID', 'woocommerce-gateway-green-money'),
		'gmpg_woo_rest_client_id_render',
		'greenpay_payment_gateway',
		'gmpg_settings_main_page',
		array("class" => "gmpg_required gmpg_validation")
	);

	add_settings_field(
		'gmpg_woo_rest_client_secret',
		__('WooCommerce Rest Client Secret', 'woocommerce-gateway-green-money'),
		'gmpg_woo_rest_client_secret_render',
		'greenpay_payment_gateway',
		'gmpg_settings_main_page',
		array("class" => "gmpg_required gmpg_validation")
	);

	add_settings_section(
		'gmpg_settings_payment_method',
		__("Front End Display:", 'woocommerce-gateway-green-money'),
		'gmpg_settings_section_callback_payment',
		'greenpay_payment_gateway'
	);

	add_settings_field(
		'gmpg_title',
		__('Payment Method Title*', 'woocommerce-gateway-green-money'),
		'gmpg_title_render',
		'greenpay_payment_gateway',
		'gmpg_settings_payment_method',
		array("class" => "gmpg_required gmpg_validation")
	);

	add_settings_field(
		'gmpg_gateway_description',
		__('Description', 'woocommerce-gateway-green-money'),
		'gmpg_gateway_description_render',
		'greenpay_payment_gateway',
		'gmpg_settings_payment_method'
	);

	add_settings_field(
		'gmpg_extra_message',
		__('Extra message', 'woocommerce-gateway-green-money'),
		'gmpg_extra_message_render',
		'greenpay_payment_gateway',
		'gmpg_settings_payment_method'
	);

	add_settings_section(
		'gmpp_settings_plaid',
		__("Plaid Bank Login: ", 'woocommerce-gateway-green-money'),
		'gmpg_settings_section_callback_plaid',
		'greenpay_payment_gateway'
	);

	add_settings_field(
		'gmpg_plaid_enabled',
		__('Plaid Enabled', 'woocommerce-gateway-green-money'),
		'gmpg_plaid_render',
		'greenpay_payment_gateway',
		'gmpp_settings_plaid'
	);

	add_settings_section(
		'gmpp_settings_advanced',
		__("Advanced Settings:", 'woocommerce-gateway-green-money'),
		'gmpg_settings_section_callback_advanced',
		'greenpay_payment_gateway'
	);

	add_settings_field(
		'gmpg_debug_log',
		__('Debug Log', 'woocommerce-gateway-green-money'),
		'gmpg_debug_log_render',
		'greenpay_payment_gateway',
		'gmpp_settings_advanced'
	);

	add_settings_field(
		'gmpg_api_endpoint',
		__('API Mode', 'woocommerce-gateway-green-money'),
		'gmpg_api_endpoint_render',
		'greenpay_payment_gateway',
		'gmpp_settings_advanced'
	);

	add_settings_field(
		'gmpg_site_url',
		__('API URL', 'woocommerce-gateway-green-money'),
		'gmpg_site_url_render',
		'greenpay_payment_gateway',
		'gmpp_settings_advanced'
	);

	add_settings_field(
		'gmpg_override_risky_option',
		__('Verification mode:', 'woocommerce-gateway-green-money'),
		'gmpg_override_risky_option_render',
		'greenpay_payment_gateway',
		'gmpp_settings_advanced'
	);
}

/**
 * Settings:
 * 
 * Render the Plaid checkbox
 */
function gmpg_plaid_render()
{
	require_once('class-wc-gateway.php');
	$gateway = new WC_GreenPay_Gateway();

	$options = get_option('gmpg_settings');

	$value = $gateway->can_widget();
	$options['gmpg_plaid_enabled'] = $value;

	update_option('gmpg_settings', $options);
?>
	<input type='checkbox' name='gmpg_settings[gmpg_plaid_enabled]' disabled="disabled" <?php checked($value); ?> />
	<?php
}

/**
 * Settings:
 *
 * Render the API select field in GreenPay™ settings page
 */
function gmpg_api_endpoint_render()
{
	$options = get_option('gmpg_settings');

	if (isset($options['gmpg_api_endpoint'])) { //Display current choice first in selector
		if ($options['gmpg_api_endpoint'] == 'https://greenbyphone.com/') {
	?>
			<select name='gmpg_settings[gmpg_api_endpoint]'>
				<option value='https://greenbyphone.com/' <?php selected($options['gmpg_api_endpoint'], "https://greenbyphone.com/"); ?>>Live</option>
				<option value='https://cpsandbox.com/' <?php selected($options['gmpg_api_endpoint'], "https://cpsandbox.com/"); ?>>Test</option>
			</select>
			<p>Do not modify this unless asked to by Green IT Support staff directly. Even when using Test Credentials generated through your Green Portal, this should be set to Live mode!</p>
		<?php
		} else {
		?>
			<select name='gmpg_settings[gmpg_api_endpoint]'>
				<option value='https://cpsandbox.com/' <?php selected($options['gmpg_api_endpoint'], "https://cpsandbox.com/"); ?>>Test</option>
				<option value='https://greenbyphone.com/' <?php selected($options['gmpg_api_endpoint'], "https://greenbyphone.com/"); ?>>Live</option>
			</select>
			<p>Do not modify this unless asked to by Green IT Support staff directly. Even when using Test Credentials generated through your Green Portal, this should be set to Live mode!</p>
		<?php
		}
	} else {
		?>
		<select name='gmpg_settings[gmpg_api_endpoint]'>
			<option value='https://greenbyphone.com/' selected="selected">Live</option>
			<option value='https://cpsandbox.com/'>Test</option>
		</select>
		<p>Do not modify this unless asked to by Green IT Support staff directly. Even when using Test Credentials generated through your Green Portal, this should be set to Live mode!</p>
	<?php
	}
}

/**
 * Settings:
 *
 * Render the Client ID field in GreenPay™ settings page
 */
function gmpg_client_id_render()
{
	$options = get_option('gmpg_settings');
	$value = isset($options['gmpg_client_id']) ? $options['gmpg_client_id'] : "";
	echo "<input type='text' name='gmpg_settings[gmpg_client_id]' value='$value' required='required'/>";
}

/**
 * Settings:
 *
 * Render the API password field in GreenPay™ settings page
 */
function gmpg_api_password_render()
{
	$options = get_option('gmpg_settings');
	$value = isset($options['gmpg_api_password']) ? $options['gmpg_api_password'] : "";
	echo "<input type='text' name='gmpg_settings[gmpg_api_password]' value='$value' required='required'/>";
}

function gmpg_woo_rest_client_id_render()
{
	$options = get_option("gmpg_settings");
	$value = isset($options['gmpg_woo_rest_client_id']) ? $options['gmpg_woo_rest_client_id'] : "";
	echo "<input type='text' name='gmpg_settings[gmpg_woo_rest_client_id]' value='$value' required='required' /><p>For more information on where to find this value, please see our installation guide <a href='https://www.green.money/woo' title='GreenPay Installation Guide'>here</a>.</p>";
}

function gmpg_woo_rest_client_secret_render()
{
	$options = get_option("gmpg_settings");
	$value = isset($options['gmpg_woo_rest_client_secret']) ? $options['gmpg_woo_rest_client_secret'] : "";
	echo "<input type='text' name='gmpg_settings[gmpg_woo_rest_client_secret]' value='$value' required='required'/><p>For more information on where to find this value, please see our installation guide <a href='https://www.green.money/woo' title='GreenPay Installation Guide'>here</a>.</p>";
}

/**
 * Settings:
 *
 * Render the API URL field in GreenPay™ settings page
 */
function gmpg_site_url_render()
{
	$options = get_option('gmpg_settings');
	$value = isset($options['gmpg_site_url']) ? $options['gmpg_site_url'] : get_site_url();
	echo "<input type='text' name='gmpg_settings[gmpg_site_url]' value='$value' size='60' />";
}

/**
 * Settings:
 *
 * Render the Title field in GreenPay™ settings page
 */
function gmpg_title_render()
{
	$options = get_option('gmpg_settings');
	$value = isset($options['gmpg_title']) ? $options['gmpg_title'] : "";
	echo "<input type='text' name='gmpg_settings[gmpg_title]' value='$value' required='required'/>";
}

/**
 * Settings:
 *
 * Render the Description field in GreenPay™ settings page
 */
function gmpg_gateway_description_render()
{
	$options = get_option('gmpg_settings');
	$value = isset($options['gmpg_gateway_description']) ? trim($options['gmpg_gateway_description']) : "";
	echo "<textarea cols='40' rows='5' name='gmpg_settings[gmpg_gateway_description]'>$value</textarea>";
}

/**
 * Settings:
 *
 * Render the Debug log checkbox in GreenPay™ settings page
 */
function gmpg_debug_log_render()
{
	$options = get_option('gmpg_settings');
	?>
	<input type='checkbox' name='gmpg_settings[gmpg_debug_log]' <?php if (isset($options['gmpg_debug_log'])) {
																	checked($options['gmpg_debug_log'], 1);
																} else {
																	checked(0, 1);
																}; ?> value='1'>

	<?php
}

/**
 * Settings:
 *
 * Render the override risky/bad select field in GreenPay™ settings page
 */
function gmpg_override_risky_option_render()
{
	$options = get_option('gmpg_settings');

	if (isset($options['gmpg_override_risky_option'])) { ?>
		<select name='gmpg_settings[gmpg_override_risky_option]'>
			<option value='legacy' <?php selected($options['gmpg_override_risky_option'], "legacy"); ?>>Legacy</option>
			<option value='permissive' <?php selected($options['gmpg_override_risky_option'], "permissive"); ?>>Permissive</option>
		</select>
	<?php
	} else {
	?>
		<select name='gmpg_settings[gmpg_override_risky_option]'>
			<option value='legacy' selected="selected">Legacy</option>
			<option value='permissive'>Permissive</option>
		</select>
	<?php
	}
	?>
	<br><br>
	<small>
		<strong>Legacy Mode - (Default)</strong> All checks that return a risky code are automatically cancelled and a note added to the order with an explanation in WooCommerce.
		This is how the GreenPay™ plugin has functioned in the past so if you're unsure of which to choose, you should likely not change this setting and
		continue using Legacy mode!
		<br><br>
		<strong>Permissive Mode -</strong> All checks that return a risky code that can be overridden are not cancelled but allowed into your Green.Money
		eCheck Risky/Bad checks. The WooCommerce order will be set to "On-Hold" status and will require you as a merchant to manually override
		the check and update the order status in WooCommerce to fit your needs and risk acceptance.
		<br><br>
		If you have a Risky/Bad order marked "On-Hold", you can click on the individual order from the WooCommerce Orders page and select the "GreenPay™
		Override Risky/Bad" order action from the "Order actions" dropdown, or you can manually override and update the order status in WooCommerce.
		<br><br>
		Log in to your Green.Money Portal and navigate to the Risky/Bad checks page under the "Checks" dropdown. Find the check for your order
		here by searching for the person's name or some other information. On the right hand side for that check, you'll find a link to Override the Risky
		code which will continue its processing in Green.Money. After that's complete, you can manually change the status in WooCommerce to
		"Completed" whenever you'd like.
		<br><br>
		If you have trouble with this process, please contact Customer Support by emailing us at support@green.money or calling our helpline at 404-891-1450
	</small>
<?php
}

/**
 * Settings:
 *
 * Render the Extra message field in GreenPay™ settings page
 */
function gmpg_extra_message_render()
{

	$options = get_option('gmpg_settings');
	$value = isset($options['gmpg_extra_message']) ? trim($options['gmpg_extra_message']) : "";
	echo "<textarea cols='40' rows='5' name='gmpg_settings[gmpg_extra_message]'>$value</textarea>";
}

/**
 * Settings:
 *
 * Insert extra text into options page
 * Left blank for aesthetic reasons
 */
function gmpg_settings_section_callback()
{
	$options = get_option('gmpg_settings');
	echo "<hr/>";
}

function gmpg_settings_section_callback_payment()
{
	echo "<hr/>";
	echo "<p>These settings control how the GreenPay™ Option will display on your store's checkout page.</p>";
}

function gmpg_settings_section_callback_advanced()
{
	echo "<hr/>";
	echo "<p>The settings in this section are intended for advanced users only and may affect the behavior of the plugin!
If you are unsure what you are doing or haven't been given direction by a Green representative to change them, please
ignore these settings</p>";
}

function gmpg_settings_section_callback_plaid()
{
	echo "<hr/>";
	echo "<p>Plaid allows your customer to pay without needing to know
their routing and account numbers. Plaid is available only by request and may be subject to additional terms and fees.
To have this feature turned on for your account, please reach out to your Account Liaison or send us an email to support@green.money asking
about Plaid!</p>";
}

function gmpg_settings_section_callback_tokenizer()
{
	echo "<hr/>";
	echo "<p>The Verde™ widget allows your customer to pay without needing to know
their routing and account numbers. Verde™ is available only by request and may be subject to additional terms and fees.
To have this feature turned on for your account, please reach out to your Account Liaison or send us an email to support@green.money asking
about Verde™!</p>";
}

function gmpg_settings_validate($settings)
{
	require_once("class-wc-gateway.php");

	$oldOptions = get_option('gmpg_settings');
	if (!isset($settings['gmpg_client_id']) || strlen($settings['gmpg_client_id']) === 0) {
		add_settings_error("gmpg_settings", "gmpg_client_id", "GreenPay API Client ID can't be empty. Settings have been reverted.");
		$settings["gmpg_client_id"] = $oldOptions["gmpg_client_id"];
		return $settings;
	}

	if (!isset($settings['gmpg_api_password']) || strlen($settings['gmpg_api_password']) === 0) {
		add_settings_error("gmpg_settings", "gmpg_api_password", "GreenPay API Password can't be empty. Settings have been reverted.");
		$settings["gmpg_api_password"] = $oldOptions["gmpg_api_password"];
		return $settings;
	}

	$gateway = new WC_GreenPay_Gateway();
	$gateway->endpoint = trailingslashit($settings["gmpg_api_endpoint"]);
	$gateway->client_id = $settings["gmpg_client_id"];
	$gateway->client_password = $settings["gmpg_api_password"];
	if (!$gateway->test_authentication()) {
		add_settings_error("gmpg_settings", "gmpg_api_password", "Authentication of the Green API credentials failed for your selected mode. Settings have been reverted. Error returned: " . $gateway->green_money_getLastError());
		$settings["gmpg_api_password"] = $oldOptions["gmpg_api_password"];
		$settings["gmpg_client_id"] = $oldOptions["gmpg_client_id"];
		return $settings;
	}

	if (!isset($settings['gmpg_woo_rest_client_id']) || strlen($settings['gmpg_woo_rest_client_id']) === 0) {
		add_settings_error("gmpg_settings", "gmpg_woo_rest_client_id", "Use of the tokenization widget requires WooCommerce REST API access. Your Rest Client ID is empty. Settings have been reverted.");
		$settings["gmpg_woo_rest_client_id"] = $oldOptions["gmpg_woo_rest_client_id"];
		return $settings;
	}

	if (!isset($settings['gmpg_woo_rest_client_secret']) || strlen($settings['gmpg_woo_rest_client_secret']) === 0) {
		add_settings_error("gmpg_settings", "gmpg_woo_rest_client_secret", "Use of the tokenization widget requires WooCommerce REST API access. Your Rest Client Secret is empty. Settings have been reverted.");
		$settings["gmpg_woo_rest_client_secret"] = $oldOptions["gmpg_woo_rest_client_secret"];
		return $settings;
	}

	$gateway->rest_client_id = $settings['gmpg_woo_rest_client_id'];
	$gateway->rest_client_secret = $settings['gmpg_woo_rest_client_secret'];
	if (!$gateway->register_store($settings["gmpg_site_url"])) {
		add_settings_error("gmpg_settings", "gmpg_woo_rest_failed", "Green was unable to contact your store's REST API using the given REST Client ID and Secret. Settings have been reverted. Error returned: " . $gateway->green_money_getLastError());
		$settings["gmpg_woo_rest_client_id"] = $oldOptions["gmpg_woo_rest_client_id"];
		$settings["gmpg_woo_rest_client_secret"] = $oldOptions["gmpg_woo_rest_client_secret"];
		$settings["gmpg_site_url"] = $oldOptions["gmpg_site_url"];
		return $settings;
	}

	return $settings;
}

/**
 * Settings:
 *
 * Main function to inject settings into wordpress admin menu
 */
function gmpg_options_page()
{
?>
	<style type="text/css">
		.gmpg_validation input:invalid {
			border-color: #900;
			background-color: #FDD;
		}
	</style>
	<form action='options.php' method='post'>
		<h1>GreenPay™ by Green.Money</h1>
		<?php
		settings_errors();
		settings_fields('greenpay_payment_gateway');
		do_settings_sections('greenpay_payment_gateway');
		submit_button('Save Changes', 'primary', 'gmpg_submit');
		?>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				$("#gmpg_submit").click(function() {
					var required = $(".gmpg_required").find("input, textarea, select");
					for (var i = 0; i < required.length; i++) {
						var input = $(required[i]);
						if (!gmpg_checkvalid(input, true)) {
							return false;
						}
					}
				});

				$(".gmpg_validation input, .gmpg_validation textarea").blur(function() {
					var input = $(this);
					gmpg_checkvalid(input, false);
				});
			});

			function gmpg_checkvalid(input, scrollTo) {
				if (scrollTo !== true) scrollTo = false;

				if (jQuery(input).val().trim().length === 0) {
					jQuery(input)[0].setCustomValidity("Required");
					if (scrollTo) {
						jQuery(input)[0].scrollIntoView({
							behavior: "smooth",
							block: "center"
						});
					}
					return false;
				} else {
					jQuery(input)[0].setCustomValidity("");
					return true;
				}
			}
		</script>
	</form>
<?php
}
add_action('admin_menu', 'gmpg_add_admin_menu');
add_action('admin_init', 'gmpg_settings_init');

?>