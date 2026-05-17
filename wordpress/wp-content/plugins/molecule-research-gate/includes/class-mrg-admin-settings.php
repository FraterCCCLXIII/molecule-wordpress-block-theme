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
	 * Set once legacy proof trio has been rewritten to updated defaults (avoids overwriting custom copy).
	 */
	private const OPTION_MIGRATE_PROOF_LINES = 'mrg_migrated_proof_lines_202602';

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
			'proof_line_1'                => __( '99%+ Purity', 'molecule-research-gate' ),
			'proof_line_2'                => __( 'Third-Party Verified', 'molecule-research-gate' ),
			'proof_line_3'                => __( 'Research Use Only', 'molecule-research-gate' ),
			'gate_shop'               => 1,
			'gate_product'            => 1,
			'gate_product_category'   => 1,
			'gate_product_tag'        => 1,
			'gate_cart'               => 0,
			'gate_checkout'           => 0,
			'brevo_newsletter_optin_enabled' => 0,
			'brevo_newsletter_list_id'       => '',
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
	 * One-time rewrite of legacy proof bullets in stored options when they match the shipped defaults from v1 copy.
	 *
	 * Front-end localized `brand.proof*` reads from the database option, so changing get_defaults()
	 * alone does not affect existing sites until settings are saved again.
	 */
	public static function maybe_migrate_proof_lines(): void {
		if ( '' !== get_option( self::OPTION_MIGRATE_PROOF_LINES, '' ) ) {
			return;
		}

		$stored = get_option( MRG_OPTION_KEY );
		if ( ! is_array( $stored ) ) {
			update_option( self::OPTION_MIGRATE_PROOF_LINES, '1', false );
			return;
		}

		$p1 = isset( $stored['proof_line_1'] ) ? (string) $stored['proof_line_1'] : '';
		$p2 = isset( $stored['proof_line_2'] ) ? (string) $stored['proof_line_2'] : '';
		$p3 = isset( $stored['proof_line_3'] ) ? (string) $stored['proof_line_3'] : '';

		$legacy_three = (
			$p1 === 'USA sourced'
			&& $p2 === 'Third-party COAs'
			&& $p3 === 'Research use only'
		);

		if ( $legacy_three ) {
			$d = self::get_defaults();
			$stored['proof_line_1'] = $d['proof_line_1'];
			$stored['proof_line_2'] = $d['proof_line_2'];
			$stored['proof_line_3'] = $d['proof_line_3'];
			update_option( MRG_OPTION_KEY, $stored, false );
		}

		update_option( self::OPTION_MIGRATE_PROOF_LINES, '1', false );
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

		wp_enqueue_style(
			'mrg-admin-guide',
			false,
			array(),
			MRG_VERSION
		);
		wp_add_inline_style(
			'mrg-admin-guide',
			'.mrg-admin-guide{margin-bottom:24px;max-width:920px}' .
			'.mrg-admin-guide .title{margin-top:0}' .
			'.mrg-admin-guide ul{margin:8px 0 0 18px}' .
			'.mrg-admin-guide li{margin:6px 0}'
		);

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

		$out['brevo_newsletter_optin_enabled'] = ! empty( $out['brevo_newsletter_optin_enabled'] ) ? 1 : 0;
		$list_raw                               = isset( $out['brevo_newsletter_list_id'] ) ? sanitize_text_field( (string) $out['brevo_newsletter_list_id'] ) : '';
		$list_raw                               = preg_replace( '/[^\d\s,]/', '', $list_raw );
		$out['brevo_newsletter_list_id']       = trim( (string) $list_raw );

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
			<?php $this->render_admin_guide(); ?>
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
						<th scope="row"><?php esc_html_e( 'Brevo newsletter opt-in (verified step)', 'molecule-research-gate' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( MRG_OPTION_KEY ); ?>[brevo_newsletter_optin_enabled]" value="1" <?php checked( $values['brevo_newsletter_optin_enabled'] ); ?> />
								<?php esc_html_e( 'Show an optional marketing checkbox on the final gate step after research verification.', 'molecule-research-gate' ); ?>
							</label>
							<p class="description">
								<?php
								echo wp_kses_post(
									sprintf(
										/* translators: %s: WordPress plugin directory URL */
										__( 'Requires the <a href="%s" target="_blank" rel="noopener noreferrer">Brevo</a> plugin (slug %s), connected API key, and list IDs below.', 'molecule-research-gate' ),
										esc_url( 'https://wordpress.org/plugins/mailin/' ),
										'<code>mailin</code>'
									)
								);
								?>
							</p>
							<p>
								<label for="mrg_brevo_lists"><?php esc_html_e( 'Brevo list ID(s)', 'molecule-research-gate' ); ?></label><br />
								<input name="<?php echo esc_attr( MRG_OPTION_KEY ); ?>[brevo_newsletter_list_id]" type="text" id="mrg_brevo_lists" value="<?php echo esc_attr( $values['brevo_newsletter_list_id'] ); ?>" class="regular-text" placeholder="12, 34" />
							</p>
							<p class="description"><?php esc_html_e( 'Numeric list IDs from your Brevo account (comma-separated). Contacts are subscribed with simple opt-in when the customer checks the box before continuing.', 'molecule-research-gate' ); ?></p>
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

	/**
	 * Instructions shown above the settings form (Settings → Molecule Research Gate).
	 */
	private function render_admin_guide(): void {
		$users_url = admin_url( 'users.php' );
		$brevo_url = 'https://wordpress.org/plugins/mailin/';
		?>
		<div class="card mrg-admin-guide">
			<h2 class="title"><?php esc_html_e( 'How this plugin works', 'molecule-research-gate' ); ?></h2>
			<ul>
				<li>
					<?php esc_html_e( 'Guests hitting gated catalog URLs see a fullscreen modal: they must acknowledge Research Use Only terms, then sign in or register via WooCommerce My Account.', 'molecule-research-gate' ); ?>
				</li>
				<li>
					<?php esc_html_e( 'After login, customers who have not submitted the research profile see a second step (organization type, research field, optional lab name and role). Submitting it unlocks the catalog.', 'molecule-research-gate' ); ?>
				</li>
				<li>
					<?php esc_html_e( 'Use \'Gate these views\' below to choose which storefront URLs require an account (shop, single product, categories, tags, cart, checkout).', 'molecule-research-gate' ); ?>
				</li>
				<li>
					<?php esc_html_e( 'Terms URL, indemnity URL, support email, logo, brand panel image, and proof-line copy control modal content and styling.', 'molecule-research-gate' ); ?>
				</li>
				<li>
					<?php esc_html_e( 'Welcome coupon code is shown on the final \'verified\' step; cart buttons can include a query argument so WooCommerce applies that coupon.', 'molecule-research-gate' ); ?>
				</li>
				<li>
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: %s: Users admin screen URL */
							__( 'Saved profile answers are stored as user meta. Review them under <strong>Users → All Users</strong> → edit a user → scroll to <strong>Molecule research gate</strong>. (<a href="%s">Open Users</a>)', 'molecule-research-gate' ),
							esc_url( $users_url )
						)
					);
					?>
				</li>
				<li>
					<?php
					echo wp_kses_post(
						sprintf(
							/* translators: 1: opening anchor tag to Brevo plugin, 2: closing anchor tag */
							__( 'Optional marketing checkbox on the verified step: enable below and enter Brevo list IDs. Requires the %1$sBrevo (mailin)%2$s plugin with a valid API key. Subscriptions use Brevo \'simple\' opt-in when the customer checks the box before leaving the modal.', 'molecule-research-gate' ),
							'<a href="' . esc_url( $brevo_url ) . '" target="_blank" rel="noopener noreferrer">',
							'</a>'
						)
					);
					?>
				</li>
			</ul>
			<p class="description">
				<?php esc_html_e( 'Tip: Proof lines and other values below are stored in the database; deploying new plugin defaults does not overwrite options already saved on this site.', 'molecule-research-gate' ); ?>
			</p>
		</div>
		<?php
	}
}
