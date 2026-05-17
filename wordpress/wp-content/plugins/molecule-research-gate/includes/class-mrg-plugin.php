<?php
/**
 * Core plugin wiring.
 *
 * @package MoleculeResearchGate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MRG_Plugin
 */
class MRG_Plugin {

	/**
	 * @var array<string, mixed>
	 */
	private $settings;

	/**
	 * @var MRG_Gate|null
	 */
	private $gate;

	/**
	 * Init hooks.
	 */
	public function init(): void {
		$this->settings = MRG_Admin_Settings::parse( get_option( MRG_OPTION_KEY, array() ) );

		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				static function () {
					echo '<div class="notice notice-error"><p>';
					esc_html_e( 'Molecule Research Gate requires WooCommerce to be installed and active.', 'molecule-research-gate' );
					echo '</p></div>';
				}
			);
			return;
		}

		if ( is_admin() ) {
			( new MRG_Admin_Settings() )->register();
		}

		$this->gate = new MRG_Gate( $this->settings );
		$this->gate->register();

		$assets = new MRG_Assets( $this->settings, $this->gate );
		$assets->register();

		add_action( 'rest_api_init', array( $this, 'register_rest' ) );

		add_filter( 'woocommerce_login_redirect', array( $this, 'login_redirect' ), 10, 2 );
		add_filter( 'woocommerce_registration_redirect', array( $this, 'registration_redirect' ), 10, 1 );

		add_action( 'woocommerce_login_form', array( $this, 'inject_redirect_hidden_field' ), 5 );
		add_action( 'woocommerce_register_form', array( $this, 'inject_redirect_hidden_field' ), 5 );
	}

	/**
	 * REST.
	 */
	public function register_rest(): void {
		$controller = new MRG_REST_Controller();
		$controller->register_routes();
	}

	/**
	 * Prefer redirect_to query arg on My Account after login.
	 *
	 * @param string                $redirect URL.
	 * @param WP_User|WC_User|mixed $user User.
	 */
	public function login_redirect( $redirect, $user ): string {
		unset( $user );
		$redirect = $this->redirect_from_query_or( (string) $redirect );
		return $this->maybe_shop_for_incomplete_profile( $redirect );
	}

	/**
	 * @param string $redirect URL.
	 */
	public function registration_redirect( $redirect ): string {
		$redirect = $this->redirect_from_query_or( (string) $redirect );
		return $this->maybe_shop_for_incomplete_profile( $redirect );
	}

	/**
	 * After login or registration, send users who still need research profile to the catalog
	 * so the gate modal can open with the shop visible (when the default redirect is My Account, etc.).
	 *
	 * @param string $redirect Validated redirect URL from WooCommerce.
	 */
	private function maybe_shop_for_incomplete_profile( string $redirect ): string {
		if ( ! $this->gate || ! is_user_logged_in() ) {
			return $redirect;
		}

		$user_id = get_current_user_id();
		if ( MRG_User_Profile::is_profile_complete( $user_id ) ) {
			return $redirect;
		}

		if ( $this->gate->url_matches_gated_catalog( $redirect ) ) {
			return $redirect;
		}

		$shop = $this->gate->get_catalog_url_for_profile_gate();
		if ( '' === $shop ) {
			return $redirect;
		}

		/**
		 * Filter URL used when the customer still owes research profile data and
		 * the normal redirect would not load the catalog modal (e.g. My Account).
		 *
		 * @param string $shop     Default shop/archive URL.
		 * @param string $redirect Original redirect URL.
		 * @param int    $user_id  User ID.
		 */
		return (string) apply_filters( 'molecule_research_gate_post_auth_catalog_redirect', $shop, $redirect, $user_id );
	}

	/**
	 * @param string $fallback Fallback redirect.
	 */
	private function redirect_from_query_or( string $fallback ): string {
		if ( empty( $_GET['redirect_to'] ) || ! is_string( $_GET['redirect_to'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return $fallback;
		}
		$decoded = rawurldecode( wp_unslash( $_GET['redirect_to'] ) );
		if ( ! is_string( $decoded ) || '' === $decoded ) {
			return $fallback;
		}
		$validated = wp_validate_redirect( $decoded, '' );
		return '' !== $validated ? $validated : $fallback;
	}

	/**
	 * Hidden redirect field for WC login/register forms (theme may omit it).
	 */
	public function inject_redirect_hidden_field(): void {
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || is_user_logged_in() ) {
			return;
		}
		$url = $this->redirect_from_query_or( '' );
		if ( '' === $url ) {
			return;
		}
		printf(
			'<input type="hidden" name="redirect" value="%s" />',
			esc_url( $url )
		);
	}
}
