<?php
/**
 * Server-side catalog gate (guests only).
 *
 * @package MoleculeResearchGate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MRG_Gate
 */
class MRG_Gate {

	/**
	 * @var array<string, mixed>
	 */
	private $settings;

	/**
	 * @param array<string, mixed> $settings Plugin settings.
	 */
	public function __construct( array $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Register hooks.
	 */
	public function register(): void {
		add_action( 'template_redirect', array( $this, 'maybe_redirect_guest' ), 8 );
		add_action( 'wp_loaded', array( $this, 'maybe_apply_coupon_from_query' ), 20 );
	}

	/**
	 * Optional: apply configured welcome coupon when visiting cart/checked with mrg_apply_coupon.
	 */
	public function maybe_apply_coupon_from_query(): void {
		if ( is_admin() || ! is_user_logged_in() || ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}

		if ( empty( $_GET['mrg_apply_coupon'] ) || ! is_string( $_GET['mrg_apply_coupon'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$code    = sanitize_text_field( wp_unslash( $_GET['mrg_apply_coupon'] ) );
		$allowed = isset( $this->settings['welcome_coupon_code'] ) ? (string) $this->settings['welcome_coupon_code'] : '';
		if ( '' === $allowed || $code !== $allowed ) {
			return;
		}

		WC()->cart->apply_coupon( $code );

		$redirect = remove_query_arg( 'mrg_apply_coupon' );
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Redirect unauthenticated users away from gated WooCommerce views.
	 */
	public function maybe_redirect_guest(): void {
		if ( is_admin() || ! $this->woocommerce_available() || is_user_logged_in() ) {
			return;
		}

		if ( ! $this->is_request_gated_by_settings() ) {
			return;
		}

		$redirect_to = $this->get_current_url();
		$myaccount   = wc_get_page_permalink( 'myaccount' );
		if ( empty( $myaccount ) ) {
			return;
		}

		/**
		 * Filter final redirect target for gated guests.
		 *
		 * @param string $account_url URL with redirect_to and auth mode (login or register).
		 * @param string $redirect_to Intended return URL after authentication.
		 */
		$login_url = apply_filters(
			'molecule_research_gate_guest_redirect',
			add_query_arg(
				array(
					'redirect_to' => $redirect_to,
					'auth'        => $this->get_guest_auth_query_value(),
				),
				$myaccount
			),
			$redirect_to
		);

		wp_safe_redirect( $login_url );
		exit;
	}

	/**
	 * Whether WooCommerce is usable.
	 */
	public function woocommerce_available(): bool {
		return function_exists( 'wc_get_page_permalink' ) && class_exists( 'WooCommerce' );
	}

	/**
	 * `auth` query value when sending guests to My Account (`login` or `register`).
	 */
	public function get_guest_auth_query_value(): string {
		$mode = apply_filters( 'molecule_research_gate_guest_auth_mode', 'register' );
		return in_array( $mode, array( 'login', 'register' ), true ) ? $mode : 'register';
	}

	/**
	 * Gate match based on WC conditional tags + settings.
	 */
	public function is_request_gated_by_settings(): bool {
		if ( ! $this->woocommerce_available() ) {
			return false;
		}

		if ( ! empty( $this->settings['gate_shop'] ) && function_exists( 'is_shop' ) && is_shop() ) {
			return true;
		}
		if ( ! empty( $this->settings['gate_product'] ) && function_exists( 'is_product' ) && is_product() ) {
			return true;
		}
		if ( ! empty( $this->settings['gate_product_category'] ) && function_exists( 'is_product_category' ) && is_product_category() ) {
			return true;
		}
		if ( ! empty( $this->settings['gate_product_tag'] ) && function_exists( 'is_product_tag' ) && is_product_tag() ) {
			return true;
		}
		if ( ! empty( $this->settings['gate_cart'] ) && function_exists( 'is_cart' ) && is_cart() ) {
			return true;
		}
		if ( ! empty( $this->settings['gate_checkout'] ) && function_exists( 'is_checkout' ) && is_checkout() ) {
			return true;
		}

		return false;
	}

	/**
	 * Whether the current user should see the profile gate (logged in, profile incomplete, on gated view).
	 */
	public function current_user_requires_profile_modal(): bool {
		if ( ! is_user_logged_in() || ! $this->woocommerce_available() ) {
			return false;
		}

		$user_id = get_current_user_id();
		if ( MRG_User_Profile::is_profile_complete( $user_id ) ) {
			return false;
		}

		return $this->is_request_gated_by_settings();
	}

	/**
	 * Current full URL (scheme + host + request URI).
	 */
	public function get_current_url(): string {
		if ( empty( $_SERVER['HTTP_HOST'] ) || empty( $_SERVER['REQUEST_URI'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			return home_url( '/' );
		}
		$scheme = is_ssl() ? 'https' : 'http';
		$host   = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) );
		$uri    = wp_unslash( $_SERVER['REQUEST_URI'] );

		return esc_url_raw( $scheme . '://' . $host . $uri );
	}

	/**
	 * URL prefixes used to intercept navigation (mirrors gate_* settings).
	 *
	 * @return string[] Absolute URLs with trailing slash.
	 */
	public function get_link_match_prefixes(): array {
		$out = array();
		if ( ! $this->woocommerce_available() ) {
			return $out;
		}

		if ( ! empty( $this->settings['gate_shop'] ) ) {
			$shop = wc_get_page_permalink( 'shop' );
			if ( $shop ) {
				$out[] = trailingslashit( $shop );
			}
		}

		if ( ! empty( $this->settings['gate_product'] ) ) {
			$pt = get_post_type_object( 'product' );
			if ( $pt && ! empty( $pt->rewrite['slug'] ) ) {
				$out[] = trailingslashit( home_url( '/' . $pt->rewrite['slug'] . '/' ) );
			}
		}

		if ( ! empty( $this->settings['gate_product_category'] ) ) {
			$tax = get_taxonomy( 'product_cat' );
			if ( $tax && ! empty( $tax->rewrite['slug'] ) ) {
				$out[] = trailingslashit( home_url( '/' . $tax->rewrite['slug'] . '/' ) );
			}
		}

		if ( ! empty( $this->settings['gate_product_tag'] ) ) {
			$tax = get_taxonomy( 'product_tag' );
			if ( $tax && ! empty( $tax->rewrite['slug'] ) ) {
				$out[] = trailingslashit( home_url( '/' . $tax->rewrite['slug'] . '/' ) );
			}
		}

		if ( ! empty( $this->settings['gate_cart'] ) && function_exists( 'wc_get_cart_url' ) ) {
			$out[] = trailingslashit( wc_get_cart_url() );
		}

		if ( ! empty( $this->settings['gate_checkout'] ) && function_exists( 'wc_get_checkout_url' ) ) {
			$out[] = trailingslashit( wc_get_checkout_url() );
		}

		/**
		 * Filter URL prefixes for gated link interception.
		 *
		 * @param string[]             $out      URL prefixes.
		 * @param array<string, mixed> $settings Plugin settings.
		 */
		return array_unique( array_filter( apply_filters( 'molecule_research_gate_link_prefixes', $out, $this->settings ) ) );
	}

	/**
	 * Whether an absolute URL points at a gated catalog view (same prefixes as link interception).
	 *
	 * @param string $url Full URL (e.g. redirect target after login/registration).
	 */
	public function url_matches_gated_catalog( string $url ): bool {
		$url = trim( $url );
		if ( '' === $url ) {
			return false;
		}

		$validated = wp_validate_redirect( $url, '' );
		if ( '' === $validated ) {
			return false;
		}

		$prefixes = $this->get_link_match_prefixes();
		foreach ( $prefixes as $prefix ) {
			if ( ! is_string( $prefix ) || '' === $prefix ) {
				continue;
			}
			if ( strpos( $validated, $prefix ) === 0 ) {
				return true;
			}
			$base = untrailingslashit( $prefix );
			if ( $base && ( strpos( $validated, $base . '/' ) === 0 || untrailingslashit( $validated ) === $base ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Shop (product archive) URL for post-auth profile gate redirect.
	 */
	public function get_catalog_url_for_profile_gate(): string {
		if ( ! $this->woocommerce_available() ) {
			return '';
		}
		$shop = wc_get_page_permalink( 'shop' );
		if ( $shop ) {
			return $shop;
		}
		$archive = get_post_type_archive_link( 'product' );
		return is_string( $archive ) ? $archive : '';
	}
}
