<?php
/**
 * Front-end rendering of the bundle tier selector on the single product page.
 *
 * Emits a semantic placeholder seeded with tier data; the JS layer builds the
 * interactive cards and drives WooCommerce's native quantity input.
 *
 * @package MoleculeBundlePricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MBP_Frontend
 */
class MBP_Frontend {

	/**
	 * Register front-end hooks.
	 *
	 * @return void
	 */
	public function register() {
		// Inside the add-to-cart form, above the quantity + button (both simple and variable templates).
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'render_selector' ), 5 );
	}

	/**
	 * Render the tier selector placeholder.
	 *
	 * @return void
	 */
	public function render_selector() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$product_id = (int) $product->get_id();
		$tiers      = MBP_Tiers::resolve( $product_id );
		if ( empty( $tiers ) ) {
			return;
		}

		$is_variable = $product->is_type( 'variable' );
		$config      = array(
			'productType' => $is_variable ? 'variable' : 'simple',
			'tiers'       => $this->prepare_tiers_for_js( $tiers ),
			'basePrice'   => $is_variable ? null : (float) wc_get_price_to_display( $product ),
			'variations'  => $is_variable ? $this->prepare_variations_for_js( $product ) : new stdClass(),
		);

		$json = wp_json_encode( $config );
		if ( false === $json ) {
			return;
		}
		?>
		<div
			class="molecule-bundle-tiers"
			data-molecule-bundle-tiers
			data-mbp-config="<?php echo esc_attr( $json ); ?>"
			role="radiogroup"
			aria-label="<?php esc_attr_e( 'Quantity options', 'molecule-bundle-pricing' ); ?>"
		>
			<div class="molecule-bundle-tiers__list"></div>
		</div>
		<?php
	}

	/**
	 * Normalize tiers into a compact, JS-friendly shape.
	 *
	 * @param array<int, array<string, mixed>> $tiers Tiers.
	 * @return array<int, array<string, mixed>>
	 */
	private function prepare_tiers_for_js( $tiers ) {
		$out = array();
		foreach ( $tiers as $tier ) {
			$out[] = array(
				'quantity'  => (int) $tier['quantity'],
				'discount'  => (float) $tier['discount_percent'],
				'label'     => (string) $tier['label'],
				'sublabel'  => (string) $tier['sublabel'],
				'badge'     => (string) $tier['badge'],
				'openEnded' => ! empty( $tier['open_ended'] ),
			);
		}

		return $out;
	}

	/**
	 * Build a variation_id => { price, tiers } map for variable products.
	 *
	 * @param WC_Product $product Variable product.
	 * @return array<int, array<string, mixed>>
	 */
	private function prepare_variations_for_js( $product ) {
		$map        = array();
		$product_id = (int) $product->get_id();

		foreach ( $product->get_children() as $variation_id ) {
			$variation_id = (int) $variation_id;
			$variation    = wc_get_product( $variation_id );
			if ( ! $variation instanceof WC_Product ) {
				continue;
			}

			$tiers = MBP_Tiers::resolve( $product_id, $variation_id );

			$map[ $variation_id ] = array(
				'price' => (float) wc_get_price_to_display( $variation ),
				'tiers' => $this->prepare_tiers_for_js( $tiers ),
			);
		}

		return $map;
	}
}
