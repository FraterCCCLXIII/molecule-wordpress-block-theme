<?php
/**
 * WooCommerce admin order screen: Zelle instructions view + resend.
 *
 * @package wc-zelle
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WC_Zelle_Admin_Order
 */
class WC_Zelle_Admin_Order {

	const SCRIPT_HANDLE = 'wc-zelle-admin-order';
	const STYLE_HANDLE  = 'wc-zelle-admin-order';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( __CLASS__, 'render_order_actions' ), 20, 1 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_footer', array( __CLASS__, 'render_modals' ) );
		add_action( 'wp_ajax_wc_zelle_admin_resend_instructions', array( __CLASS__, 'ajax_resend_instructions' ) );
	}

	/**
	 * @return WC_Zelle_Gateway|null
	 */
	private static function get_gateway() {
		if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
			return null;
		}

		$gateways = WC()->payment_gateways()->payment_gateways();

		return isset( $gateways['zelle'] ) && is_object( $gateways['zelle'] ) ? $gateways['zelle'] : null;
	}

	/**
	 * @return bool
	 */
	private static function is_order_edit_screen() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen ) {
			return false;
		}

		if ( 'shop_order' === $screen->id ) {
			return true;
		}

		if ( 'woocommerce_page_wc-orders' === $screen->id ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return isset( $_GET['action'] ) && 'edit' === sanitize_key( wp_unslash( $_GET['action'] ) );
		}

		return false;
	}

	/**
	 * @return WC_Order|null
	 */
	private static function get_current_admin_order() {
		global $theorder, $post;

		if ( $theorder instanceof WC_Order ) {
			return $theorder;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['id'] ) ) {
			$order = wc_get_order( absint( wp_unslash( $_GET['id'] ) ) );
			if ( $order instanceof WC_Order ) {
				return $order;
			}
		}

		if ( $post && 'shop_order' === $post->post_type ) {
			$order = wc_get_order( (int) $post->ID );
			if ( $order instanceof WC_Order ) {
				return $order;
			}
		}

		return null;
	}

	/**
	 * @param WC_Order $order Order.
	 * @return bool
	 */
	private static function is_zelle_order( WC_Order $order ) {
		return 'zelle' === $order->get_payment_method();
	}

	/**
	 * @param WC_Order $order Order.
	 * @return void
	 */
	public static function render_order_actions( $order ) {
		if ( ! $order instanceof WC_Order || ! self::is_zelle_order( $order ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_shop_orders' ) && ! current_user_can( 'edit_shop_order', $order->get_id() ) ) {
			return;
		}

		?>
		<div class="wc-zelle-admin-order-actions">
			<h3><?php echo esc_html__( 'Zelle payment', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?></h3>
			<p class="wc-zelle-admin-order-actions__buttons">
				<button type="button" class="button" id="wc-zelle-admin-view-instructions">
					<?php echo esc_html__( 'View payment instructions', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?>
				</button>
				<button type="button" class="button" id="wc-zelle-admin-resend-instructions">
					<?php echo esc_html__( 'Resend instructions', WCZELLE_PLUGIN_TEXT_DOMAIN ); ?>
				</button>
			</p>
		</div>
		<?php
	}

	/**
	 * @param string $hook_suffix Admin hook suffix.
	 * @return void
	 */
	public static function enqueue_assets( $hook_suffix ) {
		unset( $hook_suffix );

		if ( ! self::is_order_edit_screen() ) {
			return;
		}

		$order = self::get_current_admin_order();
		if ( ! $order instanceof WC_Order || ! self::is_zelle_order( $order ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_shop_orders' ) && ! current_user_can( 'edit_shop_order', $order->get_id() ) ) {
			return;
		}

		$ver = defined( 'WCZELLE_PLUGIN_VERSION' ) ? WCZELLE_PLUGIN_VERSION : '1';

		wp_enqueue_style(
			'wc-zelle-thankyou',
			WCZELLE_PLUGIN_DIR_URL . 'assets/css/thankyou.css',
			array(),
			$ver
		);
		wp_enqueue_style(
			self::STYLE_HANDLE,
			WCZELLE_PLUGIN_DIR_URL . 'assets/css/admin-order.css',
			array( 'wc-zelle-thankyou' ),
			$ver
		);
		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			WCZELLE_PLUGIN_DIR_URL . 'assets/js/admin-order.js',
			array( 'jquery' ),
			$ver,
			true
		);

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'wcZelleAdminOrder',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wc_zelle_admin_order_' . $order->get_id() ),
				'orderId' => $order->get_id(),
				'i18n'    => array(
					'copied'           => __( 'Copied', WCZELLE_PLUGIN_TEXT_DOMAIN ),
					'copyFailed'       => __( 'Could not copy', WCZELLE_PLUGIN_TEXT_DOMAIN ),
					'sending'          => __( 'Sending…', WCZELLE_PLUGIN_TEXT_DOMAIN ),
					'sendInstructions' => __( 'Send instructions', WCZELLE_PLUGIN_TEXT_DOMAIN ),
					'sendSuccess'      => __( 'Payment instructions sent.', WCZELLE_PLUGIN_TEXT_DOMAIN ),
					'sendFailed'       => __( 'Could not send instructions. Please try again.', WCZELLE_PLUGIN_TEXT_DOMAIN ),
					'invalidEmail'     => __( 'Please enter a valid email address.', WCZELLE_PLUGIN_TEXT_DOMAIN ),
				),
			)
		);
	}

	/**
	 * @return void
	 */
	public static function render_modals() {
		if ( ! self::is_order_edit_screen() ) {
			return;
		}

		$order = self::get_current_admin_order();
		if ( ! $order instanceof WC_Order || ! self::is_zelle_order( $order ) ) {
			return;
		}

		$gateway = self::get_gateway();
		if ( ! $gateway ) {
			return;
		}

		if ( ! current_user_can( 'edit_shop_orders' ) && ! current_user_can( 'edit_shop_order', $order->get_id() ) ) {
			return;
		}

		require WCZELLE_PLUGIN_DIR . 'includes/admin/views/order-instructions-modal.php';
		require WCZELLE_PLUGIN_DIR . 'includes/admin/views/order-resend-modal.php';
	}

	/**
	 * AJAX: resend Zelle payment instructions email.
	 *
	 * @return void
	 */
	public static function ajax_resend_instructions() {
		$order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;
		$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		if ( ! $order_id ) {
			wp_send_json_error(
				array( 'message' => __( 'Missing order ID.', WCZELLE_PLUGIN_TEXT_DOMAIN ) ),
				400
			);
		}

		check_ajax_referer( 'wc_zelle_admin_order_' . $order_id, 'nonce' );

		if ( ! current_user_can( 'edit_shop_orders' ) && ! current_user_can( 'edit_shop_order', $order_id ) ) {
			wp_send_json_error(
				array( 'message' => __( 'You do not have permission to resend instructions for this order.', WCZELLE_PLUGIN_TEXT_DOMAIN ) ),
				403
			);
		}

		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			wp_send_json_error(
				array( 'message' => __( 'Order not found.', WCZELLE_PLUGIN_TEXT_DOMAIN ) ),
				404
			);
		}

		if ( ! self::is_zelle_order( $order ) ) {
			wp_send_json_error(
				array( 'message' => __( 'This order was not paid with Zelle.', WCZELLE_PLUGIN_TEXT_DOMAIN ) ),
				400
			);
		}

		if ( ! function_exists( 'wc_zelle_send_payment_instructions_email' ) ) {
			require_once WCZELLE_PLUGIN_DIR . 'includes/wc-zelle-instructions-email.php';
		}

		$result = wc_zelle_send_payment_instructions_email( $order, $email );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array( 'message' => $result->get_error_message() ),
				400
			);
		}

		wp_send_json_success(
			array(
				'message' => __( 'Payment instructions sent.', WCZELLE_PLUGIN_TEXT_DOMAIN ),
				'email'   => $email,
			)
		);
	}
}
