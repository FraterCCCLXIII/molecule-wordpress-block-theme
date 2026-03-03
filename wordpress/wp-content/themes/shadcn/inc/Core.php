<?php

namespace Shadcn;

use Shadcn\Traits\SingletonTrait;

class Core {
	use SingletonTrait;

	private const COMMENTS_MIGRATION_OPTION = 'shadcn_comments_disabled_for_posts_v1';
	private const HEADER_NAV_MIGRATION_OPTION = 'shadcn_legacy_header_nav_migrated_v1';
	private const LEGACY_HEADER_DROPDOWN_MARKUP_V1 = '<div class="molecule-desktop-dropdown"><button class="molecule-desktop-dropdown-toggle" type="button" aria-expanded="false" aria-haspopup="true">Research</button><div class="molecule-desktop-dropdown-menu" hidden><a href="/peptide-guide">Peptide Guide</a><a href="/research">Research</a></div></div>';
	private const LEGACY_HEADER_DROPDOWN_MARKUP_V2 = '<div class="molecule-desktop-dropdown"><button class="molecule-desktop-dropdown-toggle" type="button" aria-expanded="false" aria-haspopup="true">Research<span class="molecule-desktop-dropdown-caret" aria-hidden="true"><svg viewBox="0 0 16 16" focusable="false"><path d="M4 6l4 4 4-4"/></svg></span></button><div class="molecule-desktop-dropdown-menu" hidden><a href="/peptide-guide">Peptide Guide</a><a href="/research">Research</a></div></div>';
	private const LEGACY_HEADER_DROPDOWN_MARKUP = '<div class="molecule-desktop-dropdown"><button class="molecule-desktop-dropdown-toggle" type="button" aria-expanded="false" aria-haspopup="true">Research<span class="molecule-desktop-dropdown-caret" aria-hidden="true"><svg viewBox="0 0 16 16" focusable="false"><path d="M4 6l4 4 4-4"/></svg></span></button><div class="molecule-desktop-dropdown-menu" hidden><a href="/peptide-guide">Peptide Guide</a><a href="/research">Research</a></div></div>';
	private const LEGACY_MOBILE_DRAWER_SECTION_MARKUP = '<div class="molecule-mobile-drawer-section">Resources</div>';
	private const LEGACY_MOBILE_DRAWER_QUICK_ICONS_MARKUP = '<div class="molecule-mobile-drawer-quick-icons" aria-label="Quick actions"><a href="/?s=" aria-label="Search"><svg role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true"><circle cx="11" cy="10" r="7" fill="none" stroke="currentColor"></circle><path d="m16 15 3 3" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path></svg></a><a href="/my-account" aria-label="Account"><svg role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true"><circle cx="11" cy="7" r="4" fill="none" stroke="currentColor"></circle><path d="M3.5 19c1.421-2.974 4.247-5 7.5-5s6.079 2.026 7.5 5" fill="none" stroke="currentColor" stroke-linecap="round"></path></svg></a><a class="molecule-cart-icon-link" href="/cart" aria-label="Cart"><svg role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true"><path d="M11 7H3.577A2 2 0 0 0 1.64 9.497l2.051 8A2 2 0 0 0 5.63 19H16.37a2 2 0 0 0 1.937-1.503l2.052-8A2 2 0 0 0 18.422 7H11Zm0 0V1" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path></svg></a></div>';

	public function __construct() {
		add_action( 'after_setup_theme', array( $this, 'setup_theme' ) );
		add_action( 'after_setup_theme', array( $this, 'setup_editor_styles' ) );
		add_action( 'after_setup_theme', array( $this, 'starter_content_setup' ) );
		add_action( 'init', array( $this, 'close_comments_for_existing_posts_once' ) );
		add_action( 'after_switch_theme', array( $this, 'close_comments_for_existing_posts' ) );
		add_action( 'init', array( $this, 'migrate_legacy_header_navigation_once' ) );
		add_filter( 'comments_open', array( $this, 'disable_post_comments' ), 20, 2 );
		add_filter( 'pings_open', array( $this, 'disable_post_comments' ), 20, 2 );
		add_filter( 'render_block', array( $this, 'patch_legacy_header_navigation' ), 20, 2 );

		require_once __DIR__ . '/Core/Blocks.php';
		require_once __DIR__ . '/Core/Patterns.php';
	}

