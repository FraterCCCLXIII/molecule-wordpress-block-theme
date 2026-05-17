<?php

namespace Shadcn\Integrations;

use Shadcn\Traits\SingletonTrait;

class WooCommerce {

	use SingletonTrait;

	/**
	 * @var bool
	 */
	private static $in_stock_toggle_printed = false;

	/**
	 * @var bool
	 */
	private static $classic_in_stock_sort_row_open = false;

	/**
	 * @var bool
	 */
	private static $php_shop_catalog_toolbar_open = false;

	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {
		add_filter( 'woocommerce_has_block_template', array( $this, 'disable_product_catalog_block_templates' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'render_available_sizes_module' ), 8 );
		add_action( 'woocommerce_before_variations_form', array( $this, 'render_variable_size_selector' ), 8 );
		add_filter( 'woocommerce_product_tabs', array( $this, 'override_product_detail_tabs' ), 20 );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'reorder_my_account_menu_items' ), 20 );
		add_filter( 'woocommerce_process_login_errors', array( $this, 'require_email_for_login' ), 10, 3 );
		add_filter( 'woocommerce_new_customer_data', array( $this, 'set_customer_username_from_email' ) );
		add_filter( 'body_class', array( $this, 'add_auth_mode_body_class' ) );
		add_filter( 'render_block', array( $this, 'inject_shop_loop_out_of_stock_badge_on_product_image' ), 10, 3 );
		add_filter( 'render_block', array( $this, 'prepend_shop_in_stock_toggle_near_catalog_blocks' ), 5, 2 );
		add_action( 'woocommerce_before_shop_loop', array( $this, 'open_php_shop_catalog_toolbar' ), 5 );
		add_action( 'woocommerce_before_shop_loop', array( $this, 'open_classic_shop_sort_stock_flex_row' ), 28 );
		add_action( 'woocommerce_before_shop_loop', array( $this, 'close_classic_shop_sort_stock_flex_row' ), 31 );
		add_action( 'woocommerce_before_shop_loop', array( $this, 'close_php_shop_catalog_toolbar' ), 35 );
		add_action( 'wp', array( $this, 'simplify_classic_product_catalog_loop' ), 20 );
		add_action( 'woocommerce_product_query', array( $this, 'php_catalog_query_show_all_products' ), 50, 2 );
		add_filter( 'loop_shop_columns', array( $this, 'php_catalog_loop_shop_columns' ), 20 );
		add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'open_loop_product_thumb_wrap' ), 5 );
		add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'maybe_render_loop_out_of_stock_badge' ), 88 );
		add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'close_loop_product_thumb_wrap' ), 90 );
	}

	/**
	 * Use theme woocommerce/archive-product.php instead of block templates for the product catalog.
	 *
	 * @param bool   $has_template   Whether WooCommerce registered a block template for this slug.
	 * @param string $template_name Template slug, e.g. archive-product, taxonomy-product_cat.
	 * @return bool
	 */
	public function disable_product_catalog_block_templates( $has_template, $template_name ) {
		if ( ! is_string( $template_name ) ) {
			return $has_template;
		}

		if ( 'archive-product' === $template_name || 'taxonomy-product_attribute' === $template_name ) {
			return false;
		}

		if ( preg_match( '/^taxonomy-(.+)$/', $template_name, $matches )
			&& taxonomy_exists( $matches[1] )
			&& is_object_in_taxonomy( 'product', $matches[1] ) ) {
			return false;
		}

		return $has_template;
	}

	/**
	 * Shop page or product taxonomy archive.
	 *
	 * @return bool
	 */
	private function is_php_product_archive() {
		if ( ! function_exists( 'is_shop' ) || ! function_exists( 'is_product_taxonomy' ) ) {
			return false;
		}

		return is_shop() || is_product_taxonomy();
	}

	/**
	 * Wrap classic catalog toolbar (notices, result count, in-stock + sort) for layout.
	 *
	 * @return void
	 */
	public function open_php_shop_catalog_toolbar() {
		if ( ! $this->is_php_product_archive() ) {
			return;
		}

		echo '<div class="molecule-php-shop-catalog-toolbar">';
		self::$php_shop_catalog_toolbar_open = true;
	}

	/**
	 * @return void
	 */
	public function close_php_shop_catalog_toolbar() {
		if ( ! self::$php_shop_catalog_toolbar_open ) {
			return;
		}

		echo '</div>';
		self::$php_shop_catalog_toolbar_open = false;
	}

	/**
	 * Classic PHP shop / taxonomy archives: three products per row.
	 *
	 * @param int $columns Default column count.
	 * @return int
	 */
	public function php_catalog_loop_shop_columns( $columns ) {
		if ( $this->is_php_product_archive() ) {
			return 3;
		}

		return (int) $columns;
	}

	/**
	 * Load every matching product on PHP shop/taxonomy archives (no paging).
	 *
	 * WooCommerce often keeps an existing posts_per_page from the main query, so
	 * loop_shop_per_page alone is not enough; set it on the product query directly.
	 *
	 * @param \WP_Query $query    Query instance.
	 * @param \WC_Query $wc_query WooCommerce query handler.
	 * @return void
	 */
	public function php_catalog_query_show_all_products( $query, $wc_query ) {
		unset( $wc_query );
		if ( ! $query instanceof \WP_Query ) {
			return;
		}

		if ( ! $this->is_php_product_archive() ) {
			return;
		}

		$query->set( 'posts_per_page', -1 );
		$query->set( 'nopaging', true );
	}

	/**
	 * Wrap loop thumbnail + sale flash for badge positioning (PHP catalog only).
	 *
	 * @return void
	 */
	public function open_loop_product_thumb_wrap() {
		if ( ! $this->is_php_product_archive() ) {
			return;
		}

		echo '<div class="molecule-loop-product-thumb-wrap">';
	}

	/**
	 * Sold out pill on lower-left of loop image (matches block catalog badge).
	 *
	 * @return void
	 */
	public function maybe_render_loop_out_of_stock_badge() {
		if ( ! $this->is_php_product_archive() ) {
			return;
		}

		global $product;

		if ( ! is_a( $product, \WC_Product::class ) || $product->is_in_stock() ) {
			return;
		}

		$availability = $product->get_availability();
		$text           = ( isset( $availability['availability'] ) && $availability['availability'] ) ?
			$availability['availability'] :
			__( 'Out of stock', 'woocommerce' );

		echo wp_kses_post(
			sprintf(
				'<span class="molecule-product-card-stock-badge molecule-product-card-stock-badge--out-of-stock">%s</span>',
				wp_kses_post( $text )
			)
		);
	}

	/**
	 * @return void
	 */
	public function close_loop_product_thumb_wrap() {
		if ( ! $this->is_php_product_archive() ) {
			return;
		}

		echo '</div>';
	}

	/**
	 * Open flex row before catalog ordering: sort select (priority 30) then toggle output in
	 * close_classic_shop_sort_stock_flex_row() so order is [sort][in-stock] left-to-right.
	 *
	 * @return void
	 */
	public function open_classic_shop_sort_stock_flex_row() {
		if ( ! $this->should_show_shop_in_stock_toggle() ) {
			return;
		}

		if ( ! $this->is_php_product_archive() ) {
			return;
		}

		if ( self::$in_stock_toggle_printed ) {
			return;
		}

		self::$classic_in_stock_sort_row_open = true;
		echo '<div class="molecule-shop-catalog-sort-with-stock">';
	}

	/**
	 * Output in-stock toggle and close the flex row after woocommerce_catalog_ordering (30).
	 *
	 * @return void
	 */
	public function close_classic_shop_sort_stock_flex_row() {
		if ( ! self::$classic_in_stock_sort_row_open ) {
			return;
		}

		self::$in_stock_toggle_printed = true;
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup built with esc_* in get_shop_in_stock_toggle_html().
		echo $this->get_shop_in_stock_toggle_html( false );
		echo '</div>';
		self::$classic_in_stock_sort_row_open = false;
	}

	/**
	 * PHP product archive: single price line, no loop add-to-cart, no GreenPay duplicate summary under title.
	 *
	 * @return void
	 */
	public function simplify_classic_product_catalog_loop() {
		if ( ! $this->is_php_product_archive() ) {
			return;
		}

		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
		remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
		remove_action( 'woocommerce_after_shop_loop', 'woocommerce_pagination', 10 );
		$this->remove_greenpay_shop_loop_product_summary();
	}

	/**
	 * GreenPay prints `.subscription-period-prices` after the title; the classic loop already outputs `.price`.
	 *
	 * @return void
	 */
	private function remove_greenpay_shop_loop_product_summary() {
		if ( ! class_exists( 'WC_GreenPay_Gateway' ) || ! function_exists( 'WC' ) ) {
			return;
		}

		$payment_gateways = WC()->payment_gateways();
		if ( ! $payment_gateways ) {
			return;
		}

		$gateways = $payment_gateways->payment_gateways();
		if ( empty( $gateways['greenmoney'] ) || ! $gateways['greenmoney'] instanceof \WC_GreenPay_Gateway ) {
			return;
		}

		remove_action( 'woocommerce_after_shop_loop_item_title', array( $gateways['greenmoney'], 'show_product_summary' ), 11 );
	}

	public function enqueue_scripts() {
		wp_enqueue_style( 'shadcn-woocommerce', get_template_directory_uri() . '/assets/css/woocommerce.css', array(), wp_get_theme()->get( 'Version' ) );

		// Late cascade win: login options row must not inherit horizontal padding from global/block styles.
		wp_add_inline_style(
			'shadcn-woocommerce',
			'html body.woocommerce-account form.woocommerce-form.woocommerce-form-login.login .molecule-login-options-row{' .
			'padding:0!important;padding-block:0!important;padding-inline:0!important;' .
			'margin-left:0!important;margin-right:0!important;margin-inline:0!important;' .
			'box-sizing:border-box!important;}' .
			'html body.woocommerce-account form.woocommerce-form.woocommerce-form-login.login .molecule-login-options-row>.molecule-login-remember-wrap,' .
			'html body.woocommerce-account form.woocommerce-form.woocommerce-form-login.login .molecule-login-options-row>.molecule-login-lost-password,' .
			'html body.woocommerce-account form.woocommerce-form.woocommerce-form-login.login .molecule-login-options-row label.woocommerce-form-login__rememberme{' .
			'padding:0!important;padding-inline:0!important;}'
		);

		wp_enqueue_style( 'shadcn-side-cart', get_template_directory_uri() . '/assets/css/side-cart.css', array( 'shadcn-woocommerce' ), wp_get_theme()->get( 'Version' ) );

		if ( function_exists( 'is_product' ) && is_product() ) {
			wp_enqueue_script(
				'shadcn-variable-size-selector',
				get_template_directory_uri() . '/assets/js/variable-size-selector.js',
				array( 'jquery', 'wc-add-to-cart-variation' ),
				wp_get_theme()->get( 'Version' ),
				true
			);
		}

		if ( function_exists( 'is_account_page' ) && is_account_page() && is_user_logged_in() ) {
			wp_enqueue_script(
				'shadcn-my-account-nav-mobile',
				get_template_directory_uri() . '/assets/js/my-account-nav-mobile.js',
				array(),
				wp_get_theme()->get( 'Version' ),
				true
			);
		}

		if ( $this->should_show_shop_in_stock_toggle() ) {
			wp_enqueue_script(
				'shadcn-shop-in-stock-toggle',
				get_template_directory_uri() . '/assets/js/shop-in-stock-toggle.js',
				array(),
				wp_get_theme()->get( 'Version' ),
				true
			);
		}
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

		return array(
			'description' => array(
				'title'    => esc_html__( 'Description', 'shadcn' ),
				'priority' => 10,
				'callback' => array( $this, 'render_description_tab' ),
			),
			'research'    => array(
				'title'    => esc_html__( 'Research', 'shadcn' ),
				'priority' => 20,
				'callback' => array( $this, 'render_additional_information_tab' ),
			),
			'coa'         => array(
				'title'    => esc_html__( 'COA', 'shadcn' ),
				'priority' => 30,
				'callback' => array( $this, 'render_reviews_tab' ),
			),
		);
	}

	/**
	 * Render the Description tab from product post content.
	 */
	public function render_description_tab( $key = '', $tab = array() ) {
		if ( function_exists( 'woocommerce_product_description_tab' ) ) {
			woocommerce_product_description_tab();
		}
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
	 * Keep "My Subscriptions" above "Log out" in My Account navigation.
	 *
	 * @param array<string, string> $items Account endpoint menu items.
	 * @return array<string, string>
	 */
	public function reorder_my_account_menu_items( $items ) {
		if ( ! isset( $items['my-subscriptions'], $items['customer-logout'] ) ) {
			return $items;
		}

		$subscriptions_label = $items['my-subscriptions'];
		unset( $items['my-subscriptions'] );

		$reordered = array();
		foreach ( $items as $endpoint => $label ) {
			if ( 'customer-logout' === $endpoint ) {
				$reordered['my-subscriptions'] = $subscriptions_label;
			}

			$reordered[ $endpoint ] = $label;
		}

		return $reordered;
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

		$classes[] = 'molecule-guest-auth-layout';

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
	 * Render a custom row-style selector placeholder for variable product sizes.
	 *
	 * The options are hydrated from WooCommerce's native <select> by frontend JS
	 * so variation validation and stock logic remain untouched.
	 */
	public function render_variable_size_selector() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		$product = wc_get_product( get_the_ID() );
		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return;
		}
		?>
		<div class="molecule-variable-size-selector molecule-available-sizes" data-molecule-variable-size-selector>
			<p class="molecule-variable-size-selector__label molecule-available-sizes__label"><?php esc_html_e( 'Size', 'shadcn' ); ?></p>
			<div class="molecule-variable-size-selector__options molecule-available-sizes__options" role="group" aria-label="<?php esc_attr_e( 'Size options', 'shadcn' ); ?>"></div>
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

	/**
	 * Whether we are on a product catalog view (shop, categories, etc.), not single product or cart flows.
	 *
	 * @return bool
	 */
	private function is_product_catalog_screen() {
		if ( ! function_exists( 'is_shop' ) ) {
			return false;
		}

		if ( is_cart() || is_checkout() || is_account_page() || is_product() ) {
			return false;
		}

		return is_shop() || is_product_taxonomy() || is_post_type_archive( 'product' );
	}

	/**
	 * On catalog pages, overlay an out-of-stock pill on product card images.
	 *
	 * @param string                                  $block_content  Rendered block HTML.
	 * @param array<string, mixed>                    $parsed_block   Block data.
	 * @param \WP_Block|null                          $block_instance Block instance (contains loop context).
	 * @return string
	 */
	public function inject_shop_loop_out_of_stock_badge_on_product_image( $block_content, $parsed_block, $block_instance ) {
		if ( empty( $parsed_block['blockName'] ) || 'woocommerce/product-image' !== $parsed_block['blockName'] ) {
			return $block_content;
		}

		if ( ! $block_instance instanceof \WP_Block ) {
			return $block_content;
		}

		$context = $block_instance->context ?? array();
		$query   = $context['query'] ?? array();

		if ( empty( $query['isProductCollectionBlock'] ) ) {
			return $block_content;
		}

		if ( ! $this->is_product_catalog_screen() ) {
			return $block_content;
		}

		$post_id = isset( $context['postId'] ) ? absint( $context['postId'] ) : 0;
		if ( ! $post_id ) {
			return $block_content;
		}

		$product = wc_get_product( $post_id );
		if ( ! $product || $product->is_in_stock() ) {
			return $block_content;
		}

		$availability = $product->get_availability();
		$text         = isset( $availability['availability'] ) && $availability['availability'] ?
			$availability['availability'] :
			__( 'Out of stock', 'woocommerce' );

		$badge = sprintf(
			'<span class="molecule-product-card-stock-badge molecule-product-card-stock-badge--out-of-stock">%s</span>',
			wp_kses_post( $text )
		);

		// Sibling of <a> inside the image wrapper so position:absolute anchors to the full tile, not the link box.
		if ( preg_match( '/<div\s[^>]*\bwc-block-components-product-image\b[^>]*>/', $block_content, $matches, PREG_OFFSET_CAPTURE ) ) {
			$insert_at = $matches[0][1] + strlen( $matches[0][0] );

			return substr_replace( $block_content, $badge, $insert_at, 0 );
		}

		return $block_content;
	}

	/**
	 * Whether the in-stock toggle should appear (catalog, and WC not already hiding OOS).
	 *
	 * @return bool
	 */
	private function should_show_shop_in_stock_toggle() {
		return $this->is_product_catalog_screen()
			&& 'yes' !== get_option( 'woocommerce_hide_out_of_stock_items' );
	}

	/**
	 * True when URL filter limits catalog to in-stock products only (instock only).
	 *
	 * @return bool
	 */
	private function is_instock_only_filter_active() {
		$raw = (string) get_query_var( 'filter_stock_status' );
		if ( '' === trim( $raw ) ) {
			return false;
		}

		$statuses = array_filter( array_map( 'trim', explode( ',', $raw ) ) );

		return array( 'instock' ) === $statuses;
	}

	/**
	 * Markup for the "In stock only" control.
	 *
	 * @param bool $align_wide Add alignwide for standalone placement above the grid.
	 * @return string
	 */
	private function get_shop_in_stock_toggle_html( $align_wide = false ) {
		$field_id = wp_unique_id( 'molecule-in-stock-only-' );
		$checked  = $this->is_instock_only_filter_active();
		$classes  = 'molecule-shop-in-stock-toggle' . ( $align_wide ? ' alignwide' : '' );

		ob_start();
		?>
		<div class="<?php echo esc_attr( $classes ); ?>">
			<div class="molecule-availability-facet">
				<label for="<?php echo esc_attr( $field_id ); ?>" class="molecule-availability-facet__label"><?php esc_html_e( 'In stock only', 'shadcn' ); ?></label>
				<input
					id="<?php echo esc_attr( $field_id ); ?>"
					type="checkbox"
					class="switch molecule-in-stock-only-switch"
					<?php checked( $checked ); ?>
					autocomplete="off"
				/>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Place "In stock only" immediately left of the catalog sort dropdown (catalog-sorting row), or above the grid if that block is absent.
	 *
	 * @param string               $block_content Block HTML.
	 * @param array<string, mixed> $parsed_block  Parsed block.
	 * @return string
	 */
	public function prepend_shop_in_stock_toggle_near_catalog_blocks( $block_content, $parsed_block ) {
		if ( empty( $parsed_block['blockName'] ) ) {
			return $block_content;
		}

		if ( ! $this->should_show_shop_in_stock_toggle() ) {
			return $block_content;
		}

		$block_name = $parsed_block['blockName'];

		if ( 'woocommerce/catalog-sorting' === $block_name ) {
			if ( self::$in_stock_toggle_printed ) {
				return $block_content;
			}
			self::$in_stock_toggle_printed = true;

			$toggle = $this->get_shop_in_stock_toggle_html( false );

			return sprintf(
				'<div class="molecule-shop-catalog-sort-with-stock">%1$s%2$s</div>',
				$toggle,
				$block_content
			);
		}

		if ( 'woocommerce/product-collection' !== $block_name ) {
			return $block_content;
		}

		$query = $parsed_block['attrs']['query'] ?? array();
		if ( empty( $query['inherit'] ) ) {
			return $block_content;
		}

		if ( self::$in_stock_toggle_printed ) {
			return $block_content;
		}
		self::$in_stock_toggle_printed = true;

		return $this->get_shop_in_stock_toggle_html( true ) . $block_content;
	}
}

WooCommerce::get_instance();
