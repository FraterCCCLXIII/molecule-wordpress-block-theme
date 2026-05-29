<?php
/**
 * Server-side enforcement of quantity-tier discounts in the cart.
 *
 * Discounts are threshold-based on the cart line quantity, so the correct price
 * applies regardless of how the item was added or later edited. This is the
 * source of truth; the front-end price display is cosmetic.
 *
 * @package MoleculeBundlePricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MBP_Discount
 */
class MBP_Discount {

	/**
	 * Register cart hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'apply_tier_discounts' ), 20 );
		add_filter( 'woocommerce_get_item_data', array( $this, 'render_cart_item_savings' ), 10, 2 );
	}

	/**
	 * Adjust each cart line's per-unit price to the applicable tier discount.
	 *
	 * @param WC_Cart $cart Cart instance.
	 * @return void
	 */
	public function apply_tier_discounts( $cart ) {
		if ( ! $cart instanceof WC_Cart ) {
			return;
		}

		// Safe to run on every recalculation because the base price is read fresh
		// from the product (not the possibly-modified cart item) on each pass.
		foreach ( $cart->get_cart() as $cart_item ) {
			if ( empty( $cart_item['data'] ) || ! $cart_item['data'] instanceof WC_Product ) {
				continue;
			}

			$product_id   = isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0;
			$variation_id = isset( $cart_item['variation_id'] ) ? (int) $cart_item['variation_id'] : 0;
			$quantity     = isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 0;

			$discount = $this->get_discount_percent( $product_id, $variation_id, $quantity );
			if ( $discount <= 0 ) {
				continue;
			}

			$base_price = $this->get_base_price( $product_id, $variation_id );
			if ( $base_price <= 0 ) {
				continue;
			}

			$discounted = $base_price * ( 1 - ( $discount / 100 ) );
			$cart_item['data']->set_price( $discounted );
		}
	}

	/**
	 * Resolve the discount percentage applicable to a cart line.
	 *
	 * @param int $product_id   Parent/simple product id.
	 * @param int $variation_id Variation id (0 for simple).
	 * @param int $quantity     Line quantity.
	 * @return float
	 */
	private function get_discount_percent( $product_id, $variation_id, $quantity ) {
		$tiers = MBP_Tiers::resolve( $product_id, $variation_id );
		if ( empty( $tiers ) ) {
			return 0.0;
		}

		$tier = MBP_Tiers::applicable_tier( $tiers, $quantity );
		if ( ! $tier ) {
			return 0.0;
		}

		return (float) $tier['discount_percent'];
	}

	/**
	 * Read the canonical (pre-discount) price from a fresh product instance to
	 * avoid compounding when this filter runs multiple times per request.
	 *
	 * @param int $product_id   Parent/simple product id.
	 * @param int $variation_id Variation id (0 for simple).
	 * @return float
	 */
	private function get_base_price( $product_id, $variation_id ) {
		$lookup_id = $variation_id ? $variation_id : $product_id;
		$product   = wc_get_product( $lookup_id );

		if ( ! $product instanceof WC_Product ) {
			return 0.0;
		}

		return (float) $product->get_price();
	}

	/**
	 * Show a "Save X%" note under the cart line item.
	 *
	 * @param array<int, array<string, mixed>> $item_data Existing item data rows.
	 * @param array<string, mixed>             $cart_item Cart item.
	 * @return array<int, array<string, mixed>>
	 */
	public function render_cart_item_savings( $item_data, $cart_item ) {
		$product_id   = isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0;
		$variation_id = isset( $cart_item['variation_id'] ) ? (int) $cart_item['variation_id'] : 0;
		$quantity     = isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 0;

		$discount = $this->get_discount_percent( $product_id, $variation_id, $quantity );
		if ( $discount <= 0 ) {
			return $item_data;
		}

		$item_data[] = array(
			'key'     => __( 'Bundle saving', 'molecule-bundle-pricing' ),
			'value'   => sprintf(
				/* translators: %s: discount percentage. */
				__( 'Save %s%%', 'molecule-bundle-pricing' ),
				wc_format_localized_decimal( $discount )
			),
			'display' => '',
		);

		return $item_data;
	}
}
