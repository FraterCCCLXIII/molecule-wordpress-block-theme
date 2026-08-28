<?php
/**
 * Customer email: Zelle payment received (order marked paid).
 *
 * @package wc-zelle
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Email_Zelle_Payment_Received', false ) ) :

	/**
	 * Sent when a Zelle order completes payment (webhook or manual).
	 */
	class WC_Email_Zelle_Payment_Received extends WC_Email {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'zelle_payment_received';
			$this->customer_email = true;
			$this->title          = __( 'Zelle payment received', WCZELLE_PLUGIN_TEXT_DOMAIN );
			$this->description    = __( 'Sent to the customer when their Zelle payment is confirmed and the order is marked paid.', WCZELLE_PLUGIN_TEXT_DOMAIN );
			$this->email_group    = 'order-updates';
			$this->template_html  = 'emails/zelle-payment-received.php';
			$this->template_plain = 'emails/plain/zelle-payment-received.php';
			$this->placeholders   = array(
				'{order_date}'   => '',
				'{order_number}' => '',
			);

			parent::__construct();

			add_action( 'woocommerce_payment_complete', array( $this, 'maybe_send' ), 50, 1 );
		}

		/**
		 * @return WC_Payment_Gateway|null
		 */
		protected function get_zelle_gateway() {
			if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
				return null;
			}
			$gateways = WC()->payment_gateways()->payment_gateways();
			$gw       = $gateways['zelle'] ?? null;
			return ( $gw && isset( $gw->id ) && 'zelle' === $gw->id ) ? $gw : null;
		}

		/**
		 * Gateway checkbox controls sending (not WooCommerce email settings).
		 *
		 * @return bool
		 */
		public function is_enabled() {
			$gw = $this->get_zelle_gateway();
			if ( ! $gw ) {
				return false;
			}
			$on = 'yes' === $gw->get_option( 'zelle_payment_received_email', 'no' );
			return (bool) apply_filters( 'woocommerce_email_enabled_' . $this->id, $on, $this->object, $this );
		}

		/**
		 * @return string
		 */
		public function get_default_subject() {
			return __( 'We received your Zelle payment — order {order_number}', WCZELLE_PLUGIN_TEXT_DOMAIN );
		}

		/**
		 * @return string
		 */
		public function get_default_heading() {
			return __( 'Your payment was received', WCZELLE_PLUGIN_TEXT_DOMAIN );
		}

		/**
		 * Subject from gateway settings.
		 *
		 * @return string
		 */
		public function get_subject() {
			$gw  = $this->get_zelle_gateway();
			$raw = $gw ? (string) $gw->get_option( 'zelle_payment_received_email_subject', '' ) : '';
			if ( $raw === '' ) {
				$raw = $this->get_default_subject();
			}
			$subject = $this->format_string( $raw );
			return apply_filters( 'woocommerce_email_subject_' . $this->id, $subject, $this->object, $this );
		}

		/**
		 * Heading from gateway settings.
		 *
		 * @return string
		 */
		public function get_heading() {
			$gw  = $this->get_zelle_gateway();
			$raw = $gw ? (string) $gw->get_option( 'zelle_payment_received_email_heading', '' ) : '';
			if ( $raw === '' ) {
				$raw = $this->get_default_heading();
			}
			$heading = $this->format_string( $raw );
			return apply_filters( 'woocommerce_email_heading_' . $this->id, $heading, $this->object, $this );
		}

		/**
		 * No separate WC email settings — avoid reading empty options.
		 *
		 * @return string
		 */
		public function get_additional_content() {
			return apply_filters( 'woocommerce_email_additional_content_' . $this->id, '', $this->object, $this );
		}

		/**
		 * @param int $order_id Order ID.
		 * @return void
		 */
		public function maybe_send( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order instanceof WC_Order ) {
				return;
			}
			if ( $order->get_payment_method() !== 'zelle' ) {
				return;
			}
			if ( 'yes' === $order->get_meta( '_wc_zelle_payment_received_email_sent' ) ) {
				return;
			}
			if ( ! apply_filters( 'wc_zelle_send_payment_received_email', true, $order ) ) {
				return;
			}
			if ( ! $this->is_enabled() ) {
				return;
			}

			$this->setup_locale();

			$this->object                         = $order;
			$this->recipient                      = $order->get_billing_email();
			$this->placeholders['{order_date}']   = wc_format_datetime( $order->get_date_created() );
			$this->placeholders['{order_number}'] = $order->get_order_number();

			if ( $this->get_recipient() ) {
				$sent = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
				if ( $sent ) {
					$order->update_meta_data( '_wc_zelle_payment_received_email_sent', 'yes' );
					$order->save();
				}
			}

			$this->restore_locale();
		}

		/**
		 * HTML body.
		 *
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html(
				$this->template_html,
				array(
					'order'              => $this->object,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => false,
					'plain_text'         => false,
					'email'              => $this,
				),
				'',
				WCZELLE_PLUGIN_DIR . 'templates/'
			);
		}

		/**
		 * Plain body.
		 *
		 * @return string
		 */
		public function get_content_plain() {
			return wc_get_template_html(
				$this->template_plain,
				array(
					'order'              => $this->object,
					'email_heading'      => $this->get_heading(),
					'additional_content' => $this->get_additional_content(),
					'sent_to_admin'      => false,
					'plain_text'         => true,
					'email'              => $this,
				),
				'',
				WCZELLE_PLUGIN_DIR . 'templates/'
			);
		}
	}

endif;
