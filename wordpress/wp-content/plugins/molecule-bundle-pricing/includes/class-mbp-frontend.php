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

		// Remove the redundant single-product price block when bundle tiers display pricing.
		add_filter( 'render_block', array( $this, 'maybe_remove_single_product_price' ), 10, 2 );
	}

	/**
	 * Hide the core single-product Product Price block on products that show bundle tiers.
	 *
	 * Scoped to the single-product template's own price block (not loop/related/upsell
	 * price blocks) and only when the queried product actually resolves to tiers.
	 *
	 * @param string               $block_content Rendered block HTML.
	 * @param array<string, mixed> $block         Parsed block.
	 * @return string
	 */
	public function maybe_remove_single_product_price( $block_content, $block ) {
		if ( empty( $block['blockName'] ) || 'woocommerce/product-price' !== $block['blockName'] ) {
			return $block_content;
		}

		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return $block_content;
		}

		// Only the main single-product price block carries this attribute; loop prices do not.
		if ( empty( $block['attrs']['isDescendentOfSingleProductTemplate'] ) ) {
			return $block_content;
		}

		$product_id = get_queried_object_id();
		if ( ! $product_id ) {
			return $block_content;
		}

		$tiers = MBP_Tiers::resolve( (int) $product_id );
		if ( empty( $tiers ) ) {
			return $block_content;
		}

		return '';
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
