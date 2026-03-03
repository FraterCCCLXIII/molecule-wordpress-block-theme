<?php

namespace Shadcn\Integrations;

use Shadcn\Traits\SingletonTrait;

class WooCommerce {

	use SingletonTrait;

	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'render_available_sizes_module' ), 8 );
		add_filter( 'woocommerce_product_tabs', array( $this, 'override_product_detail_tabs' ), 20 );
		add_filter( 'woocommerce_process_login_errors', array( $this, 'require_email_for_login' ), 10, 3 );
		add_filter( 'woocommerce_new_customer_data', array( $this, 'set_customer_username_from_email' ) );
		add_filter( 'body_class', array( $this, 'add_auth_mode_body_class' ) );
	}

	public function enqueue_scripts() {
		wp_enqueue_style( 'shadcn-woocommerce', get_template_directory_uri() . '/assets/css/woocommerce.css', array(), wp_get_theme()->get( 'Version' ) );
		wp_enqueue_style( 'shadcn-side-cart', get_template_directory_uri() . '/assets/css/side-cart.css', array( 'shadcn-woocommerce' ), wp_get_theme()->get( 'Version' ) );
	}

	/**
	 * Replace Product Details tab callbacks with ACF-backed content.
	 *
	 * Expected filter shape for `shadcn_product_details_tab_acf_sources`:
	 * [
	 *   'additional_information' => [
	 *     'field_key'  => 'field_xxxxx', // Preferred when available.
	 *     'field_name' => 'your_field_name',
	 *     'post_id'    => 123|'option'|null, // null = current product id
	 *   ],
	 *   'reviews' => [
	 *     'field_key'  => 'field_yyyyy', // Preferred when available.
	 *     'field_name' => 'your_other_field_name',
	 *     'post_id'    => 123|'option'|null, // null = current product id
	 *   ],
	 * ]
	 *
	 * @param array<string, mixed> $tabs Existing WooCommerce tabs.
	 * @return array<string, mixed>
	 */
	public function override_product_detail_tabs( $tabs ) {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return $tabs;
		}

		if ( isset( $tabs['additional_information'] ) ) {
			$tabs['additional_information']['title']    = esc_html__( 'Research', 'shadcn' );
			$tabs['additional_information']['callback'] = array( $this, 'render_additional_information_tab' );
		}

		if ( isset( $tabs['reviews'] ) ) {
			$tabs['reviews']['title']    = esc_html__( 'COA', 'shadcn' );
			$tabs['reviews']['callback'] = array( $this, 'render_reviews_tab' );
		}

		return $tabs;
	}

	/**
	 * Render the Additional Information tab from ACF, fallback to WooCommerce default.
	 */
	public function render_additional_information_tab( $key = '', $tab = array() ) {
		$content = $this->get_product_tab_acf_content( 'additional_information', 'product_additional_information_content' );

		if ( '' === $content ) {
			woocommerce_product_additional_information_tab();
			return;
		}

		echo wp_kses_post( $content );
	}

	/**
	 * Render the Reviews tab from ACF, fallback to WooCommerce default reviews template.
	 */
	public function render_reviews_tab( $key = '', $tab = array() ) {
		$content = $this->get_product_tab_acf_content( 'reviews', 'product_reviews_content' );

		if ( '' === $content ) {
			comments_template();
			return;
		}

		echo wp_kses_post( $content );
	}

	/**
	 * Resolve tab content from configurable ACF locations.
	 *
	 * @param string $tab_key            Tab key.
	 * @param string $default_field_name Field name used when no filter override exists.
	 * @return string
	 */
	private function get_product_tab_acf_content( $tab_key, $default_field_name ) {
		if ( ! function_exists( 'get_field' ) || ! function_exists( 'wc_get_product' ) ) {
			return '';
		}

		$product = wc_get_product( get_the_ID() );
		if ( ! $product ) {
			return '';
		}

		$product_id = (int) $product->get_id();
		$sources    = apply_filters(
			'shadcn_product_details_tab_acf_sources',
			array(
				'additional_information' => array(
					'field_name' => 'product_additional_information_content',
					'post_id'    => null,
				),
				'reviews'                => array(
					'field_name' => 'product_reviews_content',
					'post_id'    => null,
				),
			),
			$product
		);

		$source = isset( $sources[ $tab_key ] ) && is_array( $sources[ $tab_key ] ) ? $sources[ $tab_key ] : array();
		$field_key = isset( $source['field_key'] ) && is_string( $source['field_key'] ) ? trim( $source['field_key'] ) : '';
		$field     = isset( $source['field_name'] ) && is_string( $source['field_name'] ) ? $source['field_name'] : $default_field_name;
		$post      = array_key_exists( 'post_id', $source ) ? $source['post_id'] : $product_id;

		if ( empty( $field ) ) {
			return '';
		}

		if ( null === $post ) {
			$post = $product_id;
		}

		$content = '';
		if ( '' !== $field_key ) {
			$key_content = get_field( $field_key, $post );
			if ( is_string( $key_content ) && '' !== trim( $key_content ) ) {
				$content = $key_content;
			}
		}

		if ( '' === $content ) {
			$content = get_field( $field, $post );
		}

		if ( ! is_string( $content ) ) {
			return '';
		}

		$content = trim( $content );
		if ( '' === $content ) {
			return '';
		}

		return wpautop( $content );
	}

	/**
	 * Restrict My Account login to email addresses only.
	 *
	 * @param \WP_Error $validation_error Current validation errors.
	 * @param string    $username_or_email Submitted login identifier.
	 * @param string    $password Submitted password.
	 * @return \WP_Error
	 */
	public function require_email_for_login( $validation_error, $username_or_email, $password ) {
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
			return $validation_error;
		}

		$identifier = is_string( $username_or_email ) ? trim( $username_or_email ) : '';
		if ( '' !== $identifier && ! is_email( $identifier ) ) {
			return new \WP_Error(
				'email_login_required',
				esc_html__( 'Please log in with your email address.', 'shadcn' )
			);
		}

		return $validation_error;
	}

	/**
	 * Ensure WooCommerce registration uses email as internal username.
	 *
	 * @param array<string, mixed> $customer_data Data used to create customer.
	 * @return array<string, mixed>
	 */
	public function set_customer_username_from_email( $customer_data ) {
		if ( empty( $customer_data['email'] ) || ! is_email( $customer_data['email'] ) ) {
			return $customer_data;
		}

		$customer_data['username'] = sanitize_user( $customer_data['email'], true );

		return $customer_data;
	}

	/**
	 * Add auth mode body class for CSS-only form switching.
	 *
	 * @param string[] $classes Existing body classes.
	 * @return string[]
	 */
	public function add_auth_mode_body_class( $classes ) {
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() || is_user_logged_in() ) {
			return $classes;
		}

		if ( ! $this->is_account_registration_enabled() ) {
			return $classes;
		}

		$classes[] = 'molecule-auth-mode-' . $this->get_account_auth_mode();

		return $classes;
	}

	/**
	 * Show a non-interactive available sizes module for non-variable products.
	 *
	 * This keeps size information visible even when products only have one size.
	 */
	public function render_available_sizes_module() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		$product = wc_get_product( get_the_ID() );
		if ( ! $product || $product->is_type( 'variable' ) ) {
			return;
		}

		$sizes = $this->get_product_size_labels( $product );
		if ( empty( $sizes ) ) {
			return;
		}

		$group_label = 1 === count( $sizes ) ? esc_html__( 'Available size', 'shadcn' ) : esc_html__( 'Available sizes', 'shadcn' );
		?>
		<div class="molecule-available-sizes" role="group" aria-label="<?php echo esc_attr( $group_label ); ?>">
			<p class="molecule-available-sizes__label"><?php esc_html_e( 'Size', 'shadcn' ); ?></p>
			<div class="molecule-available-sizes__options">
				<?php foreach ( $sizes as $size ) : ?>
					<span class="molecule-available-sizes__option" aria-pressed="false"><?php echo esc_html( $size ); ?></span>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Resolve size labels from product attributes.
	 *
	 * @param \WC_Product $product Product instance.
	 * @return string[]
	 */
	private function get_product_size_labels( $product ) {
		$size_labels = array();
		$attributes  = $product->get_attributes();
		$candidates  = array( 'pa_size', 'size' );

		foreach ( $candidates as $attribute_key ) {
			if ( empty( $attributes[ $attribute_key ] ) ) {
				continue;
			}

			$attribute = $attributes[ $attribute_key ];
			if ( ! $attribute instanceof \WC_Product_Attribute ) {
				continue;
			}

			if ( $attribute->is_taxonomy() ) {
				$taxonomy    = $attribute->get_name();
				$size_labels = wc_get_product_terms( $product->get_id(), $taxonomy, array( 'fields' => 'names' ) );
			} else {
				$options = $attribute->get_options();
				if ( is_array( $options ) ) {
					$size_labels = array_values( array_filter( array_map( 'trim', $options ) ) );
				}
			}

			if ( ! empty( $size_labels ) ) {
				break;
			}
		}

		// Fallback for installations where size is stored as an ad-hoc string.
		if ( empty( $size_labels ) ) {
			foreach ( $candidates as $attribute_key ) {
				$raw_sizes = $product->get_attribute( $attribute_key );
				if ( ! is_string( $raw_sizes ) || '' === trim( $raw_sizes ) ) {
					continue;
				}

				$parsed_sizes = function_exists( 'wc_get_text_attributes' ) ? wc_get_text_attributes( $raw_sizes ) : explode( ',', $raw_sizes );
				$size_labels  = array_values( array_filter( array_map( 'trim', $parsed_sizes ) ) );

				if ( ! empty( $size_labels ) ) {
					break;
				}
			}
		}

		if ( empty( $size_labels ) ) {
			return array();
		}

		return array_values( array_unique( $size_labels ) );
	}

	/**
	 * Resolve the selected auth mode from current request.
	 *
	 * @return string 'login'|'register'
	 */
	private function get_account_auth_mode() {
		$requested_mode = isset( $_GET['auth'] ) ? sanitize_key( wp_unslash( $_GET['auth'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'register' === $requested_mode || isset( $_POST['register'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return 'register';
		}

		return 'login';
	}

	/**
	 * Determine whether creating an account is currently enabled.
	 *
	 * @return bool
	 */
	private function is_account_registration_enabled() {
		$wc_registration_enabled = 'yes' === get_option( 'woocommerce_enable_myaccount_registration', 'yes' );
		$wp_registration_enabled = (bool) get_option( 'users_can_register', false );

		return $wc_registration_enabled || $wp_registration_enabled;
	}
}

WooCommerce::get_instance();
