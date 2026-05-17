<?php
/**
 * Admin settings screen.
 *
 * @package MoleculeResearchGate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MRG_Admin_Settings
 */
class MRG_Admin_Settings {

	public const MENU_SLUG = 'molecule-research-gate';

	/**
	 * Default settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_defaults(): array {
		return array(
			'terms_url'               => home_url( '/terms/' ),
			'indemnity_url'           => home_url( '/indemnity-waiver/' ),
			'support_email'           => get_bloginfo( 'admin_email' ),
			'welcome_coupon_code'     => 'WELCOME15',
			'policy_version'          => '1.0',
			'logo_attachment_id'      => 0,
			'logo_url'                => '',
			'brand_panel_attachment_id' => 0,
			'brand_panel_image_url'   => '',
			'brand_eyebrow'               => __( 'Research access required', 'molecule-research-gate' ),
			'brand_image_overlay_opacity' => 85,
			'modal_backdrop_opacity'      => 55,
			'proof_line_1'                => __( 'USA sourced', 'molecule-research-gate' ),
			'proof_line_2'                => __( 'Third-party COAs', 'molecule-research-gate' ),
			'proof_line_3'                => __( 'Research use only', 'molecule-research-gate' ),
			'gate_shop'               => 1,
			'gate_product'            => 1,
			'gate_product_category'   => 1,
			'gate_product_tag'        => 1,
			'gate_cart'               => 0,
			'gate_checkout'           => 0,
		);
	}

	/**
	 * Merge with defaults.
	 *
	 * @param array<string, mixed>|false $stored Stored option.
	 * @return array<string, mixed>
	 */
	public static function parse( $stored ): array {
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		return array_merge( self::get_defaults(), $stored );
	}

	/**
	 * Register settings and menu.
	 */
	public function register(): void {
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Media library on settings screen.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		if ( 'settings_page_' . self::MENU_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_script(
			'mrg-admin-settings',
			MRG_PLUGIN_URL . 'assets/js/admin-settings.js',
			array( 'jquery' ),
			MRG_VERSION,
			true
		);

		wp_localize_script(
			'mrg-admin-settings',
			'mrgAdmin',
			array(
				'logoTitle'        => __( 'Choose logo image', 'molecule-research-gate' ),
				'brandPanelTitle'  => __( 'Choose brand panel image', 'molecule-research-gate' ),
				'useImage'         => __( 'Use this image', 'molecule-research-gate' ),
			)
		);
	}

	public function register_setting(): void {
		register_setting(
			'mrg_settings_group',
			MRG_OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::get_defaults(),
			)
		);
	}

