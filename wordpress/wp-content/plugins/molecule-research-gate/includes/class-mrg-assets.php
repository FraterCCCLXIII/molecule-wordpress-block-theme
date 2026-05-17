<?php
/**
 * Front-end assets and modal markup.
 *
 * @package MoleculeResearchGate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MRG_Assets
 */
class MRG_Assets {

	/**
	 * @var array<string, mixed>
	 */
	private $settings;

	/**
	 * @var MRG_Gate
	 */
	private $gate;

	/**
	 * @param array<string, mixed> $settings Settings.
	 * @param MRG_Gate             $gate Gate helper.
	 */
	public function __construct( array $settings, MRG_Gate $gate ) {
		$this->settings = $settings;
		$this->gate     = $gate;
	}

	/**
	 * Hooks.
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 20 );
		add_action( 'wp_footer', array( $this, 'render_modal' ), 5 );
		add_action( 'wp_footer', array( $this, 'render_noscript_notice' ), 30 );
	}

	/**
	 * Enqueue scripts/styles on front.
	 */
	public function enqueue(): void {
		if ( is_admin() || ! $this->gate->woocommerce_available() ) {
			return;
		}

		wp_register_style(
			'mrg-gate',
			MRG_PLUGIN_URL . 'assets/css/gate.css',
			array(),
			MRG_VERSION
		);
		wp_register_script(
			'mrg-gate',
			MRG_PLUGIN_URL . 'assets/js/gate.js',
			array(),
			MRG_VERSION,
			true
		);

		wp_enqueue_style( 'mrg-gate' );
		wp_enqueue_script( 'mrg-gate' );

		$user_id   = get_current_user_id();
		$needs_prof = $this->gate->current_user_requires_profile_modal();

		wp_localize_script(
			'mrg-gate',
			'moleculeResearchGate',
			array(
				'restUrl'                  => esc_url_raw( rest_url( 'molecule-research-gate/v1/profile' ) ),
				'nonce'                    => wp_create_nonce( 'wp_rest' ),
				'isLoggedIn'               => is_user_logged_in(),
				'requiresProfile'          => $needs_prof,
				'profile'                  => $user_id ? MRG_User_Profile::get_profile_for_user( $user_id ) : array(),
				'myAccountUrl'             => esc_url_raw( (string) wc_get_page_permalink( 'myaccount' ) ),
				'shopUrl'                  => esc_url_raw( (string) wc_get_page_permalink( 'shop' ) ),
				'cartUrl'                  => esc_url_raw( (string) wc_get_cart_url() ),
				'strings'                  => array(
					'authTitle'           => __( 'Sign in or create your account.', 'molecule-research-gate' ),
					'authIntro'           => __( 'Account access is required to browse the product catalog.', 'molecule-research-gate' ),
					'authSubmit'          => __( 'Sign in or create account', 'molecule-research-gate' ),
					'profileTitle'        => __( 'Verify your research entity.', 'molecule-research-gate' ),
					'profileIntro'        => __( 'Complete this final step so we can keep the catalog limited to lawful laboratory research use.', 'molecule-research-gate' ),
					'profileSubmit'       => __( 'Save & Continue', 'molecule-research-gate' ),
					'profileSaving'       => __( 'Saving…', 'molecule-research-gate' ),
					'verifiedTitle'       => __( 'Research access verified.', 'molecule-research-gate' ),
					'verifiedIntro'       => __( 'Use your welcome code at checkout on your first order.', 'molecule-research-gate' ),
					'shopCta'             => __( 'Continue shopping', 'molecule-research-gate' ),
					'cartCta'             => __( 'Go to cart with coupon', 'molecule-research-gate' ),
					'checkboxHtml'        => '', // Filled below.
				),
				'brand'                    => array(
					'eyebrow'        => (string) $this->settings['brand_eyebrow'],
					'title'          => (string) $this->settings['brand_title'],
					'proof1'         => (string) $this->settings['proof_line_1'],
					'proof2'         => (string) $this->settings['proof_line_2'],
					'proof3'         => (string) $this->settings['proof_line_3'],
					'logoUrl'        => (string) $this->settings['logo_url'],
					'panelImageUrl'  => (string) ( $this->settings['brand_panel_image_url'] ?? '' ),
					'blogName'       => get_bloginfo( 'name' ),
				),
				'welcomeCoupon'            => (string) $this->settings['welcome_coupon_code'],
				'supportEmail'             => sanitize_email( (string) $this->settings['support_email'] ),
				'researchOtherValue'       => MRG_User_Profile::RESEARCH_OTHER_VALUE,
				'gateUrlPrefixes'          => array_values( $this->gate->get_link_match_prefixes() ),
			)
		);

		// Checkbox copy with links (safe HTML for wp_kses in JS we use text - actually pass as HTML and use innerHTML carefully).
		$terms   = esc_url( (string) $this->settings['terms_url'] );
		$indemn  = esc_url( (string) $this->settings['indemnity_url'] );
		$fine    = sprintf(
			/* translators: %s: support email */
			__( 'Questions? Please email %s', 'molecule-research-gate' ),
			'<a href="mailto:' . esc_attr( (string) $this->settings['support_email'] ) . '" class="mrg-gate__link">' . esc_html( (string) $this->settings['support_email'] ) . '</a>'
		);

		$checkbox_inner = sprintf(
			/* translators: 1: terms link, 2: indemnity link */
			__(
				'All products are sold for Research Use Only. To continue you confirm that you are a qualified research professional aged 21 or older. You acknowledge that all products in this order are intended for laboratory research purposes only, are not for human or animal consumption, and you agree to the %1$s and %2$s.',
				'molecule-research-gate'
			),
			'<a href="' . $terms . '" target="_blank" rel="noopener noreferrer" class="mrg-gate__link">' . esc_html__( 'Terms & Conditions', 'molecule-research-gate' ) . '</a>',
			'<a href="' . $indemn . '" target="_blank" rel="noopener noreferrer" class="mrg-gate__link">' . esc_html__( 'Indemnity Waiver', 'molecule-research-gate' ) . '</a>'
		);

		wp_add_inline_script(
			'mrg-gate',
			'window.moleculeResearchGate.strings.checkboxHtml = ' . wp_json_encode( $checkbox_inner ) . ';
			window.moleculeResearchGate.strings.finePrintHtml = ' . wp_json_encode( $fine ) . ';',
			'before'
		);
	}