	public function setup_theme() {
		// Add theme support for various features
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'custom-logo' );
		add_theme_support(
			'html5',
			array(
				'search-form',
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
				'style',
				'script',
			)
		);
		// Add theme support
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'responsive-embeds' );
	}

	public function setup_editor_styles() {
		add_theme_support( 'editor-styles' );
		add_editor_style( get_template_directory_uri() . '/assets/css/editor-style.css' );
	}

	/**
	 * Add support for starter content
	 */
	public function starter_content_setup() {
		add_theme_support(
			'starter-content',
			array(
				'widgets'    => array(
					'sidebar-1' => array(
						'text_business_info',
						'search',
						'text_about',
					),
				),
				'posts'      => array(
					'home',
					'about'            => array(
						'thumbnail' => '{{image-sandwich}}',
					),
					'contact'          => array(
						'thumbnail' => '{{image-espresso}}',
					),
					'blog'             => array(
						'thumbnail' => '{{image-coffee}}',
					),
					'homepage-section' => array(
						'thumbnail' => '{{image-espresso}}',
					),
				),
				'options'    => array(
					'show_on_front'  => 'page',
					'page_on_front'  => '{{home}}',
					'page_for_posts' => '{{blog}}',
				),
				'theme_mods' => array(
					'panel_1' => '{{homepage-section}}',
					'panel_2' => '{{about}}',
					'panel_3' => '{{blog}}',
					'panel_4' => '{{contact}}',
				),
				'nav_menus'  => array(
					'top'    => array(
						'name'  => __( 'Top Menu', 'shadcn' ),
						'items' => array(
							'link_home',
							'page_about',
							'page_blog',
							'page_contact',
						),
					),
					'social' => array(
						'name'  => __( 'Social Links Menu', 'shadcn' ),
						'items' => array(
							'link_yelp',
							'link_facebook',
							'link_twitter',
							'link_instagram',
							'link_email',
						),
					),
				),
			)
		);
	}

	public function disable_post_comments( $open, $post_id ) {
		if ( 'post' === get_post_type( $post_id ) ) {
			return false;
		}

		return $open;
	}

	public function close_comments_for_existing_posts_once() {
		if ( get_option( self::COMMENTS_MIGRATION_OPTION ) ) {
			return;
		}

		$this->close_comments_for_existing_posts();
	}

	public function close_comments_for_existing_posts() {
		global $wpdb;

		$wpdb->query(
			"UPDATE {$wpdb->posts}
			SET comment_status = 'closed', ping_status = 'closed'
			WHERE post_type = 'post'
			AND ( comment_status <> 'closed' OR ping_status <> 'closed' )"
		);

		update_option( self::COMMENTS_MIGRATION_OPTION, gmdate( 'c' ) );
	}

	/**
	 * Apply a one-time migration to DB-saved template parts using legacy header HTML.
	 *
	 * @return void
	 */
	public function migrate_legacy_header_navigation_once() {
		if ( get_option( self::HEADER_NAV_MIGRATION_OPTION ) ) {
			return;
		}

		$template_parts = get_posts(
			array(
				'post_type'      => 'wp_template_part',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		foreach ( $template_parts as $template_part_id ) {
			$content = get_post_field( 'post_content', $template_part_id );

			if ( ! is_string( $content ) || '' === $content ) {
				continue;
			}

			$updated_content = $this->normalize_legacy_header_markup( $content );

			if ( $updated_content === $content ) {
				continue;
			}

			wp_update_post(
				array(
					'ID'           => $template_part_id,
					'post_content' => $updated_content,
				)
			);
		}

		update_option( self::HEADER_NAV_MIGRATION_OPTION, gmdate( 'c' ) );
	}

	/**
	 * Patch legacy DB-saved header HTML so navigation links remain functional.
	 *
	 * @param string $block_content Rendered block HTML.
	 * @param array  $block Parsed block metadata.
	 *
	 * @return string
	 */
	public function patch_legacy_header_navigation( $block_content, $block ) {
		if ( ! is_string( $block_content ) || '' === $block_content ) {
			return $block_content;
		}

		if ( empty( $block['blockName'] ) || 'core/html' !== $block['blockName'] ) {
			return $block_content;
		}

		return $this->normalize_legacy_header_markup( $block_content );
	}

	/**
	 * Normalize legacy header link/dropdown HTML.
	 *
	 * @param string $content Header block content.
	 *
	 * @return string
	 */
	private function normalize_legacy_header_markup( $content ) {
		if ( false !== strpos( $content, '/pages/peptide-guide' ) ) {
			$content = str_replace( '/pages/peptide-guide', '/peptide-guide', $content );
		}

		if (
			false !== strpos( $content, 'class="molecule-desktop-links"' ) &&
			false !== strpos( $content, '<a href="/research">Resources</a>' )
		) {
			$content = str_replace(
				'<a href="/research">Resources</a>',
				self::LEGACY_HEADER_DROPDOWN_MARKUP,
				$content
			);
		}

		if ( false !== strpos( $content, self::LEGACY_HEADER_DROPDOWN_MARKUP_V1 ) ) {
			$content = str_replace(
				self::LEGACY_HEADER_DROPDOWN_MARKUP_V1,
				self::LEGACY_HEADER_DROPDOWN_MARKUP,
				$content
			);
		}

		if ( false !== strpos( $content, self::LEGACY_HEADER_DROPDOWN_MARKUP_V2 ) ) {
			$content = str_replace(
				self::LEGACY_HEADER_DROPDOWN_MARKUP_V2,
				self::LEGACY_HEADER_DROPDOWN_MARKUP,
				$content
			);
		}

		$content = $this->normalize_legacy_mobile_drawer_markup( $content );
		$content = $this->normalize_legacy_cart_count_markup( $content );

		return $content;
	}

	/**
	 * Normalize legacy mobile drawer markup in DB-saved headers.
	 *
	 * @param string $content Header block content.
	 *
	 * @return string
	 */
	private function normalize_legacy_mobile_drawer_markup( $content ) {
		if ( false === strpos( $content, 'class="molecule-mobile-drawer"' ) ) {
			return $content;
		}

		if ( false !== strpos( $content, self::LEGACY_MOBILE_DRAWER_SECTION_MARKUP ) ) {
			$content = str_replace(
				self::LEGACY_MOBILE_DRAWER_SECTION_MARKUP,
				'',
				$content
			);
		}

		$content = preg_replace(
			'/<div class="molecule-mobile-drawer-quick-icons"[^>]*>.*?<\/div>/s',
			'',
			$content
		);

		if ( false !== strpos( $content, 'class="molecule-mobile-drawer-header"' ) ) {
			$content = preg_replace(
				'/(<div class="molecule-mobile-drawer-header">)(.*?)(<\/div>)/s',
				'$1$2' . self::LEGACY_MOBILE_DRAWER_QUICK_ICONS_MARKUP . '$3',
				$content,
				1
			);
		}

		return $content;
	}

	/**
	 * Add cart item badge to legacy cart icons when cart has items.
	 *
	 * @param string $content Header block content.
	 *
	 * @return string
	 */
	private function normalize_legacy_cart_count_markup( $content ) {
		if ( false === strpos( $content, 'molecule-cart-icon-link' ) ) {
			return $content;
		}

		$content = preg_replace(
			'/<span class="molecule-cart-count"[^>]*>.*?<\/span>/s',
			'',
			$content
		);

		$cart_count = $this->get_woocommerce_cart_count();
		if ( $cart_count < 1 ) {
			return $content;
		}

		$badge_markup = '<span class="molecule-cart-count" aria-hidden="true">' . esc_html( (string) $cart_count ) . '</span>';

		return preg_replace_callback(
			'/(<a\b[^>]*class="[^"]*\bmolecule-cart-icon-link\b[^"]*"[^>]*>)(.*?)(<\/a>)/s',
			static function ( $matches ) use ( $badge_markup ) {
				return $matches[1] . $matches[2] . $badge_markup . $matches[3];
			},
			$content
		);
	}

	/**
	 * Resolve WooCommerce cart quantity safely during block rendering.
	 *
	 * @return int
	 */
	private function get_woocommerce_cart_count() {
		if ( ! function_exists( 'WC' ) ) {
			return 0;
		}

		$woocommerce = WC();
		if ( ! $woocommerce || empty( $woocommerce->cart ) ) {
			return 0;
		}

		return max( 0, (int) $woocommerce->cart->get_cart_contents_count() );
	}
}

Core::get_instance();
