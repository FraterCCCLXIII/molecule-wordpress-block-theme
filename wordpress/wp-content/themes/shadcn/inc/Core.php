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
		add_action( 'init', array( $this, 'remove_footer_site_links_heading' ), 21 );
		add_action( 'init', array( $this, 'ensure_footer_refund_policy_link' ), 22 );
		add_action( 'init', array( $this, 'ensure_footer_contact_info' ), 22 );
		add_action( 'init', array( $this, 'ensure_footer_navigation_layout' ), 23 );
		add_action( 'after_switch_theme', array( $this, 'close_comments_for_existing_posts' ) );
		add_filter( 'comments_open', array( $this, 'disable_post_comments' ), 20, 2 );
		add_filter( 'pings_open', array( $this, 'disable_post_comments' ), 20, 2 );
		// Priority 20 — must run after WordPress core's _wp_add_block_level_preset_styles (priority 10)
		// which returns null when it receives a non-null value, nullifying any earlier pre-render.
		add_filter( 'pre_render_block', array( $this, 'render_php_header' ), 20, 2 );
		add_filter( 'pre_render_block', array( $this, 'suppress_footer_guest_account' ), 20, 2 );

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

	public function remove_footer_site_links_heading() {
		$footer_parts = get_posts(
			array(
				'post_type'      => 'wp_template_part',
				'name'           => 'footer',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
			)
		);

		if ( empty( $footer_parts ) ) {
			return;
		}

		$post = $footer_parts[0];
		if ( ! str_contains( $post->post_content, 'Site Links' ) ) {
			return;
		}

		$heading_block = '<!-- wp:heading {"level":4,"style":{"typography":{"fontSize":"var:preset|font-size|base","fontWeight":"600"},"spacing":{"margin":{"bottom":"var:preset|spacing|4"}}}} -->
<h4 class="wp-block-heading" style="margin-bottom:var(--wp--preset--spacing--4);font-size:var(--wp--preset--font-size--base);font-weight:600">Site Links</h4>
<!-- /wp:heading -->

';

		wp_update_post(
			array(
				'ID'           => $post->ID,
				'post_content' => str_replace( $heading_block, '', $post->post_content ),
			)
		);
	}

	public function ensure_footer_refund_policy_link() {
		$refund_page = get_page_by_path( 'refund_returns' );
		if ( ! $refund_page instanceof \WP_Post ) {
			return;
		}

		$navigation = get_posts(
			array(
				'post_type'      => 'wp_navigation',
				'name'           => 'footer-menu',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
			)
		);

		if ( empty( $navigation ) ) {
			return;
		}

		$post = $navigation[0];
		if ( str_contains( $post->post_content, '"id":' . $refund_page->ID ) || str_contains( $post->post_content, 'refund_returns' ) ) {
			return;
		}

		$permalink  = esc_url_raw( get_permalink( $refund_page ) );
		$link_block = sprintf(
			"\n\n<!-- wp:navigation-link {\"label\":\"Refund Policy\",\"type\":\"page\",\"id\":%d,\"url\":\"%s\",\"kind\":\"post-type\",\"metadata\":{\"bindings\":{\"url\":{\"source\":\"core/post-data\",\"args\":{\"field\":\"link\"}}}}} /-->\n",
			$refund_page->ID,
			$permalink
		);

		$terms_marker = '<!-- wp:navigation-link {"label":"Terms of Service"';
		if ( str_contains( $post->post_content, $terms_marker ) ) {
			$position = strpos( $post->post_content, $terms_marker );
			$end      = strpos( $post->post_content, '/-->', $position );
			if ( false !== $end ) {
				$end     += 4;
				$content = substr( $post->post_content, 0, $end ) . $link_block . substr( $post->post_content, $end );
			} else {
				$content = rtrim( $post->post_content ) . $link_block;
			}
		} else {
			$content = rtrim( $post->post_content ) . $link_block;
		}

		wp_update_post(
			array(
				'ID'           => $post->ID,
				'post_content' => $content,
			)
		);
	}

	public function ensure_footer_contact_info() {
		$footer_parts = get_posts(
			array(
				'post_type'      => 'wp_template_part',
				'name'           => 'footer',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
			)
		);

		if ( empty( $footer_parts ) ) {
			return;
		}

		$post = $footer_parts[0];
		if ( str_contains( $post->post_content, '(619) 341-8483' ) ) {
			return;
		}

		$disclaimer_marker = 'or for any form of therapeutic or diagnostic use.</p>
<!-- /wp:paragraph -->';
		$contact_block     = '

<!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"var:preset|color|muted-foreground"}}}},"textColor":"muted-foreground","fontSize":"xs"} -->
<p class="has-muted-foreground-color has-text-color has-link-color has-xs-font-size"><a href="tel:+16193418483">(619) 341-8483</a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"var:preset|color|muted-foreground"}}}},"textColor":"muted-foreground","fontSize":"xs"} -->
<p class="has-muted-foreground-color has-text-color has-link-color has-xs-font-size">Open Monday–Friday<br>9:00 AM – 5:00 PM PST</p>
<!-- /wp:paragraph -->';

		if ( ! str_contains( $post->post_content, $disclaimer_marker ) ) {
			return;
		}

		wp_update_post(
			array(
				'ID'           => $post->ID,
				'post_content' => str_replace( $disclaimer_marker, $disclaimer_marker . $contact_block, $post->post_content ),
			)
		);
	}

	public function ensure_footer_navigation_layout() {
		$navigation = get_posts(
			array(
				'post_type'      => 'wp_navigation',
				'name'           => 'footer-menu',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
			)
		);

		if ( ! empty( $navigation ) ) {
			$post        = $navigation[0];
			$updated_nav = $post->post_content;
			$nav_changed = false;

			if ( str_contains( $updated_nav, 'navigation-submenu' ) ) {
				$updated_nav = preg_replace(
					'/\n\n<!-- wp:navigation-submenu \{[^}]+\} \/-->\n\n/',
					"\n\n<!-- wp:navigation-link {\"type\":\"page\",\"kind\":\"post-type\"} /-->\n\n",
					$updated_nav
				);
				$nav_changed = true;
			}

			if ( $nav_changed ) {
				wp_update_post(
					array(
						'ID'           => $post->ID,
						'post_content' => $updated_nav,
					)
				);
			}
		}

		$footer_parts = get_posts(
			array(
				'post_type'      => 'wp_template_part',
				'name'           => 'footer',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
			)
		);

		if ( empty( $footer_parts ) ) {
			return;
		}

		$post           = $footer_parts[0];
		$vertical_nav   = '<!-- wp:navigation {"ref":469,"textColor":"muted-foreground","overlayMenu":"never","layout":{"type":"flex","orientation":"vertical"}} /-->';
		$horizontal_nav = '<!-- wp:navigation {"ref":469,"textColor":"muted-foreground","overlayMenu":"never","className":"molecule-footer-nav","layout":{"type":"flex","orientation":"horizontal","justifyContent":"left","flexWrap":"nowrap"}} /-->';
		$updated_footer = $post->post_content;

		if ( str_contains( $updated_footer, $vertical_nav ) ) {
			$updated_footer = str_replace( $vertical_nav, $horizontal_nav, $updated_footer );
		} elseif ( ! str_contains( $updated_footer, 'molecule-footer-nav' ) && str_contains( $updated_footer, '"ref":469' ) ) {
			$updated_footer = preg_replace(
				'/<!-- wp:navigation \{[^}]*"ref":469[^}]*\} \/-->/',
				$horizontal_nav,
				$updated_footer,
				1
			);
		}

		if ( $updated_footer !== $post->post_content ) {
			wp_update_post(
				array(
					'ID'           => $post->ID,
					'post_content' => $updated_footer,
				)
			);
		}
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
		static $did_render_php_header = false;

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

		// Some templates/content paths can include the header template-part more
		// than once. Ensure our fixed top-nav header is emitted only once.
		if ( $did_render_php_header ) {
			return '';
		}

		$template = get_template_directory() . '/template-parts/header.php';

		if ( ! file_exists( $template ) ) {
			return $pre_render;
		}

		ob_start();
		include $template;
		$did_render_php_header = true;
		return ob_get_clean();
	}

	/**
	 * Hide the block footer on guest My Account (login/register/lost password)
	 * so the page matches a focused auth layout.
	 *
	 * @param string|null $pre_render Existing pre-render override.
	 * @param array       $block      Parsed block data.
	 * @return string|null
	 */
	public function suppress_footer_guest_account( $pre_render, $block ) {
		if ( 'core/template-part' !== ( $block['blockName'] ?? '' ) ) {
			return $pre_render;
		}

		if ( 'footer' !== ( $block['attrs']['slug'] ?? '' ) ) {
			return $pre_render;
		}

		if ( is_admin() ) {
			return $pre_render;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return $pre_render;
		}

		if ( function_exists( 'is_account_page' ) && is_account_page() && ! is_user_logged_in() ) {
			return '';
		}

		return $pre_render;
	}
}

Core::get_instance();
