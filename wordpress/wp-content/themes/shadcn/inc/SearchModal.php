<?php

namespace Shadcn;

use Shadcn\Traits\SingletonTrait;
use WP_Query;
use WP_REST_Request;
use WP_REST_Server;

class SearchModal {
	use SingletonTrait;

	private const REST_NAMESPACE = 'shadcn/v1';
	private const REST_ROUTE = '/search';

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_front_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_modal_markup' ) );
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function enqueue_front_assets() {
		if ( is_admin() ) {
			return;
		}

		$script_path = get_template_directory() . '/assets/js/search-modal.js';
		$script_ver  = file_exists( $script_path ) ? (string) filemtime( $script_path ) : wp_get_theme()->get( 'Version' );

		wp_enqueue_script(
			'shadcn-search-modal',
			get_template_directory_uri() . '/assets/js/search-modal.js',
			array(),
			$script_ver,
			true
		);

		wp_localize_script(
			'shadcn-search-modal',
			'shadcnSearchModal',
			array(
				'endpoint'      => esc_url_raw( rest_url( self::REST_NAMESPACE . self::REST_ROUTE ) ),
				'minQueryChars' => 2,
				'labels'        => array(
					'placeholder' => __( 'Search products, pages, and articles...', 'shadcn' ),
					'startTyping' => __( 'Start typing to search...', 'shadcn' ),
					'noResults'   => __( 'No results found.', 'shadcn' ),
					'close'       => __( 'Close search', 'shadcn' ),
				),
			)
		);
	}

	public function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'search_items' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'q' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * @return array<string>
	 */
	private function get_searchable_post_types() {
		$post_types = array( 'page', 'post' );

		if ( post_type_exists( 'product' ) ) {
			array_unshift( $post_types, 'product' );
		}

		return $post_types;
	}

	/**
	 * @param WP_REST_Request $request Current request.
	 * @return array<string, mixed>
	 */
	public function search_items( WP_REST_Request $request ) {
		$query = trim( (string) $request->get_param( 'q' ) );
		if ( strlen( $query ) < 2 ) {
			return array(
				'results' => array(),
			);
		}

		$wp_query = new WP_Query(
			array(
				's'                   => $query,
				'post_type'           => $this->get_searchable_post_types(),
				'post_status'         => 'publish',
				'posts_per_page'      => 8,
				'ignore_sticky_posts' => true,
				'orderby'             => 'relevance',
			)
		);

		$results = array();
		while ( $wp_query->have_posts() ) {
			$wp_query->the_post();
			$post_id      = get_the_ID();
			$post_type    = get_post_type( $post_id );
			$label        = $this->map_result_label( $post_type );
			$title        = trim( (string) get_the_title( $post_id ) );
			$excerpt      = trim( wp_strip_all_tags( (string) get_the_excerpt( $post_id ) ) );
			$image        = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
			$results[]    = array(
				'type'    => $label,
				'title'   => '' !== $title ? $title : __( '(Untitled)', 'shadcn' ),
				'url'     => get_permalink( $post_id ),
				'excerpt' => $excerpt,
				'image'   => $image ? $image : '',
			);
		}
		wp_reset_postdata();

		return array(
			'results' => $results,
		);
	}

	/**
	 * @param string $post_type Post type slug.
	 * @return string
	 */
	private function map_result_label( $post_type ) {
		if ( 'product' === $post_type ) {
			return 'product';
		}

		if ( 'post' === $post_type ) {
			return 'article';
		}

		return 'page';
	}

	public function render_modal_markup() {
		if ( is_admin() ) {
			return;
		}
		?>
		<div class="molecule-search-modal" data-search-modal hidden>
			<div class="molecule-search-modal__backdrop" data-search-modal-close></div>
			<div class="molecule-search-modal__container" role="dialog" aria-modal="true" aria-labelledby="molecule-search-modal-title">
				<h2 id="molecule-search-modal-title" class="screen-reader-text"><?php esc_html_e( 'Site search', 'shadcn' ); ?></h2>
				<div class="molecule-search-modal__header">
					<input
						type="search"
						class="molecule-search-modal__input"
						data-search-modal-input
						placeholder="<?php esc_attr_e( 'Search products, pages, and articles...', 'shadcn' ); ?>"
						autocomplete="off"
					/>
					<button type="button" class="molecule-search-modal__close" data-search-modal-close aria-label="<?php esc_attr_e( 'Close search', 'shadcn' ); ?>">
						<svg viewBox="0 0 20 20" aria-hidden="true" focusable="false">
							<line x1="15" y1="5" x2="5" y2="15"></line>
							<line x1="5" y1="5" x2="15" y2="15"></line>
						</svg>
					</button>
				</div>
				<div class="molecule-search-modal__results" data-search-modal-results>
					<div class="molecule-search-modal__state"><?php esc_html_e( 'Start typing to search...', 'shadcn' ); ?></div>
				</div>
			</div>
		</div>
		<?php
	}
}

SearchModal::get_instance();
