<?php
/**
 * Hourly check: cancel unpaid Zelle orders past the configured deadline.
 *
 * @package wc-zelle
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Zelle_Unpaid_Cancellation class.
 */
class WC_Zelle_Unpaid_Cancellation {

	const CRON_HOOK = 'wc_zelle_cancel_unpaid_orders';

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_schedule_cron' ), 20 );
		add_action( self::CRON_HOOK, array( __CLASS__, 'process' ) );
	}

	/**
	 * Clear scheduled event (e.g. on plugin deactivation).
	 */
	public static function clear_scheduled_hook() {
		$ts = wp_next_scheduled( self::CRON_HOOK );
		if ( $ts ) {
			wp_unschedule_event( $ts, self::CRON_HOOK );
		}
	}

	/**
	 * Ensure hourly cron exists.
	 */
	public static function maybe_schedule_cron() {
		if ( wp_next_scheduled( self::CRON_HOOK ) ) {
			return;
		}
		wp_schedule_event( time(), 'hourly', self::CRON_HOOK );
	}

	/**
	 * Find and cancel eligible orders.
	 */
	public static function process() {
		if ( ! function_exists( 'wc_get_orders' ) || ! function_exists( 'WC' ) ) {
			return;
		}

		$gateways = WC()->payment_gateways();
		if ( ! $gateways || empty( $gateways->payment_gateways['zelle'] ) ) {
			return;
		}

		/** @var WC_Zelle_Gateway $gateway */
		$gateway = $gateways->payment_gateways['zelle'];
		if ( 'yes' !== $gateway->enabled || 'yes' !== $gateway->get_option( 'zelle_cancel_unpaid' ) ) {
			return;
		}

		$hours = method_exists( $gateway, 'wc_zelle_get_payment_deadline_hours' )
			? $gateway->wc_zelle_get_payment_deadline_hours()
			: 24;

		$cutoff_ts = time() - ( $hours * HOUR_IN_SECONDS );

		$statuses = apply_filters( 'wc_zelle_cancel_unpaid_order_statuses', array( 'pending', 'on-hold' ) );

		$per_run = (int) apply_filters( 'wc_zelle_cancel_unpaid_batch_size', 100 );
		if ( $per_run < 1 ) {
			$per_run = 100;
		}

		$orders = wc_get_orders(
			array(
				'limit'          => $per_run,
				'payment_method' => 'zelle',
				'status'         => $statuses,
				'orderby'        => 'date',
				'order'          => 'ASC',
				'return'         => 'objects',
			)
		);

		foreach ( $orders as $order ) {
			if ( ! is_a( $order, 'WC_Order' ) ) {
				continue;
			}
			if ( $order->is_paid() ) {
				continue;
			}

			$created = $order->get_date_created();
			if ( ! $created || $created->getTimestamp() > $cutoff_ts ) {
				continue;
			}

			$note = sprintf(
				/* translators: %d: number of hours */
				__( 'Order cancelled automatically: Zelle payment not received within %d hours.', WCZELLE_PLUGIN_TEXT_DOMAIN ),
				$hours
			);

			/**
			 * Fires before an unpaid Zelle order is auto-cancelled.
			 *
			 * @param WC_Order $order Order.
			 * @param int      $hours Configured hours.
			 */
			do_action( 'wc_zelle_before_cancel_unpaid_order', $order, $hours );

			$order->update_status( 'cancelled', $note );

			/**
			 * Fires after an unpaid Zelle order was auto-cancelled.
			 *
			 * @param WC_Order $order Order.
			 * @param int      $hours Configured hours.
			 */
			do_action( 'wc_zelle_after_cancel_unpaid_order', $order, $hours );
		}
	}
}
