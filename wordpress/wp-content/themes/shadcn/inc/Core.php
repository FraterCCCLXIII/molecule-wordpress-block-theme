<?php

namespace Shadcn;

use Shadcn\Traits\SingletonTrait;

class Core {
	use SingletonTrait;

	private const COMMENTS_MIGRATION_OPTION      = 'shadcn_comments_disabled_for_posts_v1';
	private const HEADER_NATIVE_MIGRATION_OPTION = 'shadcn_native_header_migrated_v5';
	private const HEADER_NATIVE_ROLLBACK_OPTION  = 'shadcn_native_header_rollback_v5';

	public function __construct() {
		add_action( 'after_setup_theme', array( $this, 'setup_theme' ) );
		add_action( 'after_setup_theme', array( $this, 'setup_editor_styles' ) );
		add_action( 'after_setup_theme', array( $this, 'starter_content_setup' ) );
		add_action( 'init', array( $this, 'close_comments_for_existing_posts_once' ) );
		add_action( 'after_switch_theme', array( $this, 'close_comments_for_existing_posts' ) );
		add_action( 'init', array( $this, 'migrate_to_native_header_once' ) );
		add_filter( 'render_block', array( $this, 'override_mini_cart_icon' ), 20, 2 );
		add_filter( 'comments_open', array( $this, 'disable_post_comments' ), 20, 2 );
		add_filter( 'pings_open', array( $this, 'disable_post_comments' ), 20, 2 );

		require_once __DIR__ . '/Core/Blocks.php';
		require_once __DIR__ . '/Core/Patterns.php';
	}

	public function setup_theme() {
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
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'responsive-embeds' );
	}

	public function setup_editor_styles() {
		add_theme_support( 'editor-styles' );
		add_editor_style( get_template_directory_uri() . '/assets/css/editor-style.css' );
	}

	/**
	 * Add support for starter content.
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
	 * One-time migration: replace legacy custom-HTML-block header template parts
	 * with the canonical native-block content from parts/header.html.
	 *
	 * A full rollback snapshot is saved to the DB before any changes are made.
	 * To roll back, call rollback_native_header_migration() directly or via
	 * WP-CLI: wp eval 'Shadcn\Core::get_instance()->rollback_native_header_migration();'
	 *
	 * @return void
	 */
	public function migrate_to_native_header_once() {
		if ( get_option( self::HEADER_NATIVE_MIGRATION_OPTION ) ) {
			return;
		}

		$canonical = $this->get_canonical_header_content();

		if ( empty( $canonical ) ) {
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

		$rollback = array();

		foreach ( $template_parts as $id ) {
			$content = get_post_field( 'post_content', $id );

			if ( ! is_string( $content ) || '' === $content ) {
				continue;
			}

			if ( ! $this->is_legacy_custom_html_header( $content ) ) {
				continue;
			}

			$rollback[ $id ] = $content;

			wp_update_post(
				array(
					'ID'           => $id,
					'post_content' => $canonical,
				)
			);
		}

		if ( ! empty( $rollback ) ) {
			update_option( self::HEADER_NATIVE_ROLLBACK_OPTION, $rollback, false );
		}

		update_option( self::HEADER_NATIVE_MIGRATION_OPTION, gmdate( 'c' ) );
	}

	/**
	 * Restore the pre-migration header content from the rollback snapshot and
	 * clear the migration flag so migrate_to_native_header_once() can re-run.
	 *
	 * Usage via WP-CLI:
	 *   wp eval 'Shadcn\Core::get_instance()->rollback_native_header_migration();'
	 *
	 * @return bool True if rollback ran, false if no snapshot exists.
	 */
	public function rollback_native_header_migration() {
		$rollback = get_option( self::HEADER_NATIVE_ROLLBACK_OPTION );

		if ( empty( $rollback ) || ! is_array( $rollback ) ) {
			return false;
		}

		foreach ( $rollback as $id => $content ) {
			wp_update_post(
				array(
					'ID'           => (int) $id,
					'post_content' => $content,
				)
			);
		}

		delete_option( self::HEADER_NATIVE_MIGRATION_OPTION );

		return true;
	}

	/**
	 * Replace the WooCommerce mini-cart block's default icon SVG with the
	 * custom stroke-based cart icon used throughout this theme.
	 *
	 * Only acts on header cart blocks (identified by molecule-header-cart class).
	 * Runs on every render but is fast — two early-exit string checks before
	 * any regex work.
	 *
	 * @param string $block_content Rendered block HTML.
	 * @param array  $block         Parsed block metadata.
	 * @return string
	 */
	public function override_mini_cart_icon( $block_content, $block ) {
		if ( empty( $block['blockName'] ) || 'woocommerce/mini-cart' !== $block['blockName'] ) {
			return $block_content;
		}

		if ( ! is_string( $block_content ) || false === strpos( $block_content, 'molecule-header-cart' ) ) {
			return $block_content;
		}

		$custom_svg = '<svg class="wc-block-mini-cart__icon" role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">'
			. '<path d="M11 7H3.577A2 2 0 0 0 1.64 9.497l2.051 8A2 2 0 0 0 5.63 19H16.37a2 2 0 0 0 1.937-1.503l2.052-8A2 2 0 0 0 18.422 7H11Zm0 0V1" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>'
			. '</svg>';

		return preg_replace(
			'/<svg\b[^>]*class="[^"]*wc-block-mini-cart__icon[^"]*"[^>]*>.*?<\/svg>/s',
			$custom_svg,
			$block_content,
			1
		);
	}

	/**
	 * Identify header template part content managed by this theme.
	 *
	 * Matches both legacy custom-HTML versions and any canonical native-block
	 * version so that migration version bumps always update the DB record.
	 *
	 * @param string $content Template part post_content.
	 * @return bool
	 */
	private function is_legacy_custom_html_header( $content ) {
		return false !== strpos( $content, 'molecule-top-nav' )
			|| false !== strpos( $content, 'molecule-mobile-menu-button' )
			|| false !== strpos( $content, 'molecule-mobile-drawer' )
			|| false !== strpos( $content, 'molecule-desktop-links' );
	}

	/**
	 * Read the canonical native-block header content from the theme file.
	 *
	 * @return string Block markup, or empty string if the file is missing.
	 */
	private function get_canonical_header_content() {
		$path = get_template_directory() . '/parts/header.html';

		if ( ! file_exists( $path ) ) {
			return '';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		return trim( (string) file_get_contents( $path ) );
	}
}

Core::get_instance();
