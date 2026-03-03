<?php

use Automattic\WooCommerce\Blocks\Utils\is_cart_or_checkout;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (! class_exists('WC_GreenPay_Gateway')) {

	class WC_GreenPay_Gateway extends WC_Payment_Gateway
	{
		/**
		 * Payment gateway instructions.
		 * @var string
		 *
		 */
		protected $instructions;

		/**
		 * Whether the gateway is visible for non-admin users.
		 * @var boolean
		 *
		 */
		protected $hide_for_non_admin_users;

		/**
		 * Unique id for the gateway.
		 * @var string
		 *
		 */
		public $id = 'greenmoney';

		/** @var bool Whether or not logging is enabled */
		public static $log_enabled = false;

		/** @var WC_Logger Logger instance */
		public static $log = false;
		public static $deleted;
		public static $processed;
		public static $rejected;

		/**
		 * @var string The last error to have occurred on the gateway
		 */
		public $error = "";
		/**
		 * @var array An array of options set by the gateway settings page recalled by get_option()
		 */
		public $options = array();
		/**
		 * @var string The current API endpoint URL to be called which will be appended by the method.
		 */
		public $endpoint = "https://greenbyphone.com/";
		/**
		 * @var string The current API endpoint to be called as far as eCheck, eCart, etc.
		 */
		public $method = "WooCommerce.asmx";
		/**
		 * @var string The merchant's API Client ID
		 */
		public $client_id = false;
		/**
		 * @var string The merchant's API Password
		 */
		public $client_password = false;
		/**
		 * @var string The woocommerce store REST API client ID
		 */
		public $rest_client_id = false;
		/**
		 * @var string The woocommerce store REST API client secret
		 */
		public $rest_client_secret = false;
		/**
		 * @var bool Whether or not the store allows the widget to be displayed on the front end
		 */
		public $allow_widget = false;
		/**
		 * @var string A human readable description of the payment method displayed on the checkout page
		 */
		public $description = "";
		/**
		 * @var string any extra text to add to the description of the payment method on the checkout page
		 */
		public $extra = "";
		/**
		 * @var string "permissive" or "legacy"
		 */
		public $verification_mode = "legacy";
		/**
		 * @var bool True if the plugin should be logging requests more verbosely to a log file found in wp-content/uploads/wc-logs/ by default
		 */
		public $debug = false;
		/**
		 * @var string Will either be set to the user input setting API URL or will be get_site_url()
		 */
		public $useStoreURL = "";
		/**
		 * @var string Denotes which payment method to take during checkout
		 */
		public $payment_method = "";

		/**
		 * Cloning is forbidden
		 *
		 * @since 1.0
		 */
		public function __clone()
		{
			//do nothing
		}
		/**
		 * Unserializing instances of this class is forbidden
		 *
		 * @since 1.0
		 */
		public function __wakeup()
		{
			//do nothing
		}

		//See parent class WC_Payment_Gateway for (id, has_fields, method_title, method_description, title, supports)
		public function __construct()
		{
			$this->error = '';
			$this->id					 = 'greenmoney';
			$this->has_fields			 = true;
			$this->method_title			 = __('GreenPay™', 'woocommerce-gateway-green-money');
			$new_settings_page = get_admin_url(null, 'admin.php?page=greenpay_payment_gateway');
			$this->method_description	 = __("<a href=" . $new_settings_page . " target='_blank'>GreenPay™ Settings</a>", 'woocommerce-gateway-green-money');
			$this->supports				 = array(
				'products',
				'refunds',
				'tokenization'
			);

		//Get options we need and make them usable
		$this->options = get_option('gmpg_settings', array());
		if (!is_array($this->options)) {
			$this->options = array();
		}
		$this->endpoint = (isset($this->options['gmpg_api_endpoint'])) ? trailingslashit($this->options['gmpg_api_endpoint']) : "https://greenbyphone.com/";
		$this->method = "eCheck.asmx";
		$this->client_id = (isset($this->options['gmpg_client_id'])) ? $this->options['gmpg_client_id'] : false;
		$this->client_password = (isset($this->options['gmpg_api_password'])) ? $this->options['gmpg_api_password'] : false;

		$this->rest_client_id = (isset($this->options['gmpg_woo_rest_client_id'])) ? $this->options['gmpg_woo_rest_client_id'] : false;
		$this->rest_client_secret = (isset($this->options['gmpg_woo_rest_client_secret'])) ? $this->options['gmpg_woo_rest_client_secret'] : false;
		$this->allow_widget = (isset($this->options['gmpg_tokenization_use'])) ? ($this->options["gmpg_tokenization_use"] == 1) : false;

		$this->extra = (isset($this->options['gmpg_extra_message'])) ? $this->options['gmpg_extra_message'] : "";
		$this->description = (isset($this->options['gmpg_gateway_description'])) ? $this->options['gmpg_gateway_description'] : "";
		$this->verification_mode = (isset($this->options['gmpg_override_risky_option'])) ? $this->options['gmpg_override_risky_option'] : "legacy";
		$this->title = (isset($this->options['gmpg_title'])) ? $this->options['gmpg_title'] : "";
		$this->debug = (isset($this->options['gmpg_debug_log'])) ? $this->options['gmpg_debug_log'] : 0;
		
		// Always prefer the configured API URL from settings over get_site_url()
		// This ensures we use the correct URL (e.g., with www) even if WordPress returns a different one
		$settings_url = isset($this->options['gmpg_site_url']) ? trim($this->options['gmpg_site_url']) : '';
		$this->useStoreURL = !empty($settings_url) ? trailingslashit($settings_url) : get_site_url();
		
		// Log which URL is being used for debugging
		$wp_site_url = trailingslashit(get_site_url());
		if (untrailingslashit($this->useStoreURL) !== untrailingslashit($wp_site_url)) {
			$this->log(sprintf(
				__('GreenPay: Using custom API URL from settings: %s (WordPress site URL: %s)', 'woocommerce-gateway-green-money'),
				$this->useStoreURL,
				$wp_site_url
			));
		}

			//Set debug
			if ($this->debug === '1' || $this->debug === 1 || $this->debug === true) {
				$this->debug = true;
			} else {
				$this->debug = false;
			}

			self::$log_enabled = $this->debug;

			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
			add_action('admin_notices', array($this, 'do_ssl_check'), 999);

			// We may need to add in the custom JS for the widget. $this->payment_scripts will run on wp_enqueue_scripts to determine if that's the case and potentially inject the code

			//add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
			add_action('woocommerce_cancelled_order', 'order_cancelled_so_cancelcheck', 10, 1);
			add_action('woocommerce_rest_checkout_process_payment_with_context', [$this, 'rest_checkout'], 10, 3);


			// Code to add subsription options in Edit product page

			add_action('woocommerce_product_options_general_product_data', [$this, 'add_subscription_fields']);
			add_action('woocommerce_process_product_meta', [$this, 'save_subscription_fields']);

			//add_action('wp_ajax_save_subscription_period', [$this,'handle_subscription_period_ajax']);
			//add_action('wp_ajax_nopriv_save_subscription_period', [$this,'handle_subscription_period_ajax']);
			add_filter('woocommerce_account_menu_items', [$this, 'add_account_menu']);
			add_filter('woocommerce_thankyou', [$this, 'order_success_thankyou_page'], 20);
			add_filter('post_class', [$this, 'add_subscription_product_class']);
			add_filter('woocommerce_cart_item_name', [$this, 'add_subscription_flag_to_cart_item_name'], 10, 3);
			add_filter('woocommerce_get_item_data', [$this, 'show_subscription_details_in_checkout'], 10, 2);
			add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
			add_action('admin_menu', [$this, 'register_admin_subscription_menu']);
			add_action('admin_init', [$this, 'handle_admin_subscription_actions']);
			//add_action('pre_get_posts', [$this, 'filter_admin_orders_list']);

			// Show subscription + one-time price info on shop and archive pages
			add_action('woocommerce_after_shop_loop_item_title', [$this, 'show_product_summary'], 11);
			add_action('woocommerce_after_cart_item_name', [$this, 'gmpg_show_subscription_options_in_cart'], 10, 2);
			add_filter('woocommerce_before_calculate_totals', [$this, 'custom_adjust_cart_item_price'], 20, 1);
			add_filter('woocommerce_add_cart_item_data', [$this, 'save_subscription_period_to_cart'], 10, 2);
			add_filter('woocommerce_get_cart_item_from_session', [$this, 'get_subscription_period_from_session'], 10, 2);
			//add_filter('woocommerce_my_account_my_orders_query', [$this, 'hide_split_placeholder_orders']);
			add_action('woocommerce_checkout_create_order_line_item', [$this, 'checkout_create_order_line_item'], 10, 4);
			add_action('wp_ajax_save_subscription_period', [$this, 'save_subscription_period_ajax']);
			add_action('wp_ajax_nopriv_save_subscription_period', [$this, 'save_subscription_period_ajax']);
			add_action('wp_ajax_update_subscription_price', [$this, 'greenpay_update_subscription_price']);
			add_action('wp_ajax_nopriv_update_subscription_price', [$this, 'greenpay_update_subscription_price']);

			// Clear custom prices on session load
			add_action('woocommerce_cart_loaded_from_session', [$this, 'clear_subscription_session_prices']);
			add_action('woocommerce_order_status_completed', [$this, 'mark_subscription_items_as_completed']);
			add_filter('woocommerce_account_menu_items', [$this, 'add_frontend_my_subscription']);
			add_filter('woocommerce_cart_shipping_method_full_label', [$this, 'add_cart_shipping_method_full_label'], 10, 2);
			add_action('init', function () {
				add_rewrite_endpoint('my-subscriptions', EP_ROOT | EP_PAGES);
			});
			add_action('wp_footer', [$this, 'hide_shop_prices_js']);
			add_action('woocommerce_account_my-subscriptions_endpoint', [$this, 'render_user_subscriptions_page']);
			add_action('template_redirect', [$this, 'handle_frontend_cancel_request']);

			add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_subscription_validation']);
		} //END __construct()

		/**
		 * Helper to group subscription and normal items from an order
		 * @param WC_Order $order
		 * @return array [ 'subscription_items' => [...], 'normal_items' => [...] ]
		 */
		private function group_order_items_by_subscription($order)
		{
			$subscription_items = [];
			$normal_items = [];
			foreach ($order->get_items() as $item_id => $item) {
				$product = $item->get_product();
				if (!$product) continue;
				$subscription_type = $product->get_meta('_is_subscription', true);

				if ($subscription_type === 'yes') {
					$payment_every = $product->get_meta('_subscription_repeat_count', true);
					$period = $product->get_meta('_subscription_interval', true);

					if ($period === 'yearly') {
						$payment_every = $payment_every * 12;
					}

					$subscription_items[] = [
						'item'    => $item,
						'product' => $product,
						'period'  => $period,
						'repeat'  => $payment_every,
						'interval_type' => $this->map_interval_type($period)
					];
				} else {
					$normal_items[] = $item;
				}
			}
			return [
				'subscription_items' => $subscription_items,
				'normal_items' => $normal_items
			];
		}

		/**
		 * Map interval string to code
		 * @param string $period
		 * @return string
		 */
		private function map_interval_type($period)
		{
			switch (strtolower($period)) {
				case 'monthly':
				case 'yearly':
					return 'M';
				case 'weekly':
					return 'W';
				case 'daily':
					return 'D';
				case 'week_day':
					return 'F';
				case 'bank_day':
					return 'B';
				default:
					return '';
			}
		}

		/**
		 * Map interval string to formatted string for display
		 * @param string $period
		 * @return string
		 */
		private function map_interval_name($period)
		{
			switch (strtolower($period)) {
				case 'monthly':
					return 'Month';
				case 'yearly':
					return 'Year';
				case 'weekly':
					return 'Week';
				case 'daily':
					return 'Day';
				case 'week_day':
					return 'Week day';
				case 'bank_day':
					return 'Bank day';
				default:
					return '';
			}
		}

		public function hide_shop_prices_js()
		{
			if (is_shop() || is_product_category() || is_product_tag()) {
				echo '<script>
					jQuery(document).ready(function($) {
						//$(".woocommerce-Price-amount").hide();
						$(".wp-block-woocommerce-product-price").hide();
					});
				</script>';
			}
		}

		public function order_success_thankyou_page($order_id)
		{
			$order = wc_get_order($order_id);
			if (!$order) return;

			$sub_orders = $order->get_meta('_split_sub_orders', true);
			if (!$sub_orders) {
				// If we're on a sub-order, find placeholder & its subs
				$parent_orders = wc_get_order([
					'post_type'  => 'shop_order',
					'meta_key'   => '_split_sub_orders',
					'meta_value' => $order_id,
					'fields'     => 'ids'
				]);

				if (!empty($parent_orders)) {
					$sub_orders = $parent_orders[0]->get_meta('_split_sub_orders', true);
				}
			}

			if (!empty($sub_orders) && is_array($sub_orders)) {
				echo '<section class="woocommerce-order-details">';
				echo '<h2>' . __('Additional Orders Created', 'woocommerce') . '</h2>';
				echo '<ul class="order_list">';
				foreach ($sub_orders as $sub_id) {
					$sub_order = wc_get_order($sub_id);
					if ($sub_order && $sub_order->has_status('processing')) {
						echo '<li>Order #<a href="' . esc_url($sub_order->get_view_order_url()) . '">'
							. $sub_id . '</a> – ' . wc_price($sub_order->get_total()) . '</li>';
					}
				}
				echo '</ul>';
				echo '</section>';
			}
		}

		private function rest_success_response($result, $redirect_url)
		{
			$result->set_status('success');
			$result->set_redirect_url($redirect_url);
			return $result;
		}

		/**
		 * Initialise Gateway Settings Form Fields.
		 */
		public function init_form_fields()
		{
			$this->form_fields = array(
				'enabled' => array(
					'title'   => __('Enable/Disable', 'woocommerce-greenpay-gateway'),
					'type'    => 'checkbox',
					'label'   => __('Enable GreenPay', 'woocommerce-greenpay-gateway'),
					'default' => 'yes',
				),
				'hide_for_non_admin_users' => array(
					'type'    => 'checkbox',
					'label'   => __('Hide at checkout for non-admin users', 'woocommerce-greenpay-gateway'),
					'default' => 'no',
				),
				'title' => array(
					'title'       => __('Title', 'woocommerce-greenpay-gateway'),
					'type'        => 'text',
					'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-greenpay-gateway'),
					'default'     => __('Dummy Payment', 'Dummy payment method', 'woocommerce-greenpay-gateway'),
					'desc_tip'    => true,
				),
				'description' => array(
					'title'       => __('Description', 'woocommerce-greenpay-gateway'),
					'type'        => 'textarea',
					'description' => __('Payment method description that the customer will see on your checkout.', 'woocommerce-greenpay-gateway'),
					'default'     => __('The goods are yours. No money needed.', 'woocommerce-greenpay-gateway'),
					'desc_tip'    => true,
				),
				'result' => array(
					'title'    => __('Payment result', 'woocommerce-greenpay-gateway'),
					'desc'     => __('Determine if order payments are successful when using this gateway.', 'woocommerce-greenpay-gateway'),
					'id'       => 'woo_dummy_payment_result',
					'type'     => 'select',
					'options'  => array(
						'success'  => __('Success', 'woocommerce-greenpay-gateway'),
						'failure'  => __('Failure', 'woocommerce-greenpay-gateway'),
					),
					'default' => 'success',
					'desc_tip' => true,
				)
			);
		}

		function __toString()
		{
			$str  = "Gateway Type: POST\n";
			$str .= "Endpoint: " . $this->endpoint . "\n";
			$str .= "Client ID: " . $this->client_id . "\n";
			$str .= "ApiPassword: " . $this->client_password . "\n";
			return $str;
		}

		public function add_subscription_fields()
		{
			woocommerce_wp_checkbox([
				'id' => '_is_subscription',
				'label' => 'Enable Subscription?',
			]);

			echo '<div class="options_group show_if_simple greenpay-subscription-options">';

			// Custom wrapper to ensure proper error placement

			woocommerce_wp_select([
				'id' => '_subscription_interval',
				'label' => 'Billing Interval',
				'options' => [
					'' => 'Select',
					'daily' => 'Daily',
					'week_day' => 'Week Day',
					'bank_day' => 'Bank Day',
					'weekly' => 'Weekly',
					'monthly' => 'Monthly',
					'yearly' => 'Yearly',
				],

				//'description' => 'How often the customer is billed.',
			]);
			echo '<span class="description" style="padding-left: 155px; display: block;">How often the customer is billed.</span>';

			woocommerce_wp_text_input([
				'id' => '_subscription_repeat_count',
				'label' => 'Repeat Count',
				'type' => 'number',
				'custom_attributes' => ['step' => '1', 'min'  => '-1', 'max'  => '15', 'id' => 'gmpg-subscription-repeat-count'],
				'description' => 'Number of Payments. (Enter -1 for a subscription that will continue until stopped manually)',
			]);


			echo '</div>';
		}

		public function save_subscription_fields($product_id)
		{
			// Check if the subscription checkbox is present in the request
			if (!isset($_POST['_is_subscription'])) {
				return;
			}

			$product = wc_get_product($product_id);

			// Save subscription checkbox
			$is_subscription = $_POST['_is_subscription'] === 'yes' ? 'yes' : 'no';
			$product->update_meta_data('_is_subscription', $is_subscription);
			$product->update_meta_data('_subscription_type', $is_subscription ? 'recurring' : 'one-time'); // Set default subscription type

			// Save subscription interval (e.g., monthly, yearly)
			if (isset($_POST['_subscription_interval'])) {
				$product->update_meta_data('_subscription_interval', sanitize_text_field($_POST['_subscription_interval']));
			}

			// Save repeat count - preserve -1 and allow other valid integers
			if (isset($_POST['_subscription_repeat_count']) && $_POST['_subscription_repeat_count'] !== '') {
				$repeat_count = intval($_POST['_subscription_repeat_count']); // keeps negative values
				$product->update_meta_data('_subscription_repeat_count', $repeat_count);
			}

			$product->save();
		}

		public function handle_subscription_period_ajax()
		{
			$period = sanitize_text_field($_POST['period']);
			$product_name = sanitize_text_field($_POST['product_name']);

			foreach (WC()->cart->get_cart() as $key => $item) {
				if ($item['data']->get_name() === $product_name) {
					WC()->cart->cart_contents[$key]['subscription_period'] = $period;
				}
			}

			WC()->cart->set_session();
			wp_send_json_success(['status' => 'saved']);
		}

		public function add_cart_shipping_method_full_label($label, $method)
		{
			if ($method->cost === '0' || $method->cost === 0 || empty($method->cost)) {
				// Append "FREE" only if not already present
				if (strpos($label, 'FREE') === false) {
					return $label . ' - FREE';
				}
			}
			return $label;
		}

		public function enqueue_admin_subscription_validation($hook)
		{
			if ($hook !== 'post.php' && $hook !== 'post-new.php') return;

			global $post;
			if ($post && $post->post_type !== 'product') return;

			wp_enqueue_script(
				'subscription-admin-validation',
				plugins_url('assets/js/subscription-admin-validation.js', dirname(__FILE__)),
				['jquery'],
				time(),
				true
			);
		}

		public function add_account_menu($items)
		{
			$items['my-subscriptions'] = 'My Subscriptions';
			return $items;
		}

		public function register_admin_subscription_menu()
		{
			add_menu_page(
				'Purchased Subscriptions',
				'Subscriptions',
				'manage_woocommerce',
				'gmpg-purchased-subscriptions',
				[$this, 'render_purchased_subscriptions_page'],
				'dashicons-cart',
				56
			);
		}

		public function handle_admin_subscription_actions()
		{
			if (!is_admin() || !current_user_can('manage_woocommerce')) return;

			if (isset($_GET['gmpg_cancel_subscription'], $_GET['order_id'], $_GET['product_id'])) {
				$order_id   = absint($_GET['order_id']);
				$product_id = absint($_GET['product_id']);

				$this->cancel_subscription($order_id, $product_id);

				wp_redirect(remove_query_arg(['gmpg_cancel_subscription', 'order_id', 'product_id']));
				exit;
			}

			if (isset($_GET['gmpg_delete_subscription'], $_GET['order_id'], $_GET['product_id'])) {
				$order_id   = absint($_GET['order_id']);
				$product_id = absint($_GET['product_id']);

				$this->delete_subscription_meta($order_id, $product_id);

				wp_redirect(remove_query_arg(['gmpg_delete_subscription', 'order_id', 'product_id']));
				exit;
			}
		}

		public function cancel_subscription($order_id, $product_id)
		{
			$order = wc_get_order($order_id);
			if (!$order) return;

			foreach ($order->get_items() as $item_id => $item) {
				$product = $item->get_product();
				if ($product && $product->get_id() === $product_id) {
					// Mark locally
					$item->update_meta_data('Subscription Status', 'Cancelled');
					$item->save();

					// Call Green.Money API (CancelCheck)
					$action = "CancelCheck";
					$data = array(
						"Client_ID"   => $this->client_id,
						"ApiPassword" => $this->client_password,
						"Store"       => $this->useStoreURL,
						"OrderID"     => $order->get_meta('_greenmoney_check_id', true) ?: $order_id
					);

					$cancelResponse = $this->callGreenAPI($action, $data, "WooCommerce.asmx");

					if ($cancelResponse && isset($cancelResponse->Result->Result) && $cancelResponse->Result->Result == "0") {
						$order->add_order_note(__('Subscription cancelled manually', 'woocommerce-gateway-green-money'));

						// Ensure WooCommerce order status also changes
						if ($order->get_status() !== 'cancelled') {
							$order->update_status('cancelled');
						}
					} else {
						$order->add_order_note(__('CancelCheck API call failed when cancelling subscription.', 'woocommerce-gateway-green-money'));
					}
				}
			}
		}


		public function delete_subscription_meta($order_id, $product_name)
		{
			$order = wc_get_order($order_id);
			if (!$order) return;

			foreach ($order->get_items() as $item_id => $item) {
				$product = $item->get_product();
				if ($product && $product->get_name() === $product_name) {
					$product->delete_meta_data('Subscription Type');
					$product->delete_meta_data('Subscription Period');
					$product->delete_meta_data('Process Every');
					$product->update_meta_data('Subscription Status', 'Deleted');
					$product->save();
				}
			}
		}


		public function mark_subscription_items_as_completed($order_id)
		{
			$order = wc_get_order($order_id);
			if (!$order) return;

			foreach ($order->get_items() as $item_id => $item) {
				$product = $item->get_product();

				if (!$product instanceof WC_Product) continue;

				$product_id = $product->get_id();
				$is_subscription = $product->get_meta('_is_subscription', true);

				// Also check parent if variation
				if ($is_subscription !== 'yes' && $product->is_type('variation')) {
					$parent_product = wc_get_product($product->get_parent_id());
					$is_subscription = $parent_product->get_meta('_is_subscription', true);
				}

				if ($is_subscription === 'yes') {
					// Mark the subscription item as completed
					$item->update_meta_data('Subscription Status', 'Completed');
					$item->save();

					// Create subscription record
					$period = $product->get_meta('_subscription_period', true); // e.g. 'month', 'week'
					$user_id = $order->get_user_id();
					$start_date = current_time('mysql');
					$next_payment_date = date('Y-m-d H:i:s', strtotime("+1 $period"));

					// Save subscription details in order meta (or in custom DB table if you're using one)
					$order->update_meta_data('_greenpay_subscription', [
						'user_id'           => $user_id,
						'product_id'        => $product_id,
						'interval'          => $period,
						'start_date'        => $start_date,
						'next_payment_date' => $next_payment_date,
						'status'            => 'active',
					]);
					$order->save();
				}
			}
		}


		public function add_frontend_my_subscription($items)
		{
			$items['my-subscriptions'] = 'My Subscriptions';
			return $items;
		}

		public function render_user_subscriptions_page()
		{
			$user_id = get_current_user_id();
			$orders = wc_get_orders([
				'customer_id' => $user_id,
				'limit'       => -1,
				'status'      => ['completed', 'processing', 'on-hold', 'cancelled'],
			]);

			echo '<h2>Your Subscriptions</h2>';

			if (empty($orders)) {
				echo '<p>You have no subscriptions.</p>';
				return;
			}

			$has_subscriptions = false;

			foreach ($orders as $order) {
				foreach ($order->get_items() as $item_id => $item) {
					$product = $item->get_product();
					if (!$product) continue;

					$product_id = $product->get_id();
					$is_subscription = $product->get_meta('_is_subscription', true);
					if ($is_subscription !== 'yes') continue;

					// Found at least one subscription → start table once
					if (!$has_subscriptions) {
						echo '<table class="shop_table shop_table_responsive my_account_orders">';
						echo '<thead><tr><th>Product</th><th>Duration</th><th>Status</th><th>Start Date</th><th>Action</th></tr></thead><tbody>';
						$has_subscriptions = true;
					}

					$period = $item->get_meta('Subscription Period');
					$every  = $item->get_meta('Process Every');
					$status = $item->get_meta('Subscription Status') ?: ucfirst($order->get_status());

					if ($every < 1) {
						$duration = "Until Stopped";
					} else {
						$every *= 12;
						$duration = $every . ' ' . $period . ((int)$every > 1 ? 's' : '');
					}

					echo '<tr>';
					echo '<td>' . esc_html($product->get_name()) . '</td>';
					echo '<td>' . esc_html($duration) . '</td>';
					echo '<td>' . esc_html($status) . '</td>';
					echo '<td>' . esc_html($order->get_date_created()->date('Y-m-d')) . '</td>';

					if (strtolower($status) !== 'cancelled') {
						$cancel_url = add_query_arg([
							'cancel_subscription' => 1,
							'order_id'   => $order->get_id(),
							'product_id' => $product_id
						], wc_get_account_endpoint_url('my-subscriptions'));

						echo '<td><a href="' . esc_url($cancel_url) . '" class="button cancel">Cancel</a></td>';
					} else {
						echo '<td><em>Cancelled</em></td>';
					}

					echo '</tr>';
				}
			}

			if ($has_subscriptions) {
				echo '</tbody></table>';
			} else {
				echo '<p>You have no subscriptions.</p>';
			}
		}


		public function handle_frontend_cancel_request()
		{
			if (! is_user_logged_in()) {
				return;
			}

			if (isset($_GET['cancel_subscription']) && isset($_GET['order_id']) && isset($_GET['product_id'])) {
				$order_id     = absint($_GET['order_id']);
				$product_id = absint($_GET['product_id']);
				$user_id      = get_current_user_id();

				$this->cancel_subscription($order_id, $product_id);

				wc_add_notice(__('Your subscription has been cancelled.', 'woocommerce-gateway-green-money'), 'success');
				wp_safe_redirect(wc_get_account_endpoint_url('my-subscriptions'));
				exit;
			}
		}


		public function render_purchased_subscriptions_page()
		{
			echo '<div class="wrap">';
			echo '<h1>Purchased Subscription Products</h1>';

			$orders = wc_get_orders([
				'limit'  => -1,
				'status' => ['pending', 'processing', 'completed', 'on-hold', 'cancelled'],
			]);

			$rows = [];

			foreach ($orders as $order) {
				$user_id = $order->get_user_id();

				// --- Detect whether this is a parent mixed order ---
				$has_subscription = false;
				$has_non_subscription = false;

				foreach ($order->get_items() as $item) {
					$product = $item->get_product();
					if (!$product) continue;

					$is_subscription = $product->get_meta('_is_subscription', true);
					if ($is_subscription === 'yes') {
						$has_subscription = true;
					} else {
						$has_non_subscription = true;
					}
				}

				// Skip parent orders that contain both subscription + non-subscription products
				if ($has_subscription && $has_non_subscription) {
					continue;
				}

				// --- Now only process pure subscription orders ---
				foreach ($order->get_items() as $item) {
					$product = $item->get_product();
					if (!$product || !$product instanceof WC_Product) continue;

					$product_id = $product->get_id();
					$is_subscription = $product->get_meta('_is_subscription', true);

					// For variation products, check parent
					if ($is_subscription !== 'yes' && $product->is_type('variation')) {
						$parent_product = wc_get_product($product->get_parent_id());
						if (!$parent_product) continue;
						$is_subscription = $parent_product->get_meta('_is_subscription', true);
						$product_id = $parent_product->get_id();
						$product = $parent_product;
					} else {
						$type   = "recurring";
						$period = $product->get_meta('_subscription_interval', true);
						$every  = $product->get_meta('_subscription_repeat_count', true);
					}

					if ($is_subscription !== 'yes') continue;
					if (empty($period) || empty($every)) continue;

					$duration_map = array(
						'Daily'   => 'day',
						'Week Day' => 'week day',
						'Bank Day' => 'bank day',
						'Weekly'  => 'week',
						'Monthly' => 'month',
						'Yearly'  => 'year'
					);

					$duration_value = ucfirst($period);
					$converted_duration = isset($duration_map[$duration_value]) ? $duration_map[$duration_value] : $duration_value;
					if ($converted_duration === 'year') {
						$every *= 12;
						$converted_duration = 'month';
					}

					// Translate period + count into string
					$converted_duration = $every . ' ' . $converted_duration;
					if ((int)$every > 1) {
						$converted_duration .= 's'; // pluralize
					} else {
						$converted_duration = 'Until Stopped';
					}

					// Subscription status from item meta, fallback to order status
					$subscription_status = $item->get_meta('Subscription Status');
					if (empty($subscription_status)) {
						$subscription_status = ucfirst($order->get_status());
					}

					$user    = get_userdata($user_id);

					$rows[] = [
						'user_id'      => $user_id,
						'user_name'    => $user ? $user->display_name : 'Guest',
						'product_id'   => $product->get_id(),
						'product_name' => $product->get_name(),
						'duration'     => $converted_duration,
						'period'       => ucfirst($period),
						'status'       => ucfirst($subscription_status),
						'start_date'   => $order->get_date_created()->date('Y-m-d H:i'),
						'order_id'     => $order->get_id()
					];
				}
			}

			if (empty($rows)) {
				echo '<p>No subscription products have been purchased yet.</p>';
				return;
			}

			echo '<table class="widefat striped">';
			echo '<thead><tr><th>User</th><th>User ID</th><th>Product</th><th>Duration</th><th>Period</th><th>Status</th><th>Start Date</th><th>Order</th></tr></thead>';
			echo '<tbody>';

			foreach ($rows as $row) {
				$cancel_url = add_query_arg([
					'gmpg_cancel_subscription' => 1,
					'order_id' => $row['order_id'],
					//'product_name' => urlencode($row['product_name']),
					'product_id' => $row['product_id'],
				]);

				echo '<tr>';
				echo '<td>' . esc_html($row['user_name']) . '</td>';
				echo '<td>' . esc_html($row['user_id']) . '</td>';
				echo '<td>' . esc_html($row['product_name']) . '</td>';
				echo '<td>' . esc_html($row['duration']) . '</td>';
				echo '<td>' . esc_html($row['period']) . '</td>';
				echo '<td>' . esc_html($row['status']) . '</td>';
				echo '<td>' . esc_html($row['start_date']) . '</td>';
				echo '<td>';
				echo '<a href="' . admin_url('post.php?post=' . $row['order_id'] . '&action=edit') . '">#' . $row['order_id'] . '</a><br>';
				if (strtolower($row['status']) !== 'cancelled') {
					echo '<a href="' . esc_url($cancel_url) . '" style="color:red;">Cancel</a>';
				} else {
					echo '<em>Cancelled</em>';
				}
				echo '</td>';
				echo '</tr>';
			}

			echo '</tbody></table>';
			echo '</div>';
		}


		public function show_subscription_details_in_checkout($item_data, $cart_item)
		{
			if (!is_checkout()) {
				return $item_data;
			}

			if (isset($cart_item['subscription_type']) && $cart_item['subscription_type'] === 'recurring') {
				$interval = isset($cart_item['subscription_period']) ? strtolower($cart_item['subscription_period']) : '';
				$repeat   = isset($cart_item['subscription_repeat']) ? $cart_item['subscription_repeat'] : '';
				$price    = wc_get_price_to_display($cart_item['data']);

				$billing_label = ($interval === 'yearly') ? 'month' : rtrim($interval, 'ly'); // yearly => month

				// Use descriptive and unique keys to avoid overwriting
				if ($repeat > 0) {
					$item_data[] = [
						'key'     => '',
						'value'   => '<span class="subscription_price_checkout">Subscription Price: </span>' . sprintf('$%.2f / %s', $price, $billing_label),
						'display' => '',
					];
				}


				//if (!empty($repeat) && strtolower($repeat) !== 'unlimited') {
				if (!empty($repeat) && $repeat > 0) {
					$repeat_count = (int) $repeat;

					if ($interval === 'yearly') {
						$repeat_count = $repeat_count * 12;
					}

					$plural = $repeat_count > 1 ? $billing_label . 's' : $billing_label;
					$repeat_count = $repeat_count === -1 ? 'Until Stopped' : $repeat_count;

					$item_data[] = [
						'key'     => '',
						'value'   => 'Repeat: ' . sprintf('%d %s', $repeat_count, $plural),
						'display' => '',
					];
				}
			}

			return $item_data;
		}

		public function add_subscription_product_class($classes)
		{
			global $product;

			if (is_a($product, 'WC_Product') && $product->get_meta('_is_subscription', true) === 'yes') {
				$classes[] = 'subscription-product';
			}

			return $classes;
		}

		public function add_subscription_flag_to_cart_item_name($name, $cart_item, $cart_item_key)
		{
			$product = $cart_item['data'];
			$is_subscription = $product->get_meta('_is_subscription', true) === 'yes';

			if ($is_subscription) {
				return '<span class="subscription-flag" data-is-subscription="yes">' . $name . '</span>';
			}

			return $name;
		}

		public function enqueue_styles()
		{
			if (is_shop() || is_product_category() || is_product_tag()) {
				$css_file = plugin_dir_path(__FILE__) . '../assets/css/hide-subscription-prices.css';
				wp_enqueue_style(
					'custom-subscription-style',
					plugins_url('assets/css/hide-subscription-prices.css', dirname(__FILE__)),
					array(),
					file_exists($css_file) ? filemtime($css_file) : time()
				);
			}

			if (is_cart()) {
				$js_file = plugin_dir_path(__FILE__) . '../assets/js/frontend/subscription-cart.js';
				$subscription_products = [];
				$subscription_products = $this->get_cart_subscription_products();

				wp_enqueue_script(
					'custom-subscription-cart-js',
					plugins_url('assets/js/frontend/subscription-cart.js', dirname(__FILE__)),
					['wp-blocks', 'wp-element', 'jquery'],
					file_exists($js_file) ? filemtime($js_file) : time(),
					true
				);


				wp_localize_script('custom-subscription-cart-js', 'subscriptionCartData', [
					'subscription_products' => $subscription_products,
				]);
			}
		}

		public function get_cart_subscription_products()
		{
			$subscription_products = [];

			if (WC()->cart && !WC()->cart->is_empty()) {
				foreach (WC()->cart->get_cart() as $cart_item) {
					$product = $cart_item['data'];

					if (!$product instanceof WC_Product) {
						continue;
					}

					$product_id = $product->get_id();

					// Check if subscription is enabled
					if ($product->get_meta('_is_subscription', true) !== 'yes') {
						continue;
					}

					// Get billing details set by admin
					$interval = $product->get_meta('_subscription_interval', true); // weekly/monthly/yearly
					$repeat = intval($product->get_meta('_subscription_repeat_count', true));
					$price = floatval($product->get_meta('_regular_price', true));

					// Determine how to label the interval and duration
					$starts_at = '';
					$duration = '';

					if ($interval === 'yearly') {
						// For yearly, price is monthly breakdown
						$starts_at = "Starts at Monthly: " . wc_price($price);
						$duration = "Duration: " . ceil($repeat / 12) . " year(s)";
					} elseif ($interval === 'monthly') {
						$starts_at = "Starts at Monthly: " . wc_price($price);
						$duration = "Duration: $repeat month(s)";
					} elseif ($interval === 'weekly') {
						$starts_at = "Starts at Weekly: " . wc_price($price);
						$duration = "Duration: $repeat week(s)";
					} elseif ($interval === 'daily') {
						$starts_at = "Starts at Daily: " . wc_price($price);
						$duration = "Duration: $repeat day(s)";
					} elseif ($interval === 'week_day') {
						$starts_at = "Starts at: " . wc_price($price);
						$duration = "Duration: $repeat week day(s) (M - F)";
					} elseif ($interval === 'bank_day') {
						$starts_at = "Starts at: " . wc_price($price);
						$duration = "Duration: $repeat bank day(s)";
					}

					$subscription_products[] = [
						'id' => $product_id,
						'name' => $product->get_name(),
						'price' => $product->get_price(), // fallback
						'selected_type' => $cart_item['subscription_type'] ?? 'one_time', // One Time or Recurring
						'interval' => $interval,
						'repeat' => $repeat,
						'subscription_price' => $price,
						'starts_at' => $starts_at,
						'duration' => $duration,
						'is_subscription' => true,
						'selected_type' => 'recurring'
					];
				}
			}

			return $subscription_products;
		}


		public function show_product_summary()
		{
			global $product;

			if (!$product instanceof WC_Product) return;
			$is_subscription = $product->get_meta('_is_subscription', true);

			$price_display = $product->get_price_html();
			if ('' === $price_display) {
				$regular_price = (float) $product->get_price(); // Fallback for edge cases where price HTML is empty.
				$price_display = wc_price($regular_price);
			}

			$output = '<div class="subscription-period-prices" style="font-size: 0.875rem; color: #000; margin-top: 5px; text-align: center;">';

			if ($is_subscription === 'yes') {
				$interval = $product->get_meta('_subscription_interval', true);
				$repeat = $product->get_meta('_subscription_repeat_count', true);

				if (!empty($interval)) {
					$unit = $this->map_interval_name($interval);
					$output .= "{$price_display} / {$unit}<br>";
					if ($repeat < 1) {
						$output .= "<strong>Duration: Until Stopped</strong><br>";
					} else {
						$output .= "<strong>Duration:</strong> {$repeat} " . ($repeat == 1 ? $unit : "{$unit}s") . "<br>";
					}
				}
			} else {
				$output .= "{$price_display}<br>";
			}
			$output .= '</div>';
			echo wp_kses_post($output);
		}


		public function gmpg_show_subscription_options_in_cart($cart_item, $cart_item_key)
		{
			$product_id = $cart_item['product_id'];
			$product = wc_get_product($product_id);

			if ($product->get_meta('_is_subscription', true) === 'yes') {
				$selected_type = $cart_item['subscription_type'] ?? 'one_time';
				$selected_period = $cart_item['subscription_period'] ?? '';
				$selected_repeat = $cart_item['subscription_repeat'] ?? '1';

?>
				<div class="gmpg-subscription-options" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">
					<p><strong>Purchase Type:</strong></p>
					<label>
						<input type="radio" name="gmpg_subscription_type_<?php echo $cart_item_key; ?>" value="one_time" <?php checked($selected_type, 'one_time'); ?>> One Time
					</label>
					<label style="margin-left:10px;">
						<input type="radio" name="gmpg_subscription_type_<?php echo $cart_item_key; ?>" value="recurring" <?php checked($selected_type, 'recurring'); ?>> Recurring
					</label>

					<div class="gmpg-period-wrapper" style="margin-top:10px; <?php echo $selected_type === 'recurring' ? '' : 'display:none;'; ?>">
						<label>Subscription Period:
							<select name="gmpg_subscription_period_<?php echo $cart_item_key; ?>" class="gmpg-period-select">
								<option value="">Select</option>
								<option value="daily" <?php selected($selected_period, 'daily'); ?>>Daily</option>
								<option value="week_day" <?php selected($selected_period, 'week_day'); ?>>Week Day</option>
								<option value="bank_day" <?php selected($selected_period, 'bank_day'); ?>>Bank Day</option>
								<option value="weekly" <?php selected($selected_period, 'weekly'); ?>>Weekly</option>
								<option value="monthly" <?php selected($selected_period, 'monthly'); ?>>Monthly</option>
								<option value="yearly" <?php selected($selected_period, 'yearly'); ?>>Yearly</option>
							</select>
						</label>

						<label style="margin-left:10px;">
							Repeat Count:
							<select name="gmpg_subscription_repeat_<?php echo $cart_item_key; ?>" class="gmpg-repeat-select">
								<?php for ($i = 1; $i <= 12; $i++): ?>
									<option value="<?php echo $i; ?>" <?php selected($selected_repeat, $i); ?>><?php echo $i; ?></option>
								<?php endfor; ?>
							</select>
							<span class="gmpg-repeat-label"><?php echo ucfirst($selected_period ?: 'period'); ?></span>
						</label>
					</div>
				</div>
<?php
			}
		}

		public function custom_adjust_cart_item_price($cart)
		{
			if (is_admin() && !defined('DOING_AJAX')) return;

			// Prevent double processing
			if (did_action('woocommerce_before_calculate_totals') >= 2) return;

			foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
				if (isset($cart_item['custom_subscription_price'])) {
					$price = floatval($cart_item['custom_subscription_price']);
					if ($price > 0) {
						$cart_item['data']->set_price($price);
					}
				}
			}
			$cart->set_session();
		}

		public function checkout_create_order_line_item($item, $cart_item_key, $values, $order)
		{
			if (isset($values['subscription_meta'])) {
				$meta = $values['subscription_meta'];
				$item->add_meta_data('Subscription Type', $meta['type'], true);
				$item->add_meta_data('Subscription Period', $meta['period'], true);
				$item->add_meta_data('Repeat Count', $meta['every'] === -1 ? 'Until Stopped' : $meta['every'], true);
			}

			if (isset($values['custom_price'])) {
				$item->add_meta_data('Custom Subscription Price', $values['custom_price'], true);
			}
		}


		public function save_subscription_period_to_cart($cart_item_data, $product_id)
		{
			$product = wc_get_product($product_id);

			if ($product->get_meta('_is_subscription', true) !== 'yes') return $cart_item_data;

			// Fetch from $_POST or JS-augmented request (e.g., via AJAX or hidden fields)
			$subscription_type = sanitize_text_field($_POST['subscription_type'] ?? '');
			$interval = $product->get_meta('_subscription_interval', true) ?: sanitize_text_field($_POST['subscription_interval'] ?? '');
			$repeat = $product->get_meta('_subscription_repeat_count', true) ?: sanitize_text_field($_POST['subscription_repeat_count'] ?? '');

			// Add to cart item data
			if ($subscription_type && $interval && $repeat) {
				$cart_item_data['subscription_type'] = $subscription_type;
				$cart_item_data['subscription_interval'] = $interval;
				$cart_item_data['subscription_repeat_count'] = $repeat;
				$cart_item_data['unique_key'] = md5(microtime() . rand()); // Ensures unique cart line
			}

			return $cart_item_data;
		}


		public function get_subscription_period_from_session($cart_item, $values)
		{
			if (isset($values['subscription_type'])) {
				$cart_item['subscription_type'] = $values['subscription_type'];
			}
			if (isset($values['subscription_period'])) {
				$cart_item['subscription_period'] = $values['subscription_period'];
			}
			if (isset($values['subscription_repeat'])) {
				$cart_item['subscription_repeat'] = $values['subscription_repeat'];
			}
			if (isset($values['custom_price'])) {
				$cart_item['custom_subscription_price'] = $values['custom_price'];
			}
			if (isset($values['subscription_meta'])) {
				$cart_item['subscription_meta'] = $values['subscription_meta'];
			}
			return $cart_item;
		}

		public function save_subscription_period_ajax()
		{
			$product_name = sanitize_text_field($_POST['product_name']);
			$period = sanitize_text_field($_POST['period']);

			foreach (WC()->cart->get_cart() as $cart_item_key => $item) {
				$product = $item['data'];
				if ($product->get_name() === $product_name) {
					WC()->cart->cart_contents[$cart_item_key]['subscription_period'] = $period;
					break;
				}
			}

			WC()->cart->set_session();
			wp_send_json(['status' => 'saved', 'period' => $period]);
		}


		public function greenpay_update_subscription_price()
		{
			// Bulk mode
			if (isset($_POST['subscriptions'])) {
				$subscriptions = json_decode(stripslashes($_POST['subscriptions']), true);
				if (json_last_error() !== JSON_ERROR_NONE || !is_array($subscriptions)) {
					wp_send_json_error(['message' => 'Invalid subscriptions data']);
				}

				foreach ($subscriptions as $sub) {

					$product_id            = intval($sub['product_id'] ?? 0);
					$new_price             = floatval($sub['new_price'] ?? 0);
					$subscription_type     = sanitize_text_field($sub['subscription_type'] ?? '');
					$subscription_period   = sanitize_text_field($sub['subscription_period'] ?? '');
					$process_payment_every = intval($sub['process_payment_every'] ?? 1);

					foreach (WC()->cart->get_cart() as $cart_item_key => $item) {
						if ($item['data']->get_id() == $product_id) {
							if ($subscription_type === 'one_time') {
								unset(WC()->cart->cart_contents[$cart_item_key]['subscription_period']);
								unset(WC()->cart->cart_contents[$cart_item_key]['subscription_repeat']);
								unset(WC()->cart->cart_contents[$cart_item_key]['subscription_meta']);
								unset(WC()->cart->cart_contents[$cart_item_key]['custom_subscription_price']);
							}

							WC()->cart->cart_contents[$cart_item_key]['custom_price'] = $new_price;
							WC()->cart->cart_contents[$cart_item_key]['subscription_type'] = $subscription_type;
							WC()->cart->cart_contents[$cart_item_key]['subscription_period'] = $subscription_period;
							WC()->cart->cart_contents[$cart_item_key]['subscription_repeat'] = $process_payment_every;
							WC()->cart->cart_contents[$cart_item_key]['custom_subscription_price'] = $new_price;
							WC()->cart->cart_contents[$cart_item_key]['subscription_meta'] = [
								'type'   => $subscription_type,
								'period' => $subscription_period,
								'every'  => $process_payment_every
							];
						}
					}
				}

				// Log cart before saving to session
				WC()->cart->set_session();

				// Log session cart after saving
				$session_cart = WC()->session->get('cart', []);
				wp_send_json_success(['message' => 'Bulk cart updated']);
			}

			// Single product fallback
			if (!isset($_POST['product_id'], $_POST['new_price'])) {
				wp_send_json_error(['message' => 'Missing data']);
			}

			$product_id            = intval($_POST['product_id']);
			$new_price             = floatval($_POST['new_price']);
			$subscription_type     = sanitize_text_field($_POST['subscription_type'] ?? '');
			$subscription_period   = sanitize_text_field($_POST['subscription_period'] ?? '');
			$process_payment_every = intval($_POST['process_payment_every'] ?? 1);

			foreach (WC()->cart->get_cart() as $cart_item_key => $item) {
				if ($item['data']->get_id() == $product_id) {
					if ($subscription_type === 'one_time') {
						unset(WC()->cart->cart_contents[$cart_item_key]['subscription_period']);
						unset(WC()->cart->cart_contents[$cart_item_key]['subscription_repeat']);
						unset(WC()->cart->cart_contents[$cart_item_key]['subscription_meta']);
						unset(WC()->cart->cart_contents[$cart_item_key]['custom_subscription_price']);
					}

					WC()->cart->cart_contents[$cart_item_key]['custom_price'] = $new_price;
					WC()->cart->cart_contents[$cart_item_key]['subscription_type'] = $subscription_type;
					WC()->cart->cart_contents[$cart_item_key]['subscription_period'] = $subscription_period;
					WC()->cart->cart_contents[$cart_item_key]['subscription_repeat'] = $process_payment_every;
					WC()->cart->cart_contents[$cart_item_key]['custom_subscription_price'] = $new_price;
					WC()->cart->cart_contents[$cart_item_key]['subscription_meta'] = [
						'type'   => $subscription_type,
						'period' => $subscription_period,
						'every'  => $process_payment_every
					];

					WC()->cart->set_session();

					$session_cart = WC()->session->get('cart', []);
					wp_send_json_success(['message' => 'Cart updated']);
				}
			}

			wp_send_json_error(['message' => 'Product not found in cart']);
		}



		public function clear_subscription_session_prices($cart)
		{
			foreach ($cart->get_cart() as $key => &$item) {
				unset($item['custom_subscription_price']);
			}
		}

		/**
		 * Internal helper function returns the entire endpoint URL where endpoint is either greenbyphone.com or cpsandbox.com
		 * and method would be eCart.asmx/eCheck.asmx
		 *
		 * @return string The full unqualified URL an API call is targeted to for this Gateway
		 */
		public function full_endpoint()
		{
			return trailingslashit(trailingslashit($this->endpoint) . $this->method);
		}

		function green_money_toString($html = TRUE)
		{
			if ($html) {
				return nl2br($this->__toString());
			}

			return $this->__toString();
		}

		private function green_money_setLastError($error)
		{
			$this->error = $error;
		}

		public function green_money_getLastError()
		{
			return $this->error;
		}

		/**
		 * Logging method.
		 *
		 * @param string $message
		 */
		public static function log($message)
		{
			if (self::$log_enabled) {
				if (empty(self::$log)) {
					self::$log = new WC_Logger();
				}
				self::$log->add('greenmoney', $message);
			}
		}

		// Check if we are forcing SSL on checkout pages
		public function do_ssl_check()
		{
			if ((function_exists('wc_site_is_https') && ! wc_site_is_https()) && ('no' === get_option('woocommerce_force_ssl_checkout') && ! class_exists('WordPressHTTPS'))) {
				echo '<div class="error"><p>' . sprintf(__('<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href="%s">forcing the checkout pages to be secured.</a>', 'woocommerce-gateway-green-money'), $this->method_title, admin_url('admin.php?page=wc-settings&tab=checkout')) . '</p></div>';
			}
		}

		/**
		 * The order was cancelled so we want to try and cancel the Check in Green
		 *
		 * @param string $order_id
		 */
		public function order_cancelled_so_cancelcheck($order_id)
		{
			$order = wc_get_order($order_id);
			if (! $this->via_greenmoney($order)) {
				return false;
			}

			$action = "CancelCheck";
			$data = array(
				"Store" => $this->useStoreURL,
				"OrderID" => $order_id
			);

			$cancelResponse = $this->callGreenAPI($action, $data, "WooCommerce.asmx");
			if ($cancelResponse->Result->Result == "0") {
				$this->log(__('Check canceled. ', 'woocommerce-gateway-green-money'));
				$order->add_order_note(__('Order Cancelled so Check was also Canceled', 'woocommerce-gateway-green-money'));
				//Cancel the order if it has not already been marked as cancelled
				if ('cancelled' != $order->get_status()) {
					$order->update_status('cancelled');
				}
			}
		}

		/**
		 * Check if this gateway is enabled
		 */
		public function is_available()
		{
			return true;

			// Check if we're on a WooCommerce Blocks Checkout Page
			if ($this->is_blocks_editor_page()) {
				return true;
			}

			if ((is_checkout() || is_checkout_pay_page()) && ! is_ssl()) {
				return false;
			}

			if (! $this->client_id || ! $this->client_password || ! $this->endpoint || !$this->rest_client_id || !$this->rest_client_secret) {
				return false;
			}
			return true;
		}

		/**
		 * Determine if the current page is a WooCommerce Blocks-based page.
		 *
		 * @return bool
		 */
		private function is_blocks_editor_page()
		{
			// Check for specific WooCommerce Blocks context via query parameter
			if (defined('REST_REQUEST') && REST_REQUEST && isset($_GET['context']) && $_GET['context'] === 'edit') {
				return true;
			}

			// Check for WooCommerce Blocks usage based on WooCommerce Blocks-specific data
			if (did_action('woocommerce_blocks_loaded')) {
				return true;
			}

			return false;
		}

		/**
		 * Safely get and trim data from $_POST
		 *
		 * @since 1.0.0
		 * @param string $key array key to get from $_POST array
		 * @return string value from $_POST or blank string if $_POST[ $key ] is not set
		 */
		public static function get_post($key)
		{
			if (isset($_POST[$key])) {
				return trim($_POST[$key]);
			}

			return '';
		}

               public function payment_fields()
               {
                       echo '<div id="wc_greenpay_mount"></div>';
                       ?>
                       <style>
                               .wc-greenpay-fallback { display: none; }
                               html.no-js .wc-greenpay-fallback { display: block; }
                       </style>
                       <div class="wc-greenpay-fallback">
                               <p>
                                       <label for="wc_greenpay_routing_number"><?php esc_html_e('Routing Number', 'woocommerce-gateway-green-money'); ?></label>
                                       <input type="text" id="wc_greenpay_routing_number" name="wc_greenpay_routing_number" />
                               </p>
                               <p>
                                       <label for="wc_greenpay_account_number"><?php esc_html_e('Account Number', 'woocommerce-gateway-green-money'); ?></label>
                                       <input type="text" id="wc_greenpay_account_number" name="wc_greenpay_account_number" />
                               </p>
                               <input type="hidden" id="wc_greenpay_token" name="wc_greenpay_token" />
                               <input type="hidden" id="wc_greenpay_institution" name="wc_greenpay_institution" />
                               <input type="hidden" id="wc_greenpay_payment_method" name="wc_greenpay_payment_method" value="" />
                               <input type="hidden" id="wc_greenpay_context" name="wc_greenpay_context" value="classic" />
                       </div>
                       <?php
               }

		/**
		 * Returns true if the posted echeck fields are valid, false otherwise
		 *
		 * @since 1.0.0
		 * @param bool $is_valid true if the fields are valid, false otherwise
		 * @return bool
		 */
		public function validate_classic_fields()
		{
			$payment_method = $_POST['wc_greenpay_payment_method'];

			if ($payment_method === 'debit') {
				$routing_number = $_POST['wc_greenpay_routing_number'];
				$account_number = $_POST['wc_greenpay_account_number'];

				// routing number exists?
				if (empty($routing_number)) {
					wc_add_notice(esc_html__('Routing Number is missing ' . $routing_number, 'woocommerce-gateway-green-money'), 'error');
					return false;
				} else {
					// routing number digit validation
					$message = "";
					if (!$this->routing_number_validate($routing_number, $message)) {
						wc_add_notice(esc_html__('Routing Number is invalid: ' . $message, 'woocommerce-gateway-green-money'), 'error');
						return false;
					}
				}

				// account number exists?
				if (empty($account_number)) {
					wc_add_notice(esc_html__('Account Number is missing' . $account_number, 'woocommerce-gateway-green-money'), 'error');
					return false;
				} else {
					// account number length validation
					if (strlen($account_number) < 5 || strlen($account_number) > 17) {
						wc_add_notice(esc_html__('Account number is invalid (must be between 5 and 17 digits)', 'woocommerce-gateway-green-money'), 'error');
						return false;
					}
				}

				return true;
			} else if ($payment_method === 'plaid') {
				$token = $_POST['wc_greenpay_token'];
				$institution = $_POST['wc_greenpay_institution'];

				if (empty($token) || empty($institution)) {
					wc_add_notice(esc_html__('Bank login session data not found.', 'woocommerce-gateway-green-money'), 'error');
					return false;
				}

				return true;
			} else {
				return false;
			}
		}

		/**
		 * Internal message that is used to call the Green API in some form or fashion
		 *
		 * @param string $messageName	The name of the individual method to call in the fully qualified API endpoint URL like OneTimeDraft or RegisterStore
		 * @param array $data			An array of key => value pairs that will be serialized and sent to the API as the parameters
		 * @param string $method		Optional. If supplied, will override the method name in the fully qualified API. Example "eCart.asmx" or "FTFTokenizer.asmx"
		 *
		 * @return mixed				An associative array of SimpleXMlElementObjects representing the return of the API OR boolean False if the call failed for any reason
		 */
		function callGreenAPI($messageName, $data, $method = null)
		{
			$prev_method = $this->method;
			if ($method != null) {
				$this->method = $method;
			}

			if (!isset($data['Client_ID'])) {
				$data["Client_ID"] = $this->client_id;
			}

			// Sanatize passwords for the log
			// Always set ApiPassword to redacted as we will always be sending Client_ID and ApiPassword for each API call
			$data['ApiPassword'] = 'REDACTED';

			if (isset($data['RESTConsumerSecret']) && strlen($data['RESTConsumerSecret']) > 8) {
				//We do not filter if the REST Secret has length <= 9 because it will definitely be malformed and we want to see what they have in the setting field
				$data['RESTConsumerSecret'] = str_repeat("X", strlen($data['RESTConsumerSecret']) - 8) . substr($data['RESTConsumerSecret'], -8);
			}

			// Prepare payload for transer
			$this->log(__('Preparing data, endpoint is ', 'woocommerce-gateway-green-money') . $this->full_endpoint() . $messageName);
			$data_string = http_build_query($data);
			$this->log(__('Sending POST to GreenPay™, this is what we are sending: ', 'woocommerce-gateway-green-money') . $data_string);

			// Set back to actual passwords so the call goes through
			// Always set ApiPassword here because we will always be sending Client_ID and ApiPassword for each API call
			$data['ApiPassword'] = $this->client_password;
			if (isset($data['RESTConsumerSecret'])) {
				$data['RESTConsumerSecret'] = $this->rest_client_secret;
			}

			// Decode Store URL if it's already encoded to prevent double-encoding
			// http_build_query() will encode it properly, so we need the raw URL
			// Also remove trailing slashes as the WebMethod expects URLs without them
			if (isset($data['Store'])) {
				$decoded_store = urldecode($data['Store']);
				// Only use decoded version if it's different (was encoded) and looks like a URL
				if ($decoded_store !== $data['Store'] && (strpos($decoded_store, 'http://') === 0 || strpos($decoded_store, 'https://') === 0)) {
					$data['Store'] = $decoded_store;
				}
				// Always remove trailing slash from Store URL
				$data['Store'] = untrailingslashit($data['Store']);
			}

			try {
				$ch = curl_init();

				if ($ch === FALSE) {
					throw new \Exception('Failed to initialize cURL');
				}

				curl_setopt($ch, CURLOPT_URL, $this->full_endpoint() . $messageName);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

				$response = curl_exec($ch);
				$this->log(__('Raw Response: ', 'woocommerce-gateway-green-money') . print_r($response, true));

				if ($response === FALSE) {
					throw new \Exception(curl_error($ch), curl_errno($ch));
				}

				$this->method = $prev_method;
				curl_close($ch);
			} catch (\Exception $e) {
				$this->log(__('cURL failed with error #' . $e->getCode() . ': ' . $e->getMessage(), 'woocommerce-gateway-green-money'));
				$this->green_money_setLastError(sprintf('Curl failed with error #%d: %s', $e->getCode(), $e->getMessage()));
				$this->method = $prev_method;
				return false;
			}

			try {
				//Suppress all XML warnings
				libxml_use_internal_errors(true);
				$loadedResponse =  simplexml_load_string($response, "SimpleXMLElement", LIBXML_NOWARNING);
				if (!$loadedResponse) {
					$this->log("Unable to parse API results: XML is invalid");
					$this->green_money_setLastError("Unable to parse API results: XML is invalid");
					libxml_use_internal_errors(false);
					return false;
				} else {
					$this->log(__('Loaded Response: ', 'woocommerce-gateway-green-money') . print_r($loadedResponse, true));
					libxml_use_internal_errors(false);
					return $loadedResponse;
				}
			} catch (\Exception $e) {
				$this->log("Unable to parse API results: " . $e->getMessage());
				$this->green_money_setLastError("An error occurred while attempting to parse the API result: " . $e->getMessage());
				libxml_use_internal_errors(false);
				return false;
			}
		}

		/**
		 * Helper function to process payment for both normal and subscription orders.
		 *
		 * @param WC_Order $order The order object.
		 * @param string $payment_method The payment method used (either 'plaid' or 'debit').
		 * @param string $token The Plaid token if using Plaid, otherwise empty.
		 * @param string $institution The Plaid institution ID if using Plaid, otherwise empty.
		 * @param string $routing_number The routing number if using eCheck, otherwise empty.
		 * @param string $account_number The account number if using eCheck, otherwise empty.
		 * @return array An array containing created order IDs and any errors encountered.
		 */
		private function process_payment_helper($order, $payment_method, $token, $institution, $routing_number, $account_number)
		{
			$created_orders = [];
			$errors = [];

			$grouped = $this->group_order_items_by_subscription($order);
			$normal_items = $grouped['normal_items'];
			$subscription_items = $grouped['subscription_items'];

			if (!empty($normal_items)) {
				$normal_item_ids = array_map(function ($item) {
					return $item->get_id();
				}, $normal_items);

				foreach ($order->get_items() as $item_id => $item) {
					if (!in_array($item->get_id(), $normal_item_ids, true)) {
						$order->remove_item($item_id);
					}
					   // ✅ Save all the meta changes
					   $order->save();
					// Clean up the transient after saving to the order
					delete_transient('checkout_temp_data');
				}

				$order->calculate_totals();
				$order_data = $this->get_order_info($order);

				if ($payment_method === 'plaid') {
					$order_data['token'] = $token;
					$order_data['institution'] = $institution;
					$response = $this->process_payment_plaid($order->get_id(), $order, $order_data);
				} else {
					$order_data['routing_number'] = $routing_number;
					$order_data['account_number'] = $account_number;
					$response = $this->process_payment_echeck($order->get_id(), $order, $order_data);
				}

				if ($response['result'] !== 'success') {
					$errors[] = $response['failures'] ?? ($payment_method === 'plaid' ? 'Plaid normal payment failed.' : 'Debit normal payment failed.');
				}

				$created_orders[] = $order->get_id();
			}

			if (!empty($subscription_items)) {
				$start = 0;
				if (empty($created_orders)) {
					$first_sub = $subscription_items[0];
					// Remove all other items from the original order
					foreach ($order->get_items() as $item_id => $item) {
						if ($item->get_product_id() !== $first_sub['product']->get_id()) {
							$order->remove_item($item_id);
						}
					}

					$order->calculate_totals();
					$order_data = $this->get_order_info($order);
					$order_data['interval_type'] = $first_sub['interval_type'];
					$order_data['repeat'] = $first_sub['repeat'];

					if ($payment_method === 'plaid') {
						$order_data['token'] = $token;
						$order_data['institution'] = $institution;
						$response = $this->process_recurring_payment_plaid($order, $order_data);
					} else {
						$order_data['routing_number'] = $routing_number;
						$order_data['account_number'] = $account_number;
						$response = $this->process_recurring_payment_echeck($order, $order_data);
					}

					if ($response['result'] !== 'success') {
						$errors[] = $response['failures'] ?? ($payment_method === 'plaid' ? 'Plaid normal payment failed.' : 'Debit normal payment failed.');
					}

					$created_orders[] = $order->get_id();
					$start = 1; // Start from the second item
				}

				// For the remaining subscription items, create new orders
				for ($i = $start; $i < count($subscription_items); $i++) {
					$sub_item = $subscription_items[$i];
					$sub_order = wc_create_order();
					$sub_order->set_customer_id($order->get_user_id());
					$sub_order->add_product($sub_item['product'], $sub_item['item']->get_quantity());
					$sub_order->set_address($order->get_address('billing'), 'billing');
					$sub_order->calculate_totals();
					$sub_order->save();
					$sub_data = $this->get_order_info($sub_order);
					$sub_data['interval_type'] = $sub_item['interval_type'];
					$sub_data['repeat'] = $sub_item['repeat'];

					if ($payment_method === 'plaid') {
						$sub_data['token'] = $token;
						$sub_data['institution'] = $institution;
						$response = $this->process_recurring_payment_plaid($sub_order, $sub_data);
					} else {
						$sub_data['routing_number'] = $routing_number;
						$sub_data['account_number'] = $account_number;
						$response = $this->process_recurring_payment_echeck($sub_order, $sub_data);
					}

					if ($response['result'] !== 'success') {
						$errors[] = $response['failures'] ?? ($payment_method === 'plaid' ? 'Plaid recurring payment failed.' : 'Debit recurring payment failed.');
					}

					$created_orders[] = $sub_order->get_id();
				}
			}

			if (count($created_orders) > 1) {
				$order->update_meta_data('_split_sub_orders', $created_orders);
				$order->save();
			}

			return ['created_orders' => $created_orders, 'errors' => $errors];
		}

		/**
		 * Process the payment for REST API checkout used in Blocks Checkout.
		 *
		 * @param object $context The context object containing order and payment data.
		 * @param array $result The result array to be modified.
		 *
		 * @return array|WP_Error Returns the modified result array or a WP_Error on failure.
		 *
		 * See WC_Payment_Gateway::process_payment, WC_Order::update_status()
		 */
		public function rest_checkout($context, $result)
		{
			if ($context->payment_method !== 'greenmoney') {
				return $result;
			}

			$order           = $context->order;
			$payment_method  = $context->payment_data['payment_method'];
			$token           = $context->payment_data['token'];
			$institution     = $context->payment_data['institution'];
			$routing_number  = $context->payment_data['routing_number'];
			$account_number  = $context->payment_data['account_number'];

			$payment_result = $this->process_payment_helper($order, $payment_method, $token, $institution, $routing_number, $account_number);

			// Redirect to last processed sub-order thank you page
			if (empty($payment_result['errors'])) {
				$final_order = wc_get_order(end($payment_result['created_orders']));
				return $this->rest_success_response($result, $this->get_return_url($final_order));
			} else {
				return new WP_Error(400, implode("\n", $payment_result['errors']));
			}
		}

		/**
		 * Process the payment for AJAX checkout used in Classic Checkout.
		 * If payment fails for any reason, we should throw an error with wc_notice and return null
		 *
		 * @param int $order_id
		 * @return array
		 *
		 * See WC_Payment_Gateway::process_payment, WC_Order::update_status()
		 */
		public function process_payment($order_id)
		{
			$order = wc_get_order($order_id);
			$orderdata = $this->get_order_info($order);

			if ($orderdata['payment_method'] !== 'greenmoney') {
				return null;
			}

			if (!$this->validate_classic_fields()) {
				return null;
			}

			$payment_method = $_POST['wc_greenpay_payment_method'];
			$token = $_POST['wc_greenpay_token'];
			$institution = $_POST['wc_greenpay_institution'];
			$routing_number = $_POST['wc_greenpay_routing_number'];
			$account_number = $_POST['wc_greenpay_account_number'];

			$result = $this->process_payment_helper($order, $payment_method, $token, $institution, $routing_number, $account_number);

			if (!empty($result['errors'])) {
				return array(
					'result' => 'failure',
					'failures' => json_encode($result['errors'])
				);
			}

			$final_order = wc_get_order(end($result['created_orders']));

			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url($final_order),
			);
		}

		/**
		 * Call to the Green API to generate the plaid check and return the status of that to the front end
		 *
		 * @param int $order_id		The id of the WooCommerce Order instance we're checking out for.
		 * @param mixed $order		The WooCommerce Order Instance we're checking out for.
		 * @param array $orderdata	The result of self::get_order_info on the order
		 *
		 * @return array|void Returns an array with success info or null on error
		 */
		private function process_payment_plaid($order_id, $order, $orderdata)
		{
			$ve = get_option('gmt_offset') > 0 ? '+' : '-';
			$check_date = date("m-d-Y", strtotime('now ' . $ve . get_option('gmt_offset') . ' HOURS'));

			$data = array(
				"Store" => $this->useStoreURL,
				"OrderID" => $order_id,
				"FirstName" => $orderdata["billing_first_name"],
				"LastName" => $orderdata["billing_last_name"],
				"NameOnAccount" => "{$orderdata["billing_first_name"]}  {$orderdata["billing_last_name"]}",
				'EmailAddress' => $orderdata["billing_email"],
				'Phone' => $orderdata["billing_phone"],
				'PhoneExtension' => '',
				'Address1' => $orderdata["billing_address_1"],
				'Address2' => $orderdata["billing_address_2"],
				'City' => $orderdata["billing_city"],
				'State' => $orderdata["billing_state"],
				'Zip' => $orderdata["billing_postcode"],
				'Country' => $orderdata["billing_country"],
				'Token' => $orderdata['token'],
				'InstitutionName' => $orderdata['institution'],
				'CheckMemo'	=> __('Order #', 'woocommerce-gateway-green-money') . $order->get_order_number(),
				'CheckAmount' => $order->get_total(),
				'CheckDate' => $check_date,
				'CheckNumber' => '',
				'AllowRisky' => '1'
			);

			// Send this payload to GreenPay™ for processing
			$response = $this->callGreenAPI('PlaidOneTimeDraft', $data, "WooCommerce.asmx");

			if ($response && $response->Result->Result == '0') {
				//Check was accepted and either passed verification or was risky and store allowed it. We are all good to go here.
				$this->log(sprintf(__('GreenPay™ check accepted (Check_ID: %s, CheckNumber: %s)', 'woocommerce-gateway-green-money'), $response->Check_ID, $response->CheckNumber));

				if (strtolower($response->ResponseCode->Passes) == "false" && strtolower($response->ResponseCode->Risky) == "true") {
					$order->add_order_note(sprintf(__('GreenPay™ check has a risky verification code (%s) that must be overridden manually.', 'woocommerce-gateway-green-money'), $response->ResponseCode->Code));
				}
				  // ✅ Use HPOS-safe method
				  $order->add_meta_data( '_greenmoney_payment_check_id', (string) $response->Check_ID );
				  $order->add_meta_data( '_greenmoney_payment_check_number', (string) $response->CheckNumber );
				  $order->save();
				// Empty cart
				WC()->cart->empty_cart();
				// Return thankyou redirect
				return array(
					'result'	 => 'success',
					'redirect'	 => $this->get_return_url($order),
				);
			} else {
				// Check for Internal Duplicate (result code 47)
				$result_code = (string) ($response->Result->Result ?? '');
				if ($result_code === '47') {
					$this->log(__('Internal Duplicate detected. GreenPay™ returned result code 47: ' . ($response->Result->ResultDescription ?? 'Internal Duplicate'), 'woocommerce-gateway-green-money'));
					
					// Add order note explaining the duplicate was detected and blocked
					$order->add_order_note(
						__('GreenPay™ detected a duplicate payment attempt for this order and blocked it to prevent creating a second check. ' .
						'If you are unsure about the current status of this payment/order, please contact the merchant.', 'woocommerce-gateway-green-money')
					);
					
					// Do not change order status - leave it as-is
					// Return failure to prevent further processing, but don't mark order as failed
					wc_add_notice(__('A duplicate payment attempt was detected for this order. If you have already completed payment, please contact us if you have any questions.', 'woocommerce-gateway-green-money'), 'error');
					
					return array(
						'result'   => 'failure',
						'failures' => json_encode(array(
							array(
								'code' => '47',
								'message' => $response->Result->ResultDescription ?? __('Internal Duplicate - duplicate payment attempt blocked', 'woocommerce-gateway-green-money')
							)
						))
					);
				}
				
				$this->log(__('Check is not accepted. Green returned error description: ' . $response->Result->Description, 'woocommerce-gateway-green-money'));

				$failures = array(
					array(
						'code' => $response->Result->Result ?? 'unknown_error',
						'message' => $response->Result->ResultDescription ?? __('Unknown error occurred.', 'woocommerce-gateway-green-money')
					)
				);

				$order->update_status('failed', __('Attemped call to GreenPay™ service failed to create check.<br/><br/>Response (if available): ' . $response->Result->ResultDescription . "<br/><br/>", 'woocommerce-gateway-green-money'));
				wc_add_notice("Payment error: " . $response->Result->ResultDescription, 'error');
				return array(
					'result' 	 => 'failure',
					'failures'   => json_encode($failures)
				);
			}
		}

		/**
		 * Call to the Green API to generate the check and return the status of that to the front end
		 *
		 * @param int $order_id		The id of the WooCommerce Order instance we're checking out for.
		 * @param mixed $order		The WooCommerce Order Instance we're checking out for.
		 * @param array $orderdata	The result of self::get_order_info on the order
		 *
		 * @return array|void Returns an array with success info or null on error
		 */

		private function process_payment_echeck($order_id, $order, $orderdata)
		{
			// Normalize order object & ID
			$order = is_a($order, 'WC_Order') ? $order : wc_get_order($order);
			$order_id = $order->get_id();

			$ve = get_option('gmt_offset') > 0 ? '+' : '-';
			$check_date = date("m-d-Y", strtotime('now ' . $ve . get_option('gmt_offset') . ' HOURS'));

			$data = array(
				"Store" => $this->useStoreURL,
				"OrderID" => $order_id,
				"FirstName" => $orderdata["billing_first_name"],
				"LastName" => $orderdata["billing_last_name"],
				"NameOnAccount" => "{$orderdata["billing_first_name"]}  {$orderdata["billing_last_name"]}",
				'EmailAddress' => $orderdata["billing_email"],
				'Phone' => $orderdata["billing_phone"],
				'PhoneExtension' => '',
				'Address1' => $orderdata["billing_address_1"],
				'Address2' => $orderdata["billing_address_2"],
				'City' => $orderdata["billing_city"],
				'State' => $orderdata["billing_state"],
				'Zip' => $orderdata["billing_postcode"],
				'Country' => $orderdata["billing_country"],
				'RoutingNumber' => $orderdata['routing_number'],
				'AccountNumber' => $orderdata['account_number'],
				'BankName' => '',
				'CheckMemo'	=> __('Order #', 'woocommerce-gateway-green-money') . $order->get_order_number(),
				'CheckAmount' => $orderdata['order_total'],
				'CheckDate' => $check_date,
				'CheckNumber' => '',
				"AllowRisky" => ($this->verification_mode === "permissive")
			);

			// Send this payload to GreenPay™ for processing
			$response = $this->callGreenAPI('OneTimeDraft', $data, "WooCommerce.asmx");

			if ($response && isset($response->Result->Result) && $response->Result->Result == '0') {
				$this->log(sprintf(__('GreenPay™ check accepted (Check_ID: %s, CheckNumber: %s)', 'woocommerce-gateway-green-money'), $response->Check_ID, $response->CheckNumber));

				if (strtolower($response->ResponseCode->Passes) == "false" && strtolower($response->ResponseCode->Risky) == "true") {
					$order->add_order_note(sprintf(__('GreenPay™ check has a risky verification code (%s) that must be overridden manually.', 'woocommerce-gateway-green-money'), $response->ResponseCode->Code));
				}
				$order->add_meta_data( '_greenmoney_payment_check_id', (string) $response->Check_ID );
                $order->add_meta_data( '_greenmoney_payment_check_number', (string) $response->CheckNumber );
                $order->save(); 
				// Empty cart
				WC()->cart->empty_cart();

				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url($order),
				);
			} else {
				// Check for Internal Duplicate (result code 47)
				$result_code = (string) ($response->Result->Result ?? '');
				if ($result_code === '47') {
					$this->log(__('Internal Duplicate detected. GreenPay™ returned result code 47: ' . ($response->Result->ResultDescription ?? 'Internal Duplicate'), 'woocommerce-gateway-green-money'));
					
					// Add order note explaining the duplicate was detected and blocked
					$order->add_order_note(
						__('GreenPay™ detected a duplicate payment attempt for this order and blocked it to prevent creating a second check. ' .
						'If you are unsure about the current status of this payment/order, please contact the merchant.', 'woocommerce-gateway-green-money')
					);
					
					// Do not change order status - leave it as-is
					// Return failure to prevent further processing, but don't mark order as failed
					wc_add_notice(__('A duplicate payment attempt was detected for this order. If you have already completed payment, please contact us if you have any questions.', 'woocommerce-gateway-green-money'), 'error');
					
					return array(
						'result'  => 'failure',
						'failures' => json_encode(array(
							array(
								'code' => '47',
								'message' => $response->Result->ResultDescription ?? __('Internal Duplicate - duplicate payment attempt blocked', 'woocommerce-gateway-green-money')
							)
						))
					);
				}
				
				$this->log(__('Check is not accepted. Green returned error description: ' . $response->Result->Description, 'woocommerce-gateway-green-money'));

				$failures = array(
					array(
						'code' => $response->Result->Result ?? 'unknown_error',
						'message' => $response->Result->ResultDescription ?? __('Unknown error occurred.', 'woocommerce-gateway-green-money')
					)
				);

				$order->update_status('failed', __('Attemped call to GreenPay™ service failed to create check.<br/><br/>Response (if available): ' . $response->Result->ResultDescription . "<br/><br/>", 'woocommerce-gateway-green-money'));
				wc_add_notice("Payment error: " . $response->Result->ResultDescription, 'error');
				return array(
					'result'  => 'failure',
					'failures' => json_encode($failures)
				);
			}
		}

		/**
		 * Call to the Green API to generate a recurring payment via eCheck and return the status to the front end
		 * 
		 * @param int|WC_Order $order_input The WooCommerce Order instance or ID
		 * @param array $account_data The result of self::get_order_info on the order, plus Plaid fields
		 * @return array Result array with success/failure info
		 */
		private function process_recurring_payment_echeck($order_input, $account_data)
		{
			$order    = is_a($order_input, 'WC_Order') ? $order_input : wc_get_order($order_input);
			$order_id = $order->get_id();

			$ve         = get_option('gmt_offset') > 0 ? '+' : '-';
			$check_date = date("m-d-Y", strtotime('now ' . $ve . get_option('gmt_offset') . ' HOURS'));

			$data = array(
				"Store"             => $this->useStoreURL,
				"OrderID"           => $order_id,
				"FirstName"         => $account_data["billing_first_name"],
				"LastName"          => $account_data["billing_last_name"],
				"NameOnAccount"     => "{$account_data["billing_first_name"]} {$account_data["billing_last_name"]}",
				"EmailAddress"      => $account_data["billing_email"],
				"Phone"             => $account_data["billing_phone"],
				"PhoneExtension"    => '',
				"Address1"          => $account_data["billing_address_1"],
				"Address2"          => $account_data["billing_address_2"],
				"City"              => $account_data["billing_city"],
				"State"             => $account_data["billing_state"],
				"Zip"               => $account_data["billing_postcode"],
				"Country"           => $account_data["billing_country"],
				"RoutingNumber"     => $account_data['routing_number'],
				"AccountNumber"     => $account_data['account_number'],
				"BankName"          => '',
				"CheckMemo"         => __('Recurring for Order #', 'woocommerce-gateway-green-money') . $order->get_order_number(),
				"CheckAmount"       => $order->get_total(),
				"CheckDate"         => $check_date,
				"CheckNumber"       => '',
				"AllowRisky"        => ($this->verification_mode === "permissive"),
				"RecurringType"     => $account_data["interval_type"],
				"RecurringOffset"   => '1',
				"RecurringPayments" => $account_data["repeat"]
			);

			self::log("Sending RecurringDraft request for Order #{$order_id}...");
			$response = $this->callGreenAPI('RecurringDraft', $data, "WooCommerce.asmx");

			// Defensive check
			if (!$response || !isset($response->Result->Result)) {
				self::log("ERROR: No valid response from GreenPay™ for recurring Order #{$order_id}");
				$order->update_status('failed', __('Recurring payment failed: No response from gateway', 'woocommerce-gateway-green-money'));
				return [
					'result'  => 'failure',
					'message' => 'No response from gateway'
				];
			}

			$result      = (string) $response->Result->Result;
			$result_desc = (string) $response->Result->ResultDescription;

			if ($result === '0') {
				$this->log(sprintf(__('GreenPay™ check accepted (Check_ID: %s, CheckNumber: %s)', 'woocommerce-gateway-green-money'), $response->Check_ID, $response->CheckNumber));

				// Risky flag
				if (
					isset($response->ResponseCode->Passes) && strtolower($response->ResponseCode->Passes) === "false"
					&& strtolower($response->ResponseCode->Risky) === "true"
				) {
					$order->add_order_note(sprintf(__('GreenPay™ recurring check has a risky code (%s) that may need manual approval.', 'woocommerce-gateway-green-money'), $response->ResponseCode->Code));
				}

				$order->update_meta_data('_greenmoney_payment_check_id', (string) $response->Check_ID, true);
				$order->update_meta_data('_greenmoney_payment_check_number', (string) $response->CheckNumber, true);
				$order->save();

				WC()->cart->empty_cart();

				return array(
					'result'	 => 'success',
					'redirect'	 => $this->get_return_url($order),
				);
			} else {
				// Check for Internal Duplicate (result code 47)
				if ($result === '47') {
					self::log("Internal Duplicate detected for recurring eCheck Order #{$order_id}. GreenPay™ returned result code 47: " . ($result_desc ?? 'Internal Duplicate'));
					
					// Add order note explaining the duplicate was detected and blocked
					$order->add_order_note(
						__('GreenPay™ detected a duplicate payment attempt for this order and blocked it to prevent creating a second check. ' .
						'If you are unsure about the current status of this payment/order, please contact the merchant.', 'woocommerce-gateway-green-money')
					);
					
					// Do not change order status - leave it as-is
					// Return failure to prevent further processing, but don't mark order as failed
					return [
						'result'  => 'failure',
						'message' => __('A duplicate payment attempt was detected for this order. If you have already completed payment, please contact us if you have any questions.', 'woocommerce-gateway-green-money'),
						'failures' => json_encode(array(
							array(
								'code' => '47',
								'message' => $result_desc ?? __('Internal Duplicate - duplicate payment attempt blocked', 'woocommerce-gateway-green-money')
							)
						))
					];
				}
				
				$this->log(__('Check is not accepted. Green returned error description: ' . $result_desc, 'woocommerce-gateway-green-money'));

				$failures = array(
					array(
						'code' => $result ?? 'unknown_error',
						'message' => $result_desc ?? __('Unknown error occurred.', 'woocommerce-gateway-green-money')
					)
				);

				$order->update_status('failed', __('Attemped call to GreenPay™ service failed to create check.<br/><br/>Response (if available): ' . $result_desc . "<br/><br/>", 'woocommerce-gateway-green-money'));
				wc_add_notice("Payment error: " . $result_desc, 'error');

				return array(
					'result' 	 => 'failure',
					'failures'   => json_encode($failures)
				);
			}
		}

		/**
		 * Call to the Green API to generate a recurring payment via Plaid and return the status to the front end
		 *
		 * @param int|WC_Order $order_input The WooCommerce Order instance or ID
		 * @param array $account_data The result of self::get_order_info on the order, plus Plaid fields
		 * @return array Result array with success/failure info
		 */
		private function process_recurring_payment_plaid($order_input, $account_data)
		{
			$order    = is_a($order_input, 'WC_Order') ? $order_input : wc_get_order($order_input);
			$order_id = $order->get_id();

			$ve         = get_option('gmt_offset') > 0 ? '+' : '-';
			$check_date = date("m-d-Y", strtotime('now ' . $ve . get_option('gmt_offset') . ' HOURS'));

			$data = array(
				"Store"             => $this->useStoreURL,
				"OrderID"           => $order_id,
				"FirstName"         => $account_data["billing_first_name"],
				"LastName"          => $account_data["billing_last_name"],
				"NameOnAccount"     => "{$account_data["billing_first_name"]} {$account_data["billing_last_name"]}",
				"EmailAddress"      => $account_data["billing_email"],
				"Phone"             => $account_data["billing_phone"],
				"PhoneExtension"    => '',
				"Address1"          => $account_data["billing_address_1"],
				"Address2"          => $account_data["billing_address_2"],
				"City"              => $account_data["billing_city"],
				"State"             => $account_data["billing_state"],
				"Zip"               => $account_data["billing_postcode"],
				"Country"           => $account_data["billing_country"],
				"Token"             => $account_data['token'],
				"InstitutionName"   => $account_data['institution'],
				"CheckMemo"         => __('Recurring for Order #', 'woocommerce-gateway-green-money') . $order->get_order_number(),
				"CheckAmount"       => $order->get_total(),
				"CheckDate"         => $check_date,
				"CheckNumber"       => '',
				"AllowRisky"        => ($this->verification_mode === "permissive"),
				"RecurringType"     => $account_data["interval_type"],
				"RecurringOffset"   => '1',
				"RecurringPayments" => $account_data["repeat"]
			);

			self::log("Sending PlaidRecurringDraft request for Order #{$order_id}...");
			$response = $this->callGreenAPI('PlaidRecurringDraft', $data, "WooCommerce.asmx");

			if (!$response || !isset($response->Result->Result)) {
				self::log("ERROR: No valid response from GreenPay™ for recurring Plaid Order #{$order_id}");
				$order->update_status('failed', __('Recurring Plaid payment failed: No response from gateway', 'woocommerce-gateway-green-money'));
				return [
					'result'  => 'failure',
					'message' => 'No response from gateway'
				];
			}

			$result      = (string) $response->Result->Result;
			$result_desc = (string) $response->Result->ResultDescription;
			self::log("GreenPay™ PlaidRecurringDraft Response for Order #{$order_id}: Result={$result}, Desc={$result_desc}");

			if ($result === '0') {
				$this->log(sprintf(__('GreenPay™ check accepted (Check_ID: %s, CheckNumber: %s)', 'woocommerce-gateway-green-money'), $response->Check_ID, $response->CheckNumber));

				// Risky flag
				if (
					isset($response->ResponseCode->Passes) && strtolower($response->ResponseCode->Passes) === "false"
					&& strtolower($response->ResponseCode->Risky) === "true"
				) {
					$order->add_order_note(sprintf(__('GreenPay™ recurring Plaid check has a risky code (%s) that may need manual approval.', 'woocommerce-gateway-green-money'), $response->ResponseCode->Code));
				}

				// Update order meta
				$order->update_meta_data('_greenmoney_payment_check_id', (string) $response->Check_ID, true);
				$order->update_meta_data('_greenmoney_payment_check_number', (string) $response->CheckNumber, true);
				$order->save();

				WC()->cart->empty_cart();

				return [
					'result'  => 'success',
					'message' => $result_desc
				];
			} else {
				// Check for Internal Duplicate (result code 47)
				if ($result === '47') {
					self::log("Internal Duplicate detected for recurring Plaid Order #{$order_id}. GreenPay™ returned result code 47: " . ($result_desc ?? 'Internal Duplicate'));
					
					// Add order note explaining the duplicate was detected and blocked
					$order->add_order_note(
						__('GreenPay™ detected a duplicate payment attempt for this order and blocked it to prevent creating a second check. ' .
						'If you are unsure about the current status of this payment/order, please contact the merchant.', 'woocommerce-gateway-green-money')
					);
					
					// Do not change order status - leave it as-is
					// Return failure to prevent further processing, but don't mark order as failed
					return [
						'result'  => 'failure',
						'message' => __('A duplicate payment attempt was detected for this order. If you have already completed payment, please contact us if you have any questions.', 'woocommerce-gateway-green-money'),
						'failures' => json_encode(array(
							array(
								'code' => '47',
								'message' => $result_desc ?? __('Internal Duplicate - duplicate payment attempt blocked', 'woocommerce-gateway-green-money')
							)
						))
					];
				}
				
				$this->log(__('Check is not accepted. Green returned error description: ' . $response->Result->Description, 'woocommerce-gateway-green-money'));

				$failures = array(
					array(
						'code' => $result ?? 'unknown_error',
						'message' => $result_desc ?? __('Unknown error occurred.', 'woocommerce-gateway-green-money')
					)
				);

				$order->update_status('failed', __('Attemped call to GreenPay™ service failed to create check.<br/><br/>Response (if available): ' . $result_desc . "<br/><br/>", 'woocommerce-gateway-green-money'));
				wc_add_notice("Payment error: " . $result_desc, 'error');
				return array(
					'result' 	 => 'failure',
					'failures'   => json_encode($failures)
				);
			}
		}

		/**
		 * Return the order information in a version independent way
		 *
		 * @param WC_Order $order
		 * @return array
		 */
		public function get_order_info($order)
		{
			$data = array(
				"id" => '',
				"payment_method" => '',
				"billing_first_name" => '',
				"billing_last_name" => '',
				"billing_email" => '',
				"billing_phone" => '',
				"billing_address_1" => '',
				"billing_address_2" => '',
				"billing_city" => '',
				"billing_state" => '',
				"billing_postcode" => '',
				"billing_country" => '',
				"order_total" => ''
			);
			if (version_compare(WC_VERSION, '3.0', '<')) {
				//Do it the old school way
				$data["id"] = $order->id;
				$data["payment_method"] = $order->payment_method;
				$data["billing_first_name"] = $order->billing_first_name;
				$data["billing_last_name"] = $order->billing_last_name;
				$data["billing_email"] = $order->billing_email;
				$data["billing_phone"] = $order->billing_phone;
				$data["billing_address_1"] = $order->billing_address_1;
				$data["billing_address_2"] = $order->billing_address_2;
				$data["billing_city"] = $order->billing_city;
				$data["billing_state"] = $order->billing_state;
				$data["billing_postcode"] = $order->billing_postcode;
				$data["billing_country"] = $order->billing_country;
				$data["order_total"] = $order->order_total;
			} else {
				//New school
				$data["id"] = $order->get_id();
				$data["payment_method"] = $order->get_payment_method();
				$data["billing_first_name"] = $order->get_billing_first_name();
				$data["billing_last_name"] = $order->get_billing_last_name();
				$data["billing_email"] = $order->get_billing_email();
				$data["billing_phone"] = $order->get_billing_phone();
				$data["billing_address_1"] = $order->get_billing_address_1();
				$data["billing_address_2"] = $order->get_billing_address_2();
				$data["billing_city"] = $order->get_billing_city();
				$data["billing_state"] = $order->get_billing_state();
				$data["billing_postcode"] = $order->get_billing_postcode();
				$data["billing_country"] = $order->get_billing_country();
				$data["order_total"] = $order->get_total();
			}
			return $data;
		}

		/**
		 * Run the CheckStatus method of the API and return the response
		 *
		 * @param integer $order_id
		 * @return boolean|SimpleXMLElement		Returns false on error and the response of the API on a success
		 */
		public function check_status($order_id)
		{
			$data = array(
				"Store" => $this->useStoreURL,
				"OrderID" => $order_id
			);

			$response = $this->callGreenAPI("CheckStatus", $data, "WooCommerce.asmx");
			if ($response) {
				return $response;
			} else {
				return false;
			}
		}

		/**
		* Can the order be refunded via GreenPay™?
		*
		* @param  WC_Order $order
		* @return bool
		*/
		public function via_greenmoney( $order ) {
			return $order && $order->get_meta( '_greenmoney_payment_check_id', true );
		}

		/**
		 * Process a refund if supported
		 *
		 * @param  int    $order_id
		 * @param  float  $amount
		 * @param  string $reason
		 * @return  boolean True or false based on success, or a WP_Error object
		 *
		 * See WC_Payment_Gateway::process_refund()
		 */
		public function process_refund($order_id, $amount = null, $reason = 'Refund')
		{
			$order = wc_get_order($order_id);

			if (! $this->via_greenmoney($order)) {
				$this->log(__('Refund Failed: Missing GreenPay™ Payment Reference ', 'woocommerce-gateway-green-money'));
				$order->add_order_note('Refund Failed: Missing GreenPay™ Payment Reference. You will have to use Green portal to process this refund manually if there is a Green Check ID associated with this order');
				return false;
			}

			if ($amount === null || $amount == 0) {
				$amount = number_format($order->get_total(), wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator());
			}

			if ($reason === null || strlen(trim($reason)) < 1) {
				$reason = "Refund";
			}

			$status = $this->check_status($order_id);
			if ($status) {
				$deleted = strtolower($status->Check->Deleted) == "true";
				$processed = strtolower($status->Check->Processed) == "true";
				$useFullAmount = ($amount === number_format($order->get_total(), wc_get_price_decimals(), wc_get_price_decimal_separator(), wc_get_price_thousand_separator()));

				if ($deleted) {
					$this->log(__('Refund Failed: Check deleted from GreenPay™ system.', 'woocommerce-gateway-green-money'));
					return false;
				}

				if (!$processed && $useFullAmount) {
					//Either it's not been processed OR we're trying to refund the full amount so just cancel the check
					$action = "CancelCheck";
					$data = array(
						"Store" => $this->useStoreURL,
						"OrderID" => $order_id
					);
				} elseif ($processed) {
					$action = "RefundCheck";
					$data = array(
						"Store" => $this->useStoreURL,
						"OrderID" => $order_id,
						"Memo" => $reason,
						"Amount" => $amount
					);
				} else {
					$this->log(__('Unable to process a refund for this check.', 'woocommerce-gateway-green-money'));
					return false;
				}

				$refundResponse = $this->callGreenAPI($action, $data, "WooCommerce.asmx");
				if ($refundResponse) {
					if ($refundResponse->Result->Result == "0") {
						if ('RefundCheck' === $action) {
							$this->log(__('Refund Accepted. GreenPay™ refund Check_ID: ', 'woocommerce-gateway-green-money') . $refundResponse->RefundCheck->Check_ID);
							$order->add_order_note(sprintf(__('Refunded %s - Refund Check ID: %s - refund Check Number: %s', 'woocommerce-gateway-green-money'), $amount, $refundResponse->RefundCheck->Check_ID, $refundResponse->RefundCheck->CheckNumber));
						} else {
							$this->log(__('Check canceled. ', 'woocommerce-gateway-green-money'));
							$order->add_order_note(__('Check Canceled', 'woocommerce-gateway-green-money'));
						}
						return true;
					} else {
						$this->log(sprintf(__('Refund was declined (Error code: %s, Description: %s)', 'woocommerce-gateway-green-money'), $refundResponse->Result->Result, $refundResponse->Result->ResultDescription));
						$order->add_order_note(sprintf(__('GreenPay™ refund was declined (Error code: %s, Description: %s)', 'woocommerce-gateway-green-money'), $refundResponse->Result->Result, $refundResponse->Result->ResultDescription));
						wc_add_notice((string) $refundResponse->Result->ResultDescription, 'error');
						return false;
					}
				} else {
					$this->log(__('Refund Request was not created. ', 'woocommerce-gateway-green-money'));
					return false;
				}
			} else {
				$this->log(__('Can\'t get status of the check.', 'woocommerce-gateway-green-money'));
				return false;
			}
		} //END process_refund()

		/**
		 * Call the Test API's TestAuthentication to validate the merchant's API credentials with their selected endpoint.
		 *
		 * @return boolean	True if the credentials validate, false otherwise.
		 */
		public function test_authentication()
		{
			if (!$this->client_id || strlen($this->client_id) == 0 || !$this->client_password || strlen($this->client_password) == 0) {
				$this->green_money_setLastError("Client ID and API Password must be filled out and valid!");
				return false;
			}

			try {
				$result = $this->callGreenAPI("TestAuthentication", array("x_delim_data" => "", "x_delim_char" => ","), "eTest.asmx");
				$this->green_money_setLastError($result->ResultDescription);
				return ($result && $result->Result == "0");
			} catch (\Exception $e) {
				return false;
			}
		}

		/**
		 * A function to determine whether the current Green client can use the tokenization widget
		 *
		 * @return boolean True if they can use the widget. False if not.
		 */
		public function can_widget()
		{
			try {
				if ($this->client_id == false || $this->client_password == false) {
					return false;
				}
				$response = $this->callGreenAPI("WidgetCapable", array("x_delim_data" => ""), "WooCommerce.asmx");

				return ($response[0] == "true");
			} catch (\Exception $e) {
				$this->log($e->getMessage());
				return false;
			}
		}

		/**
		 * A function to determine whether the current Green client must use the tokenization widget
		 *
		 * @return boolean True if they must use the widget. False if not.
		 */
		public function need_widget()
		{
			try {
				if ($this->client_id == false || $this->client_password == false) {
					return false;
				}
				$response = $this->callGreenAPI("WidgetRequired", array("x_delim_data" => ""), "FTFTokenizer.asmx");
				return ($response[0] === "true" || $response[0] === true);
			} catch (\Exception $e) {
				$this->log($e->getMessage());
				return false;
			}
		}

		/**
		 * Will call to Green to register the current store's WooCommerce REST API credentials
		 *
		 * @return boolean True if the store was/is saved, false if some other error occurred.
		 */
		public function register_store($storeURL)
		{
			try {
				// Normalize the input URL
				$storeURL = trim($storeURL);
				
				// If empty, use the gateway's configured URL (which should already be from settings)
				// This ensures we always use the GreenPay settings URL instead of falling back to get_site_url()
				if (empty($storeURL)) {
					$storeURL = $this->useStoreURL;
				}
				
				// The RegisterStore webmethod does UnTrailingSlashIt, so send without trailing slash
				$storeURL = untrailingslashit($storeURL);
				
				$this->log(sprintf(
					__('GreenPay: Registering store with URL: %s', 'woocommerce-gateway-green-money'),
					$storeURL
				));

				$data = array(
					"Store" => $storeURL,
					"RESTConsumerKey" => $this->rest_client_id,
					"RESTConsumerSecret" => $this->rest_client_secret
				);
				$response = $this->callGreenAPI("RegisterStore", $data, "WooCommerce.asmx");
				$this->green_money_setLastError($response->Result->ResultDescription);
				return ($response->Result->Result == 0);
			} catch (\Exception $e) {
				$this->log($e->getMessage());
				return false;
			}
		}

		/**
		 * Returns the merchant's Tokenization service Merchant ID
		 * @return string|bool	False on a failure or the authenticated merchant ID
		 */
		public function widget_mid()
		{
			try {
				$response = $this->callGreenAPI("TokenizerMID", array(), "FTFTokenizer.asmx");
				return $response->MerchantID;
			} catch (\Exception $e) {
				$this->log($e->getMessage());
				return false;
			}
		}

		/**
		 * Function will make an API call to our API that will register the session in our server
		 *
		 * @return boolean True if they can use the widget. False if not.
		 */
		public function start_session($sessionId)
		{
			try {
				//We don't need to use the response here because either we'll get a 500 error or we'll get a True/good
				$this->callGreenAPI("StartSession", array("s" => $sessionId, "c" => $this->client_id), "FTFTokenizer.asmx");
				return true;
			} catch (\Exception $e) {
				$this->log($e->getMessage());
				return false;
			}
		}

		/**
		 * Internal helper function to determine whether or not a routing number appears to be validate
		 *
		 * @param string $routing_number	The string version of the routing number to validate
		 * @param string $error 			A reference to a string which will contain the error if it returns false
		 *
		 * @return bool Whether the routing number validates as either a US or a CA routing number
		 */
		private function routing_number_validate($routing_number, &$error)
		{
			if (strlen($routing_number) !== 9) {
				$error = "Must be 9 digits.";
				return false;
			}

			if (ctype_digit($routing_number)) {
				//It's all numeric, so let's try to make sure it fits the US format
				if ($routing_number === "000000000" || $routing_number === "642260020") return true;
				if ((int)$routing_number > 370000000) {
					$error = "Doesn't match valid routing number format defined by the ABA.";
					return false;
				}

				$digits = array();
				foreach (str_split($routing_number) as $key => $char) {
					$digits[] = (int)$char;
				}

				$chk = ((7 * ($digits[0] + $digits[3] + $digits[6])) + (3 * ($digits[1] + $digits[4] + $digits[7])) + (9 * ($digits[2] + $digits[5]))) % 10;
				if (strcasecmp($chk, $digits[8]) !== 0) {
					$error = "Doesn't match valid routing number format defined by the ABA.";
					return false;
				}
				return true;
			} else {
				//It could still be a Canadian routing number
				$split = explode("-", $routing_number);
				if (count($split) !== 2) {
					$error = "Doesn't match valid routing number format for Canada.";
					return false;
				}

				if (!((strlen($split[0]) === 5) && (strlen($split[1]) === 3))) {
					$error = "Doesn't match valid routing number format for Canada.";
					return false;
				}
				return true;
			}
		}
	} // END class WC_Gateway_Green_Money
} // END if(!class_exists('WC_Gateway_Green_Money'))