	/**
	 * Dialog root (one shell; states toggled in JS).
	 */
	public function render_modal(): void {
		if ( is_admin() || ! $this->gate->woocommerce_available() ) {
			return;
		}
		?>
		<div id="mrg-gate" class="mrg-gate" hidden data-mrg-gate-root role="dialog" aria-modal="true" aria-labelledby="mrg-gate-title-auth" aria-hidden="true">
			<div class="mrg-gate__backdrop" data-mrg-backdrop></div>
			<div class="mrg-gate__shell">
				<div class="mrg-gate__brand-panel" aria-hidden="true" data-mrg-brand-panel>
					<p class="mrg-gate__eyebrow" data-mrg-brand-eyebrow></p>
					<div class="mrg-gate__brand-copy">
						<h2 class="mrg-gate__brand-title" data-mrg-brand-title></h2>
					</div>
					<div class="mrg-gate__proof" data-mrg-proof></div>
				</div>
				<div class="mrg-gate__panel">
					<div class="mrg-gate__inner">
						<div class="mrg-gate__logo-wrap" data-mrg-logo-wrap hidden>
							<img src="" alt="" class="mrg-gate__logo" width="132" height="40" data-mrg-logo />
						</div>

						<div class="mrg-gate__state" data-mrg-state="auth" hidden>
							<h2 id="mrg-gate-title-auth" class="mrg-gate__title" data-mrg-auth-title></h2>
							<p class="mrg-gate__intro" data-mrg-auth-intro></p>
							<form class="mrg-gate__form" data-mrg-auth-form novalidate>
								<label class="mrg-gate__check">
									<input type="checkbox" name="access_compliance_confirmed" value="1" required data-mrg-auth-checkbox />
									<span data-mrg-auth-checkbox-label></span>
								</label>
								<button type="submit" class="mrg-gate__button" data-mrg-auth-submit disabled></button>
							</form>
						</div>

						<div class="mrg-gate__state" data-mrg-state="profile" hidden>
							<h2 class="mrg-gate__title" id="mrg-profile-title" data-mrg-profile-title></h2>
							<p class="mrg-gate__intro" data-mrg-profile-intro></p>
							<form class="mrg-gate__form" data-mrg-profile-form novalidate>
								<div class="mrg-gate__fields">
									<div class="mrg-gate__field">
										<label class="mrg-gate__field-label" for="mrg-entity-type"><?php esc_html_e( 'Organization / Lab Type', 'molecule-research-gate' ); ?> <span class="mrg-gate__required" aria-hidden="true">*</span></label>
										<select id="mrg-entity-type" class="mrg-gate__select" name="entity_type" required data-mrg-field-entity>
											<option value="" disabled selected><?php esc_html_e( 'Select…', 'molecule-research-gate' ); ?></option>
											<?php foreach ( MRG_User_Profile::get_entity_types() as $label ) : ?>
												<option value="<?php echo esc_attr( $label ); ?>"><?php echo esc_html( $label ); ?></option>
											<?php endforeach; ?>
										</select>
									</div>
									<div class="mrg-gate__field">
										<label class="mrg-gate__field-label" for="mrg-research-setting"><?php esc_html_e( 'Field of Qualified Research', 'molecule-research-gate' ); ?> <span class="mrg-gate__required" aria-hidden="true">*</span></label>
										<select id="mrg-research-setting" class="mrg-gate__select" name="research_setting" required data-mrg-field-research>
											<option value="" disabled selected><?php esc_html_e( 'Select…', 'molecule-research-gate' ); ?></option>
											<?php foreach ( MRG_User_Profile::get_research_settings() as $label ) : ?>
												<option value="<?php echo esc_attr( $label ); ?>"><?php echo esc_html( $label ); ?></option>
											<?php endforeach; ?>
										</select>
									</div>
									<div class="mrg-gate__field" data-mrg-other-wrap hidden>
										<label class="mrg-gate__field-label" for="mrg-research-other"><?php esc_html_e( 'Specify other qualified research', 'molecule-research-gate' ); ?> <span class="mrg-gate__required" aria-hidden="true">*</span></label>
										<input type="text" id="mrg-research-other" class="mrg-gate__input" name="research_setting_other" autocomplete="off" data-mrg-field-research-other disabled />
									</div>
									<div class="mrg-gate__field">
										<label class="mrg-gate__field-label" for="mrg-org-name"><?php esc_html_e( 'Organization / Lab Name', 'molecule-research-gate' ); ?></label>
										<input type="text" id="mrg-org-name" class="mrg-gate__input" name="org_name" autocomplete="organization" data-mrg-field-org />
									</div>
									<div class="mrg-gate__field">
										<label class="mrg-gate__field-label" for="mrg-role-title"><?php esc_html_e( 'Role / Title', 'molecule-research-gate' ); ?></label>
										<input type="text" id="mrg-role-title" class="mrg-gate__input" name="role_title" autocomplete="organization-title" data-mrg-field-role />
									</div>
								</div>
								<p class="mrg-gate__error" data-mrg-profile-error role="alert" hidden></p>
								<button type="submit" class="mrg-gate__button" data-mrg-profile-submit></button>
							</form>
						</div>

						<div class="mrg-gate__state" data-mrg-state="verified" hidden>
							<h2 id="mrg-gate-title-verified" class="mrg-gate__title" data-mrg-verified-title></h2>
							<p class="mrg-gate__intro" data-mrg-verified-intro></p>
							<div class="mrg-gate__discount" data-mrg-discount-wrap hidden>
								<div class="mrg-gate__discount-code" data-mrg-discount-code></div>
							</div>
							<a href="#" class="mrg-gate__button mrg-gate__button--link" data-mrg-verified-shop></a>
							<a href="#" class="mrg-gate__button mrg-gate__button--secondary mrg-gate__button--link" data-mrg-verified-cart hidden></a>
						</div>

						<p class="mrg-gate__fine-print" data-mrg-fine-print></p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * No-JS hint when profile required.
	 */
	public function render_noscript_notice(): void {
		if ( is_admin() || ! $this->gate->current_user_requires_profile_modal() ) {
			return;
		}
		echo '<noscript><div class="mrg-gate-noscript">' . esc_html__( 'JavaScript is required to complete research verification on this site. Please enable JavaScript and reload the page.', 'molecule-research-gate' ) . '</div></noscript>';
	}
}
