<?php

namespace Shadcn;

use Shadcn\Traits\SingletonTrait;

class Core {
	use SingletonTrait;

	private const COMMENTS_MIGRATION_OPTION = 'shadcn_comments_disabled_for_posts_v1';

	public function __construct() {
		add_action( 'after_setup_theme', array( $this, 'setup_theme' ) );
		add_action( 'after_setup_theme', array( $this, 'setup_editor_styles' ) );
		add_action( 'after_setup_theme', array( $this, 'starter_content_setup' ) );
		add_action( 'init', array( $this, 'close_comments_for_existing_posts_once' ) );
		add_action( 'after_switch_theme', array( $this, 'close_comments_for_existing_posts' ) );
		add_filter( 'comments_open', array( $this, 'disable_post_comments' ), 20, 2 );
		add_filter( 'pings_open', array( $this, 'disable_post_comments' ), 20, 2 );
		add_filter( 'pre_render_block', array( $this, 'render_php_header' ), 5, 2 );

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
	 * Intercept the core/template-part "header" slot on the frontend and
	 * return the output of template-parts/header.php instead.
	 *
	 * The block editor (admin + REST requests) is left untouched so the Site
	 * Editor continues to show the block-based header for reference.
	 *
	 * @param string|null $pre_render Existing pre-render override (null = none).
	 * @param array       $block      Parsed block data.
	 * @return string|null PHP-rendered header HTML, or the original $pre_render.
	 */
	public function render_php_header( $pre_render, $block ) {
		if ( 'core/template-part' !== ( $block['blockName'] ?? '' ) ) {
			return $pre_render;
		}

		if ( 'header' !== ( $block['attrs']['slug'] ?? '' ) ) {
			return $pre_render;
		}

		// Skip override inside the block editor (admin screens) and REST requests.
		if ( is_admin() ) {
			return $pre_render;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return $pre_render;
		}

		$template = get_template_directory() . '/template-parts/header.php';

		if ( ! file_exists( $template ) ) {
			return $pre_render;
		}

		ob_start();
		include $template;
		return ob_get_clean();
	}
}

Core::get_instance();
