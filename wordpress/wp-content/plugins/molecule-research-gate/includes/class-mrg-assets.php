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

		$user_id    = get_current_user_id();
		$needs_prof = $this->gate->current_user_requires_profile_modal();
		$offer_copy = $this->get_welcome_offer_strings();

		$brevo_ready    = MRG_Brevo_Newsletter::should_offer_opt_in_ui( $this->settings );
		$already_opt_in = $user_id && (bool) get_user_meta( $user_id, MRG_User_Profile::META_BREVO_NEWSLETTER_OPT_IN_AT, true );

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
				'myAccountAuthMode'        => $this->gate->get_guest_auth_query_value(),
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
					'verifiedTitle'        => $offer_copy['verifiedTitle'],
					'verifiedIntro'        => $offer_copy['verifiedIntro'],
					'rewardTitle'          => $offer_copy['rewardTitle'],
					'rewardIntro'          => $offer_copy['rewardIntro'],
					'newsletterClaimCta'   => $offer_copy['newsletterClaimCta'],
					'newsletterClaiming'   => __( 'Saving…', 'molecule-research-gate' ),
					'newsletterUnavailable' => __( 'Newsletter signup is not available right now. You can skip and continue shopping without a discount code.', 'molecule-research-gate' ),
					'newsletterSubscribeFailed' => __( 'We could not add you to the newsletter. Please try again or skip to continue without a discount code.', 'molecule-research-gate' ),
					'verifiedSkipCta'      => $offer_copy['verifiedSkipCta'],
					'copyCodeCta'          => __( 'Copy code', 'molecule-research-gate' ),
					'copyCodeDone'         => __( 'Copied', 'molecule-research-gate' ),
					'copyCodeFailed'       => __( 'Could not copy', 'molecule-research-gate' ),
					'shopCta'              => __( 'Continue shopping', 'molecule-research-gate' ),
					'checkboxHtml'         => '', // Filled below.
					'newsletterOptInLabel' => $offer_copy['newsletterOptInLabel'],
				),
				'brand'                    => array(
					'eyebrow'        => (string) $this->settings['brand_eyebrow'],
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
				'newsletterOptIn'          => array(
					'stepEnabled'       => ! empty( $this->settings['welcome_optin_step_enabled'] ),
					'canSubscribe'      => $brevo_ready,
					'alreadySubscribed' => $already_opt_in,
					'restUrl'           => esc_url_raw( rest_url( 'molecule-research-gate/v1/newsletter-opt-in' ) ),
				),
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

		$backdrop_pct = min( 100, max( 0, (int) ( $this->settings['modal_backdrop_opacity'] ?? 55 ) ) );
		$overlay_pct  = min( 100, max( 0, (int) ( $this->settings['brand_image_overlay_opacity'] ?? 85 ) ) );
		$mrg_surface  = sprintf(
			'--mrg-backdrop-pct: %d; --mrg-brand-overlay-pct: %d;',
			$backdrop_pct,
			$overlay_pct
		);
		?>
		<div id="mrg-gate" class="mrg-gate" style="<?php echo esc_attr( $mrg_surface ); ?>" hidden data-mrg-gate-root role="dialog" aria-modal="true" aria-labelledby="mrg-gate-title-auth" aria-hidden="true">
			<div class="mrg-gate__backdrop" data-mrg-backdrop></div>
			<div class="mrg-gate__shell">
				<div class="mrg-gate__brand-panel" aria-hidden="true" data-mrg-brand-panel>
					<p class="mrg-gate__eyebrow" data-mrg-brand-eyebrow></p>
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
							<div class="mrg-gate__newsletter" data-mrg-newsletter-wrap hidden>
								<label class="mrg-gate__check">
									<input type="checkbox" name="mrg_newsletter_opt_in" value="1" data-mrg-newsletter-checkbox autocomplete="off" />
									<span data-mrg-newsletter-label></span>
								</label>
							</div>
							<p class="mrg-gate__error" data-mrg-newsletter-error role="alert" hidden></p>
							<div class="mrg-gate__actions" data-mrg-verified-actions>
								<button type="button" class="mrg-gate__button" data-mrg-newsletter-claim disabled></button>
								<button type="button" class="mrg-gate__button mrg-gate__button--secondary" data-mrg-verified-skip></button>
							</div>
						</div>

						<div class="mrg-gate__state" data-mrg-state="reward" hidden>
							<h2 id="mrg-gate-title-reward" class="mrg-gate__title" data-mrg-reward-title></h2>
							<p class="mrg-gate__intro" data-mrg-reward-intro></p>
							<div class="mrg-gate__discount" data-mrg-discount-wrap hidden>
								<div class="mrg-gate__discount-code" data-mrg-discount-code></div>
								<div class="mrg-gate__discount-actions">
									<button type="button" class="mrg-gate__button mrg-gate__button--secondary" data-mrg-copy-code></button>
								</div>
							</div>
							<a href="#" class="mrg-gate__button mrg-gate__button--link" data-mrg-verified-shop></a>
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

	/**
	 * Newsletter / welcome-code copy from settings (with defaults).
	 *
	 * @return array{verifiedTitle:string,verifiedIntro:string,rewardTitle:string,rewardIntro:string,newsletterClaimCta:string,newsletterOptInLabel:string,verifiedSkipCta:string}
	 */
	private function get_welcome_offer_strings(): array {
		$label = trim( (string) ( $this->settings['welcome_offer_label'] ?? '' ) );

		$verified_title = trim( (string) ( $this->settings['welcome_optin_title'] ?? '' ) );
		if ( '' === $verified_title ) {
			$verified_title = __( 'Claim your coupon', 'molecule-research-gate' );
		}

		$verified_intro = trim( (string) ( $this->settings['welcome_optin_intro'] ?? '' ) );
		if ( '' === $verified_intro ) {
			$verified_intro = __( 'Subscribe to the newsletter to get discount code', 'molecule-research-gate' );
		}

		$checkbox_label = trim( (string) ( $this->settings['welcome_optin_checkbox_label'] ?? '' ) );
		if ( '' === $checkbox_label ) {
			$checkbox_label = '' !== $label
				? sprintf(
					/* translators: %s: offer label, e.g. 10% off */
					__( 'Yes — email me product updates and promotions to receive my %s code.', 'molecule-research-gate' ),
					$label
				)
				: __( 'Yes — email me product updates and promotions to receive my welcome discount code.', 'molecule-research-gate' );
		}

		$claim_cta = trim( (string) ( $this->settings['welcome_optin_claim_cta'] ?? '' ) );
		if ( '' === $claim_cta ) {
			$claim_cta = __( 'Claim your coupon', 'molecule-research-gate' );
		}

		$skip_cta = trim( (string) ( $this->settings['welcome_optin_skip_cta'] ?? '' ) );
		if ( '' === $skip_cta ) {
			$skip_cta = __( 'Skip', 'molecule-research-gate' );
		}

		$reward_title = trim( (string) ( $this->settings['reward_title'] ?? '' ) );
		if ( '' === $reward_title ) {
			$reward_title = '' !== $label
				? sprintf(
					/* translators: %s: offer label, e.g. 10% off */
					__( 'Your %s code', 'molecule-research-gate' ),
					$label
				)
				: __( 'Your welcome code', 'molecule-research-gate' );
		}

		$reward_intro = trim( (string) ( $this->settings['reward_intro'] ?? '' ) );
		if ( '' === $reward_intro ) {
			$reward_intro = __( 'Copy your code below and apply it at checkout on your first order.', 'molecule-research-gate' );
		}

		return array(
			'verifiedTitle'        => $verified_title,
			'verifiedIntro'        => $verified_intro,
			'rewardTitle'          => $reward_title,
			'rewardIntro'          => $reward_intro,
			'newsletterClaimCta'   => $claim_cta,
			'newsletterOptInLabel' => $checkbox_label,
			'verifiedSkipCta'      => $skip_cta,
		);
	}
}