	/**
	 * @param array<string, mixed> $input Raw.
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ): array {
		$defaults = self::get_defaults();
		$out      = is_array( $input ) ? $input : array();

		$url_keys = array( 'terms_url', 'indemnity_url' );
		foreach ( $url_keys as $key ) {
			$out[ $key ] = isset( $out[ $key ] ) ? esc_url_raw( (string) $out[ $key ] ) : $defaults[ $key ];
		}

		$clear_logo = isset( $out['clear_logo'] ) && '1' === (string) $out['clear_logo'];
		unset( $out['clear_logo'] );

		if ( $clear_logo ) {
			$out['logo_attachment_id'] = 0;
			$out['logo_url']             = '';
		} else {
			$logo_id = isset( $out['logo_attachment_id'] ) ? absint( $out['logo_attachment_id'] ) : 0;
			$out['logo_attachment_id'] = $logo_id;
			if ( $logo_id && wp_attachment_is_image( $logo_id ) ) {
				$logo_src = wp_get_attachment_image_url( $logo_id, 'full' );
				$out['logo_url'] = $logo_src ? esc_url_raw( $logo_src ) : '';
			} else {
				$prev         = get_option( MRG_OPTION_KEY, array() );
				$prev_id      = is_array( $prev ) ? absint( $prev['logo_attachment_id'] ?? 0 ) : 0;
				$prev_logourl = is_array( $prev ) && ! empty( $prev['logo_url'] ) ? (string) $prev['logo_url'] : '';

				if ( 0 === $logo_id && $prev_id > 0 ) {
					$out['logo_url'] = '';
				} elseif ( 0 === $logo_id && 0 === $prev_id && '' !== $prev_logourl ) {
					$out['logo_url'] = esc_url_raw( $prev_logourl );
				} else {
					$out['logo_url'] = '';
				}
			}
		}

		$clear_brand = isset( $out['clear_brand_panel'] ) && '1' === (string) $out['clear_brand_panel'];
		unset( $out['clear_brand_panel'] );

		if ( $clear_brand ) {
			$out['brand_panel_attachment_id'] = 0;
			$out['brand_panel_image_url']     = '';
		} else {
			$bp_id = isset( $out['brand_panel_attachment_id'] ) ? absint( $out['brand_panel_attachment_id'] ) : 0;
			$out['brand_panel_attachment_id'] = $bp_id;
			if ( $bp_id && wp_attachment_is_image( $bp_id ) ) {
				$bp_src = wp_get_attachment_image_url( $bp_id, 'full' );
				$out['brand_panel_image_url'] = $bp_src ? esc_url_raw( $bp_src ) : '';
			} else {
				$out['brand_panel_image_url'] = '';
			}
		}

		$out['support_email'] = isset( $out['support_email'] ) ? sanitize_email( (string) $out['support_email'] ) : $defaults['support_email'];

		$code = isset( $out['welcome_coupon_code'] ) ? sanitize_text_field( (string) $out['welcome_coupon_code'] ) : '';
		$out['welcome_coupon_code'] = function_exists( 'wc_format_coupon_code' ) ? wc_format_coupon_code( $code ) : $code;

		$out['policy_version'] = isset( $out['policy_version'] ) ? sanitize_text_field( (string) $out['policy_version'] ) : $defaults['policy_version'];

		$text_keys = array( 'brand_eyebrow', 'proof_line_1', 'proof_line_2', 'proof_line_3' );
		foreach ( $text_keys as $key ) {
			$out[ $key ] = isset( $out[ $key ] ) ? sanitize_text_field( (string) $out[ $key ] ) : $defaults[ $key ];
		}

		$out['brand_image_overlay_opacity'] = isset( $out['brand_image_overlay_opacity'] )
			? min( 100, max( 0, (int) $out['brand_image_overlay_opacity'] ) )
			: $defaults['brand_image_overlay_opacity'];
		$out['modal_backdrop_opacity']      = isset( $out['modal_backdrop_opacity'] )
			? min( 100, max( 0, (int) $out['modal_backdrop_opacity'] ) )
			: $defaults['modal_backdrop_opacity'];

		$gate_keys = array( 'gate_shop', 'gate_product', 'gate_product_category', 'gate_product_tag', 'gate_cart', 'gate_checkout' );
		foreach ( $gate_keys as $key ) {
			$out[ $key ] = ! empty( $out[ $key ] ) ? 1 : 0;
		}

		return array_merge( $defaults, $out );
	}

	public function add_menu(): void {
		add_options_page(
			__( 'Molecule Research Gate', 'molecule-research-gate' ),
			__( 'Molecule Research Gate', 'molecule-research-gate' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$values = self::parse( get_option( MRG_OPTION_KEY, array() ) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Molecule Research Gate', 'molecule-research-gate' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'mrg_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="mrg_terms_url"><?php esc_html_e( 'Terms URL', 'molecule-research-gate' ); ?></label></th>
						<td><input name="<?php echo esc_attr( MRG_OPTION_KEY ); ?>[terms_url]" type="url" id="mrg_terms_url" value="<?php echo esc_attr( $values['terms_url'] ); ?>" class="large-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="mrg_indemnity_url"><?php esc_html_e( 'Indemnity / waiver URL', 'molecule-research-gate' ); ?></label></th>
						<td><input name="<?php echo esc_attr( MRG_OPTION_KEY ); ?>[indemnity_url]" type="url" id="mrg_indemnity_url" value="<?php echo esc_attr( $values['indemnity_url'] ); ?>" class="large-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="mrg_support_email"><?php esc_html_e( 'Support email', 'molecule-research-gate' ); ?></label></th>
						<td><input name="<?php echo esc_attr( MRG_OPTION_KEY ); ?>[support_email]" type="email" id="mrg_support_email" value="<?php echo esc_attr( $values['support_email'] ); ?>" class="large-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="mrg_coupon"><?php esc_html_e( 'Welcome coupon code', 'molecule-research-gate' ); ?></label></th>
						<td>
							<input name="<?php echo esc_attr( MRG_OPTION_KEY ); ?>[welcome_coupon_code]" type="text" id="mrg_coupon" value="<?php echo esc_attr( $values['welcome_coupon_code'] ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Create this coupon in WooCommerce. Optional: cart links can append ?mrg_apply_coupon=CODE to auto-apply for logged-in customers.', 'molecule-research-gate' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mrg_policy"><?php esc_html_e( 'RUO policy version string', 'molecule-research-gate' ); ?></label></th>
						<td><input name="<?php echo esc_attr( MRG_OPTION_KEY ); ?>[policy_version]" type="text" id="mrg_policy" value="<?php echo esc_attr( $values['policy_version'] ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Logo (optional)', 'molecule-research-gate' ); ?></th>
						<td>
							<input type="hidden" name="<?php echo esc_attr( MRG_OPTION_KEY ); ?>[clear_logo]" id="mrg_clear_logo" value="0" />
							<input type="hidden" name="<?php echo esc_attr( MRG_OPTION_KEY ); ?>[logo_attachment_id]" id="mrg_logo_attachment_id" value="<?php echo esc_attr( (string) (int) ( $values['logo_attachment_id'] ?? 0 ) ); ?>" />
							<p>
								<button type="button" class="button" id="mrg_select_logo"><?php esc_html_e( 'Select from Media Library', 'molecule-research-gate' ); ?></button>
								<button type="button" class="button" id="mrg_remove_logo"><?php esc_html_e( 'Remove logo', 'molecule-research-gate' ); ?></button>
							</p>
							<div id="mrg_logo_preview" class="mrg-logo-preview" style="margin-top:8px;">
								<?php
								$logo_id  = isset( $values['logo_attachment_id'] ) ? absint( $values['logo_attachment_id'] ) : 0;
								$prev_url = '';
								if ( $logo_id && wp_attachment_is_image( $logo_id ) ) {
									$prev_url = wp_get_attachment_image_url( $logo_id, 'medium' );
								} elseif ( ! empty( $values['logo_url'] ) ) {
									$prev_url = $values['logo_url'];
								}
								if ( $prev_url ) {
									echo '<img src="' . esc_url( $prev_url ) . '" alt="" style="max-width:220px;height:auto;border-radius:4px;border:1px solid #c3c4c7;" />';
								}
								?>
							</div>
							<p class="description"><?php esc_html_e( 'Shown at the top of the gate modal. The full-size image URL is stored when you save.', 'molecule-research-gate' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Brand panel image (optional)', 'molecule-research-gate' ); ?></th>
						<td>
							<input type="hidden" name="<?php echo esc_attr( MRG_OPTION_KEY ); ?>[clear_brand_panel]" id="mrg_clear_brand_panel" value="0" />
							<input type="hidden" name="<?php echo esc_attr( MRG_OPTION_KEY ); ?>[brand_panel_attachment_id]" id="mrg_brand_panel_attachment_id" value="<?php echo esc_attr( (string) (int) ( $values['brand_panel_attachment_id'] ?? 0 ) ); ?>" />
							<p>
								<button type="button" class="button" id="mrg_select_brand_panel"><?php esc_html_e( 'Select from Media Library', 'molecule-research-gate' ); ?></button>
								<button type="button" class="button" id="mrg_remove_brand_panel"><?php esc_html_e( 'Remove image', 'molecule-research-gate' ); ?></button>
							</p>
							<div id="mrg_brand_panel_preview" class="mrg-brand-panel-preview" style="margin-top:8px;">
								<?php
								$bp_id = isset( $values['brand_panel_attachment_id'] ) ? absint( $values['brand_panel_attachment_id'] ) : 0;
								if ( $bp_id && wp_attachment_is_image( $bp_id ) ) {
									$bp_prev = wp_get_attachment_image_url( $bp_id, 'medium' );
									if ( $bp_prev ) {
										echo '<img src="' . esc_url( $bp_prev ) . '" alt="" style="max-width:280px;height:auto;border-radius:4px;border:1px solid #c3c4c7;" />';
									}
								}
								?>
							</div>
							<p class="description"><?php esc_html_e( 'Background for the left brand column (eyebrow and proof points) on every gate step. Use the overlay control below to keep text readable.', 'molecule-research-gate' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mrg_brand_overlay"><?php esc_html_e( 'Brand image overlay', 'molecule-research-gate' ); ?></label></th>
						<td>
							<input name="<?php echo esc_attr( MRG_OPTION_KEY ); ?>[brand_image_overlay_opacity]" type="number" id="mrg_brand_overlay" min="0" max="100" step="1" value="<?php echo esc_attr( (string) (int) $values['brand_image_overlay_opacity'] ); ?>" class="small-text" />
							<p class="description"><?php esc_html_e( 'Dark gradient strength over the brand image (0 = image only, 100 = strongest). Default 85.', 'molecule-research-gate' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="mrg_modal_backdrop"><?php esc_html_e( 'Modal backdrop dim', 'molecule-research-gate' ); ?></label></th>
						<td>
							<input name="<?php echo esc_attr( MRG_OPTION_KEY ); ?>[modal_backdrop_opacity]" type="number" id="mrg_modal_backdrop" min="0" max="100" step="1" value="<?php echo esc_attr( (string) (int) $values['modal_backdrop_opacity'] ); ?>" class="small-text" />
							<p class="description"><?php esc_html_e( 'How much the page behind the modal is darkened (0–100). Default 55.', 'molecule-research-gate' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Gate these views (guests redirected to login)', 'molecule-research-gate' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( MRG_OPTION_KEY ); ?>[gate_shop]" value="1" <?php checked( $values['gate_shop'] ); ?> /> <?php esc_html_e( 'Shop archive', 'molecule-research-gate' ); ?></label><br />
							<label><input type="checkbox" name="<?php echo esc_attr( MRG_OPTION_KEY ); ?>[gate_product]" value="1" <?php checked( $values['gate_product'] ); ?> /> <?php esc_html_e( 'Single product', 'molecule-research-gate' ); ?></label><br />
							<label><input type="checkbox" name="<?php echo esc_attr( MRG_OPTION_KEY ); ?>[gate_product_category]" value="1" <?php checked( $values['gate_product_category'] ); ?> /> <?php esc_html_e( 'Product categories', 'molecule-research-gate' ); ?></label><br />
							<label><input type="checkbox" name="<?php echo esc_attr( MRG_OPTION_KEY ); ?>[gate_product_tag]" value="1" <?php checked( $values['gate_product_tag'] ); ?> /> <?php esc_html_e( 'Product tags', 'molecule-research-gate' ); ?></label><br />
							<label><input type="checkbox" name="<?php echo esc_attr( MRG_OPTION_KEY ); ?>[gate_cart]" value="1" <?php checked( $values['gate_cart'] ); ?> /> <?php esc_html_e( 'Cart', 'molecule-research-gate' ); ?></label><br />
							<label><input type="checkbox" name="<?php echo esc_attr( MRG_OPTION_KEY ); ?>[gate_checkout]" value="1" <?php checked( $values['gate_checkout'] ); ?> /> <?php esc_html_e( 'Checkout', 'molecule-research-gate' ); ?></label>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
