<?php
/**
 * Global settings: default tiers and the "enable by default" flag.
 *
 * Stored as a single option (MBP_OPTION_KEY) and edited from a self-contained
 * admin page under the WooCommerce menu. No ACF dependency.
 *
 * @package MoleculeBundlePricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MBP_Settings
 */
class MBP_Settings {

	const NONCE_ACTION = 'mbp_save_settings';
	const NONCE_NAME   = 'mbp_settings_nonce';

	/**
	 * Default settings used on activation and as a fallback.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_defaults() {
		return array(
			'enable_by_default' => false,
			'tiers'             => array(
				array(
					'quantity'         => 1,
					'discount_percent' => 0.0,
					'label'            => __( 'Buy 1', 'molecule-bundle-pricing' ),
					'sublabel'         => __( 'Standard', 'molecule-bundle-pricing' ),
					'badge'            => '',
					'open_ended'       => false,
				),
				array(
					'quantity'         => 3,
					'discount_percent' => 5.0,
					'label'            => __( 'Buy 3', 'molecule-bundle-pricing' ),
					'sublabel'         => __( 'Save 5%', 'molecule-bundle-pricing' ),
					'badge'            => __( 'Popular', 'molecule-bundle-pricing' ),
					'open_ended'       => false,
				),
				array(
					'quantity'         => 6,
					'discount_percent' => 10.0,
					'label'            => __( 'Buy 6+', 'molecule-bundle-pricing' ),
					'sublabel'         => __( 'Save 10%', 'molecule-bundle-pricing' ),
					'badge'            => '',
					'open_ended'       => true,
				),
			),
		);
	}

	/**
	 * Parsed settings array (sanitized tiers, normalized flag).
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings() {
		$stored = get_option( MBP_OPTION_KEY, array() );
		$stored = is_array( $stored ) ? $stored : array();

		return array(
			'enable_by_default' => ! empty( $stored['enable_by_default'] ),
			'tiers'             => MBP_Tiers::sanitize_tiers( isset( $stored['tiers'] ) ? $stored['tiers'] : array() ),
		);
	}

	/**
	 * Global default tiers (falls back to packaged defaults if none configured).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_global_tiers() {
		$settings = self::get_settings();
		if ( ! empty( $settings['tiers'] ) ) {
			return $settings['tiers'];
		}

		return MBP_Tiers::sanitize_tiers( self::get_defaults()['tiers'] );
	}

	/**
	 * Whether products without an explicit per-product flag are bundle-enabled.
	 *
	 * @return bool
	 */
	public static function enabled_by_default() {
		$settings = self::get_settings();

		return ! empty( $settings['enable_by_default'] );
	}

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'maybe_save' ) );
	}

	/**
	 * Add the settings page under the WooCommerce menu.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Bundle Pricing', 'molecule-bundle-pricing' ),
			__( 'Bundle Pricing', 'molecule-bundle-pricing' ),
			'manage_woocommerce',
			'mbp-bundle-pricing',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Handle settings form submission.
	 *
	 * @return void
	 */
	public function maybe_save() {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		check_admin_referer( self::NONCE_ACTION, self::NONCE_NAME );

		$raw_tiers = isset( $_POST['mbp_settings']['tiers'] ) ? wp_unslash( $_POST['mbp_settings']['tiers'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized in MBP_Tiers.

		$settings = array(
			'enable_by_default' => ! empty( $_POST['mbp_settings']['enable_by_default'] ),
			'tiers'             => MBP_Tiers::sanitize_tiers( $raw_tiers ),
		);

		update_option( MBP_OPTION_KEY, $settings );

		add_settings_error( 'mbp_settings', 'mbp_saved', __( 'Bundle pricing settings saved.', 'molecule-bundle-pricing' ), 'updated' );
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$settings = self::get_settings();
		?>
		<div class="wrap mbp-settings">
			<h1><?php esc_html_e( 'Bundle Pricing', 'molecule-bundle-pricing' ); ?></h1>
			<?php settings_errors( 'mbp_settings' ); ?>
			<p class="description">
				<?php esc_html_e( 'These are the default tiers applied to bundle-enabled products. Individual products (and variations) can override them.', 'molecule-bundle-pricing' ); ?>
			</p>
			<form method="post" action="">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable for all products by default', 'molecule-bundle-pricing' ); ?></th>
						<td>
							<label>
								<input
									type="checkbox"
									name="mbp_settings[enable_by_default]"
									value="1"
									<?php checked( ! empty( $settings['enable_by_default'] ) ); ?>
								/>
								<?php esc_html_e( 'Show bundle tiers on every product unless turned off per product.', 'molecule-bundle-pricing' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Default tiers', 'molecule-bundle-pricing' ); ?></th>
						<td>
							<?php MBP_Admin_View::tier_rows( 'mbp_settings[tiers]', $settings['tiers'] ); ?>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save changes', 'molecule-bundle-pricing' ) ); ?>
			</form>
		</div>
		<?php
	}
}
