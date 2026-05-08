<?php
/**
 * Manage affiliate metadata on WooCommerce coupons.
 *
 * @package AffiliateCouponTracker
 */

defined( 'ABSPATH' ) || exit;

class ACT_Coupon_Affiliate_Fields {
	const META_AFFILIATE_ID   = '_act_affiliate_id';
	const META_AFFILIATE_NAME = '_act_affiliate_name';
	const NONCE_ACTION        = 'act_save_coupon_affiliate_meta';
	const NONCE_NAME          = 'act_coupon_affiliate_meta_nonce';

	/**
	 * Register WordPress hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( 'woocommerce_coupon_options', array( $this, 'render_fields' ) );
		add_action( 'woocommerce_coupon_options_save', array( $this, 'save_fields' ), 10, 2 );

		add_filter( 'manage_edit-shop_coupon_columns', array( $this, 'add_coupon_columns' ) );
		add_action( 'manage_shop_coupon_posts_custom_column', array( $this, 'render_coupon_column' ), 10, 2 );
	}

	/**
	 * Render affiliate fields on coupon editor.
	 *
	 * @param int|WC_Coupon $coupon Coupon object or ID.
	 * @return void
	 */
	public function render_fields( $coupon ) {
		$coupon_id = is_a( $coupon, 'WC_Coupon' ) ? $coupon->get_id() : absint( $coupon );

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		echo '<div class="options_group">';

		$this->render_text_input(
			array(
				'id'          => self::META_AFFILIATE_ID,
				'label'       => __( 'Affiliate ID', 'affiliate-coupon-tracker' ),
				'placeholder' => __( 'AFF-123', 'affiliate-coupon-tracker' ),
				'description' => __( 'Internal affiliate identifier linked to this coupon.', 'affiliate-coupon-tracker' ),
				'value'       => (string) get_post_meta( $coupon_id, self::META_AFFILIATE_ID, true ),
			)
		);

		$this->render_text_input(
			array(
				'id'          => self::META_AFFILIATE_NAME,
				'label'       => __( 'Affiliate Name', 'affiliate-coupon-tracker' ),
				'placeholder' => __( 'Acme Partner', 'affiliate-coupon-tracker' ),
				'description' => __( 'Display name used in affiliate reports.', 'affiliate-coupon-tracker' ),
				'value'       => (string) get_post_meta( $coupon_id, self::META_AFFILIATE_NAME, true ),
			)
		);

		echo '</div>';
	}

	/**
	 * Save affiliate fields from coupon editor.
	 *
	 * @param int       $post_id Coupon post ID.
	 * @param WC_Coupon $coupon  Coupon object.
	 * @return void
	 */
	public function save_fields( $post_id, $coupon = null ) {
		unset( $coupon );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$nonce = isset( $_POST[ self::NONCE_NAME ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		$affiliate_id   = isset( $_POST[ self::META_AFFILIATE_ID ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::META_AFFILIATE_ID ] ) ) : '';
		$affiliate_name = isset( $_POST[ self::META_AFFILIATE_NAME ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::META_AFFILIATE_NAME ] ) ) : '';

		$this->update_coupon_meta( $post_id, self::META_AFFILIATE_ID, $affiliate_id );
		$this->update_coupon_meta( $post_id, self::META_AFFILIATE_NAME, $affiliate_name );
	}

	/**
	 * Add columns to coupon list table.
	 *
	 * @param array<string, string> $columns Existing columns.
	 * @return array<string, string>
	 */
	public function add_coupon_columns( $columns ) {
		$updated = array();

		foreach ( $columns as $key => $label ) {
			$updated[ $key ] = $label;

			if ( 'coupon_amount' === $key ) {
				$updated['act_affiliate_id']   = __( 'Affiliate ID', 'affiliate-coupon-tracker' );
				$updated['act_affiliate_name'] = __( 'Affiliate Name', 'affiliate-coupon-tracker' );
			}
		}

		return $updated;
	}

	/**
	 * Render custom coupon list table columns.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Coupon post ID.
	 * @return void
	 */
	public function render_coupon_column( $column, $post_id ) {
		if ( 'act_affiliate_id' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, self::META_AFFILIATE_ID, true ) );
			return;
		}

		if ( 'act_affiliate_name' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, self::META_AFFILIATE_NAME, true ) );
		}
	}

	/**
	 * Safely update or remove coupon meta value.
	 *
	 * @param int    $post_id Coupon ID.
	 * @param string $meta_key Meta key.
	 * @param string $value Sanitized value.
	 * @return void
	 */
	private function update_coupon_meta( $post_id, $meta_key, $value ) {
		if ( '' === $value ) {
			delete_post_meta( $post_id, $meta_key );
			return;
		}

		update_post_meta( $post_id, $meta_key, $value );
	}

	/**
	 * Render a WooCommerce admin text input field.
	 *
	 * @param array<string, string> $args Field arguments.
	 * @return void
	 */
	private function render_text_input( $args ) {
		if ( function_exists( 'woocommerce_wp_text_input' ) ) {
			woocommerce_wp_text_input( $args );
			return;
		}

		printf(
			'<p class="form-field %1$s_field"><label for="%1$s">%2$s</label><input type="text" class="short" name="%1$s" id="%1$s" value="%3$s" placeholder="%4$s" /><span class="description">%5$s</span></p>',
			esc_attr( $args['id'] ),
			esc_html( $args['label'] ),
			esc_attr( $args['value'] ),
			esc_attr( $args['placeholder'] ),
			esc_html( $args['description'] )
		);
	}
}
