<?php
/**
 * Per-product and per-variation admin fields (native WooCommerce product data).
 *
 * @package MoleculeBundlePricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MBP_Product_Admin
 */
class MBP_Product_Admin {

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public function register() {
		// Product-level: dedicated "Bundle Pricing" product data tab + panel.
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_product_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'render_product_panel' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_fields' ) );

		// Variation-level overrides.
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'render_variation_fields' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation_fields' ), 10, 2 );
	}

	/**
	 * Add the Bundle Pricing tab to the product data metabox.
	 *
	 * @param array<string, array<string, mixed>> $tabs Existing tabs.
	 * @return array<string, array<string, mixed>>
	 */
	public function add_product_tab( $tabs ) {
		$tabs['mbp_bundle_pricing'] = array(
			'label'    => __( 'Bundle Pricing', 'molecule-bundle-pricing' ),
			'target'   => 'mbp_bundle_pricing_data',
			'class'    => array(),
			'priority' => 65,
		);

		return $tabs;
	}

	/**
	 * Render the product-level panel.
	 *
	 * @return void
	 */
	public function render_product_panel() {
		global $post;

		$product_id = $post ? (int) $post->ID : 0;
		$enabled    = $product_id ? get_post_meta( $product_id, MBP_META_ENABLED, true ) : '';
		$tiers      = $product_id ? MBP_Tiers::get_meta_tiers( $product_id ) : array();
		?>
		<div id="mbp_bundle_pricing_data" class="panel woocommerce_options_panel mbp-product-panel">
			<div class="options_group">
				<p class="form-field">
					<label for="mbp_enabled"><?php esc_html_e( 'Bundle tiers', 'molecule-bundle-pricing' ); ?></label>
					<select id="mbp_enabled" name="<?php echo esc_attr( MBP_META_ENABLED ); ?>" class="select short">
						<option value="" <?php selected( '', $enabled ); ?>><?php esc_html_e( 'Use site default', 'molecule-bundle-pricing' ); ?></option>
						<option value="yes" <?php selected( 'yes', $enabled ); ?>><?php esc_html_e( 'Enabled', 'molecule-bundle-pricing' ); ?></option>
						<option value="no" <?php selected( 'no', $enabled ); ?>><?php esc_html_e( 'Disabled', 'molecule-bundle-pricing' ); ?></option>
					</select>
				</p>
			</div>
			<div class="options_group">
				<p class="form-field">
					<label><?php esc_html_e( 'Custom tiers (optional)', 'molecule-bundle-pricing' ); ?></label>
				</p>
				<div class="mbp-product-tier-wrap" style="padding:0 12px 12px;">
					<?php MBP_Admin_View::tier_rows( MBP_META_TIERS, $tiers ); ?>
					<p class="description">
						<?php esc_html_e( 'Leave all rows empty to inherit the site default tiers. Add rows here to override them for this product only.', 'molecule-bundle-pricing' ); ?>
					</p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save product-level fields.
	 *
	 * @param int $product_id Product id.
	 * @return void
	 */
	public function save_product_fields( $product_id ) {
		$product_id = absint( $product_id );
		if ( ! $product_id || ! current_user_can( 'edit_post', $product_id ) ) {
			return;
		}

		// Nonce: WooCommerce verifies its own product meta nonce before this hook fires.
		$enabled_raw = isset( $_POST[ MBP_META_ENABLED ] ) ? sanitize_key( wp_unslash( $_POST[ MBP_META_ENABLED ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$enabled     = in_array( $enabled_raw, array( 'yes', 'no' ), true ) ? $enabled_raw : '';

		if ( '' === $enabled ) {
			delete_post_meta( $product_id, MBP_META_ENABLED );
		} else {
			update_post_meta( $product_id, MBP_META_ENABLED, $enabled );
		}

		$raw_tiers = isset( $_POST[ MBP_META_TIERS ] ) ? wp_unslash( $_POST[ MBP_META_TIERS ] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized in MBP_Tiers.
		$tiers     = MBP_Tiers::sanitize_tiers( $raw_tiers );

		if ( empty( $tiers ) ) {
			delete_post_meta( $product_id, MBP_META_TIERS );
		} else {
			update_post_meta( $product_id, MBP_META_TIERS, $tiers );
		}
	}

	/**
	 * Render per-variation override fields.
	 *
	 * @param int     $loop           Variation loop index.
	 * @param array   $variation_data Variation data (unused).
	 * @param WP_Post $variation      Variation post object.
	 * @return void
	 */
	public function render_variation_fields( $loop, $variation_data, $variation ) {
		unset( $variation_data );

		$variation_id = $variation ? (int) $variation->ID : 0;
		$tiers        = $variation_id ? MBP_Tiers::get_meta_tiers( $variation_id ) : array();
		?>
		<div class="mbp-variation-tiers" style="flex:0 0 100%;margin-top:8px;">
			<p class="form-row form-row-full">
				<strong><?php esc_html_e( 'Bundle tier override', 'molecule-bundle-pricing' ); ?></strong>
				<br />
				<span class="description"><?php esc_html_e( 'Leave empty to inherit the parent product / site default tiers.', 'molecule-bundle-pricing' ); ?></span>
			</p>
			<?php MBP_Admin_View::tier_rows( '_mbp_var_tiers[' . (int) $loop . ']', $tiers, array( 'compact' => true ) ); ?>
		</div>
		<?php
	}

	/**
	 * Save per-variation override fields.
	 *
	 * @param int $variation_id Variation id.
	 * @param int $i            Loop index.
	 * @return void
	 */
	public function save_variation_fields( $variation_id, $i ) {
		$variation_id = absint( $variation_id );
		if ( ! $variation_id || ! current_user_can( 'edit_post', $variation_id ) ) {
			return;
		}

		$all_raw = isset( $_POST['_mbp_var_tiers'] ) && is_array( $_POST['_mbp_var_tiers'] ) ? wp_unslash( $_POST['_mbp_var_tiers'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized in MBP_Tiers.
		$raw     = isset( $all_raw[ $i ] ) ? $all_raw[ $i ] : array();
		$tiers   = MBP_Tiers::sanitize_tiers( $raw );

		if ( empty( $tiers ) ) {
			delete_post_meta( $variation_id, MBP_META_TIERS );
		} else {
			update_post_meta( $variation_id, MBP_META_TIERS, $tiers );
		}
	}
}
