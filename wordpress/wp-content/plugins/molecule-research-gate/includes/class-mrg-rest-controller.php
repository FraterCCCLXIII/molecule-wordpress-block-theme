<?php
/**
 * REST API: research profile save.
 *
 * @package MoleculeResearchGate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MRG_REST_Controller
 */
class MRG_REST_Controller extends WP_REST_Controller {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->namespace = 'molecule-research-gate/v1';
		$this->rest_base = 'profile';
	}

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_profile' ),
					'permission_callback' => array( $this, 'can_edit_profile' ),
					'args'                => array(
						'entity_type'            => array(
							'type'     => 'string',
							'required' => true,
						),
						'research_setting'       => array(
							'type'     => 'string',
							'required' => true,
						),
						'research_setting_other' => array(
							'type'     => 'string',
							'required' => false,
						),
						'org_name'               => array(
							'type'     => 'string',
							'required' => false,
						),
						'role_title'             => array(
							'type'     => 'string',
							'required' => false,
						),
					),
				),
			)
		);
	}

	/**
	 * @return bool
	 */
	public function can_edit_profile(): bool {
		return is_user_logged_in();
	}

	/**
	 * Save profile.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function save_profile( WP_REST_Request $request ) {
		$user_id = get_current_user_id();
		$data    = array(
			'entity_type'            => $request->get_param( 'entity_type' ),
			'research_setting'       => $request->get_param( 'research_setting' ),
			'research_setting_other' => $request->get_param( 'research_setting_other' ),
			'org_name'               => $request->get_param( 'org_name' ),
			'role_title'             => $request->get_param( 'role_title' ),
		);

		$validated = MRG_User_Profile::validate_profile_payload( $data );
		if ( ! $validated['ok'] ) {
			return new WP_Error(
				'mrg_invalid_profile',
				implode( ' ', $validated['errors'] ),
				array( 'status' => 400 )
			);
		}

		$settings = get_option( MRG_OPTION_KEY, array() );
		$policy   = '';
		if ( is_array( $settings ) && ! empty( $settings['policy_version'] ) ) {
			$policy = (string) $settings['policy_version'];
		}

		MRG_User_Profile::save_profile( $user_id, $validated['normalized'], $policy );

		$settings  = get_option( MRG_OPTION_KEY, array() );
		$coupon    = isset( $settings['welcome_coupon_code'] ) ? (string) $settings['welcome_coupon_code'] : '';
		$shop_url  = function_exists( 'wc_get_page_permalink' ) ? (string) wc_get_page_permalink( 'shop' ) : home_url( '/' );
		$cart_url  = function_exists( 'wc_get_cart_url' ) ? (string) wc_get_cart_url() : $shop_url;
		$apply_url = $coupon ? add_query_arg( 'mrg_apply_coupon', rawurlencode( $coupon ), $cart_url ) : $cart_url;

		return new WP_REST_Response(
			array(
				'success'            => true,
				'profile'            => MRG_User_Profile::get_profile_for_user( $user_id ),
				'welcomeCoupon'      => $coupon,
				'shopUrl'            => $shop_url,
				'cartUrlWithCoupon'  => $apply_url,
				'couponHelpTextKey'  => $coupon ? 'checkout' : 'none',
			),
			200
		);
	}
}
