<?php

namespace Shadcn;

use Shadcn\Traits\SingletonTrait;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class AffiliateApplication {
	use SingletonTrait;

	private const REST_NAMESPACE = 'shadcn/v1';
	private const REST_ROUTE     = '/affiliate-application';
	private const PAGE_SLUG      = 'affiliates';

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'init', array( $this, 'ensure_affiliates_page' ), 20 );
		add_action( 'init', array( $this, 'ensure_footer_affiliate_link' ), 21 );
		add_shortcode( 'molecule_affiliate_application_form', array( $this, 'render_form_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_ROUTE,
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_application' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'firstName'     => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'lastName'      => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'email'         => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_email',
					),
					'website'       => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
					),
					'promotionPlan' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
					'venmoUsername' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	public function ensure_affiliates_page() {
		$page = get_page_by_path( self::PAGE_SLUG );

		if ( ! $page ) {
			$page_id = wp_insert_post(
				array(
					'post_title'   => __( 'Affiliate Program', 'shadcn' ),
					'post_name'    => self::PAGE_SLUG,
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_content' => '',
				),
				true
			);

			if ( is_wp_error( $page_id ) ) {
				return;
			}

			$page = get_post( $page_id );
		}

		if ( $page instanceof \WP_Post ) {
			update_post_meta( $page->ID, '_wp_page_template', 'page-affiliates' );
		}
	}

	public function ensure_footer_affiliate_link() {
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
		if ( str_contains( $post->post_content, '/affiliates' ) ) {
			return;
		}

		$link_block   = "\n\n<!-- wp:navigation-link {\"label\":\"Affiliate Program\",\"url\":\"/affiliates\",\"kind\":\"custom\"} /-->\n\n";
		$about_marker = '<!-- wp:navigation-link {"label":"About Us","url":"/about","kind":"custom"} /-->';

		if ( str_contains( $post->post_content, $about_marker ) ) {
			$content = str_replace( $about_marker, $about_marker . $link_block, $post->post_content );
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

	public function enqueue_assets() {
		if ( ! is_page( self::PAGE_SLUG ) ) {
			return;
		}

		$script_path = get_template_directory() . '/assets/js/affiliate-application-form.js';
		$script_ver  = file_exists( $script_path ) ? (string) filemtime( $script_path ) : wp_get_theme()->get( 'Version' );

		wp_enqueue_script(
			'shadcn-affiliate-application-form',
			get_template_directory_uri() . '/assets/js/affiliate-application-form.js',
			array(),
			$script_ver,
			true
		);

		wp_localize_script(
			'shadcn-affiliate-application-form',
			'shadcnAffiliateApplication',
			array(
				'endpoint' => esc_url_raw( rest_url( self::REST_NAMESPACE . self::REST_ROUTE ) ),
				'labels'   => array(
					'error' => __( 'Unable to submit your application. Please try again.', 'shadcn' ),
				),
			)
		);
	}

	/** @return string */
	public function render_form_shortcode() {
		ob_start();
		?>
		<div class="molecule-affiliate-form-shell">
			<div class="molecule-affiliate-form__success" data-affiliate-form-success hidden>
				<h3><?php esc_html_e( 'Application Received', 'shadcn' ); ?></h3>
				<p><?php esc_html_e( 'Thanks for reaching out. We typically respond within two to three business days.', 'shadcn' ); ?></p>
			</div>
			<form class="molecule-affiliate-form" data-affiliate-application-form novalidate>
				<div class="molecule-affiliate-form__grid">
					<div class="molecule-affiliate-form__field">
						<label for="affiliate-first-name"><?php esc_html_e( 'First Name *', 'shadcn' ); ?></label>
						<input id="affiliate-first-name" name="firstName" type="text" required autocomplete="given-name">
					</div>
					<div class="molecule-affiliate-form__field">
						<label for="affiliate-last-name"><?php esc_html_e( 'Last Name *', 'shadcn' ); ?></label>
						<input id="affiliate-last-name" name="lastName" type="text" required autocomplete="family-name">
					</div>
				</div>
				<div class="molecule-affiliate-form__field">
					<label for="affiliate-email"><?php esc_html_e( 'Email Address *', 'shadcn' ); ?></label>
					<input id="affiliate-email" name="email" type="email" required autocomplete="email">
				</div>
				<div class="molecule-affiliate-form__field">
					<label for="affiliate-website"><?php esc_html_e( 'Website or social profile (optional)', 'shadcn' ); ?></label>
					<input id="affiliate-website" name="website" type="url" placeholder="https://">
				</div>
				<div class="molecule-affiliate-form__field">
					<label for="affiliate-promotion-plan"><?php esc_html_e( 'Tell us how you would introduce Molecule to your audience *', 'shadcn' ); ?></label>
					<textarea id="affiliate-promotion-plan" name="promotionPlan" rows="5" required minlength="20"></textarea>
					<p class="molecule-affiliate-form__char-count"><span data-promotion-plan-count>0</span> <?php esc_html_e( 'characters', 'shadcn' ); ?></p>
				</div>
				<div class="molecule-affiliate-form__field">
					<label for="affiliate-venmo-username"><?php esc_html_e( 'Venmo handle for payouts (optional)', 'shadcn' ); ?></label>
					<input id="affiliate-venmo-username" name="venmoUsername" type="text" placeholder="@username" autocomplete="off">
				</div>
				<div class="molecule-affiliate-form__error" data-affiliate-form-error hidden></div>
				<button type="submit" class="molecule-affiliate-form__submit wp-element-button"><?php esc_html_e( 'Send Application', 'shadcn' ); ?></button>
				<p class="molecule-affiliate-form__note"><?php esc_html_e( 'Most applications are reviewed within two to three business days.', 'shadcn' ); ?></p>
			</form>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/** @param WP_REST_Request $request */
	public function handle_application( WP_REST_Request $request ) {
		$first_name     = trim( (string) $request->get_param( 'firstName' ) );
		$last_name      = trim( (string) $request->get_param( 'lastName' ) );
		$email          = trim( (string) $request->get_param( 'email' ) );
		$website        = trim( (string) $request->get_param( 'website' ) );
		$promotion_plan = trim( (string) $request->get_param( 'promotionPlan' ) );
		$venmo_username = trim( (string) $request->get_param( 'venmoUsername' ) );
		if ( '' !== $venmo_username && '@' === $venmo_username[0] ) {
			$venmo_username = substr( $venmo_username, 1 );
		}

		if ( '' === $first_name || '' === $last_name ) {
			return new WP_Error( 'invalid_name', __( 'First and last name are required.', 'shadcn' ), array( 'status' => 400 ) );
		}
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', __( 'A valid email address is required.', 'shadcn' ), array( 'status' => 400 ) );
		}
		if ( strlen( $promotion_plan ) < 20 ) {
			return new WP_Error( 'invalid_promotion_plan', __( 'Please describe how you plan to promote Molecule.', 'shadcn' ), array( 'status' => 400 ) );
		}
		if ( '' !== $venmo_username && strlen( $venmo_username ) > 30 ) {
			return new WP_Error( 'invalid_venmo_username', __( 'Please enter a valid Venmo username.', 'shadcn' ), array( 'status' => 400 ) );
		}

		$recipient = apply_filters( 'shadcn_affiliate_application_recipient', 'contact@moleculepeptides.com' );
		$subject   = sprintf( __( 'New Affiliate Application: %s', 'shadcn' ), $first_name . ' ' . $last_name );
		$body      = implode(
			"\n",
			array(
				'New affiliate application received.',
				'',
				'Name: ' . $first_name . ' ' . $last_name,
				'Email: ' . $email,
				'Website / Social: ' . ( '' !== $website ? $website : 'Not provided' ),
				'Venmo Username: ' . ( '' !== $venmo_username ? '@' . $venmo_username : 'Not provided' ),
				'',
				'Promotion Plan:',
				$promotion_plan,
			)
		);

		$sent = wp_mail(
			$recipient,
			$subject,
			$body,
			array(
				'Content-Type: text/plain; charset=UTF-8',
				'Reply-To: ' . $first_name . ' ' . $last_name . ' <' . $email . '>',
			)
		);

		if ( ! $sent ) {
			return new WP_Error( 'mail_failed', __( 'Unable to submit your application right now.', 'shadcn' ), array( 'status' => 500 ) );
		}

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}
}

AffiliateApplication::get_instance();
