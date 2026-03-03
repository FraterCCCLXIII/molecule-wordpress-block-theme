<?php

namespace Shadcn;

use Shadcn\Traits\SingletonTrait;

class PromoPopup {
	use SingletonTrait;

	private const SCRIPT_HANDLE = 'shadcn-promo-popup';
	private const STYLE_HANDLE  = 'shadcn-promo-popup';

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_front_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_markup' ) );
	}

	public function enqueue_front_assets() {
		if ( ! $this->should_render() ) {
			return;
		}

		$script_path = get_template_directory() . '/assets/js/promo-popup.js';
		$style_path  = get_template_directory() . '/assets/css/promo-popup.css';
		$script_ver  = file_exists( $script_path ) ? (string) filemtime( $script_path ) : wp_get_theme()->get( 'Version' );
		$style_ver   = file_exists( $style_path ) ? (string) filemtime( $style_path ) : wp_get_theme()->get( 'Version' );

		wp_enqueue_style(
			self::STYLE_HANDLE,
			get_template_directory_uri() . '/assets/css/promo-popup.css',
			array(),
			$style_ver
		);

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			get_template_directory_uri() . '/assets/js/promo-popup.js',
			array(),
			$script_ver,
			true
		);

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'shadcnPromoPopup',
			array(
				'storageKey'  => 'shadcnAgeGateRememberedAt',
				'sessionKey'  => 'shadcnAgeGateAcceptedForSession',
				'expiresDays' => (int) apply_filters( 'shadcn_promo_popup_expiry_days', 7 ),
				'showDelayMs' => (int) apply_filters( 'shadcn_promo_popup_show_delay_ms', 600 ),
				'declineUrl'  => (string) apply_filters( 'shadcn_promo_popup_decline_url', 'https://www.google.com' ),
			)
		);
	}

	private function should_render() {
		if ( is_admin() ) {
			return false;
		}

		return (bool) apply_filters( 'shadcn_promo_popup_enabled', true );
	}

	public function render_markup() {
		if ( ! $this->should_render() ) {
			return;
		}
		?>
		<div class="molecule-promo-popup" data-promo-popup hidden>
			<div class="molecule-promo-popup__backdrop"></div>
			<div class="molecule-promo-popup__dialog" role="dialog" aria-modal="true" aria-labelledby="molecule-promo-popup-title">
				<div class="molecule-promo-popup__logo">
					<?php if ( function_exists( 'get_custom_logo' ) && has_custom_logo() ) : ?>
						<?php echo wp_kses_post( get_custom_logo() ); ?>
					<?php else : ?>
						<p class="molecule-promo-popup__fallback-logo"><?php bloginfo( 'name' ); ?></p>
					<?php endif; ?>
				</div>

				<div class="molecule-promo-popup__intro">
					<p id="molecule-promo-popup-title" class="molecule-promo-popup__title"><strong><?php esc_html_e( 'You must be at least 21 to visit this site.', 'shadcn' ); ?></strong></p>
					<p class="molecule-promo-popup__copy"><?php esc_html_e( 'By entering this site, you are accepting our Terms of Service', 'shadcn' ); ?></p>
				</div>

				<div class="molecule-promo-popup__actions">
					<button type="button" class="wp-element-button molecule-promo-popup__button molecule-promo-popup__button--decline" data-promo-popup-decline id="declineBtn">
						<?php esc_html_e( 'Decline', 'shadcn' ); ?>
					</button>
					<button type="button" class="wp-element-button molecule-promo-popup__button molecule-promo-popup__button--accept" data-promo-popup-accept id="acceptBtn">
						<?php esc_html_e( 'Accept', 'shadcn' ); ?>
					</button>
				</div>

				<div class="molecule-promo-popup__remember">
					<input id="rememberMe" class="molecule-promo-popup__remember-input" type="checkbox" data-promo-popup-remember>
					<label for="rememberMe" class="molecule-promo-popup__remember-label"><?php esc_html_e( 'Remember me', 'shadcn' ); ?></label>
				</div>

				<div class="molecule-promo-popup__disclaimer">
					<strong><?php esc_html_e( 'DISCLAIMER:', 'shadcn' ); ?></strong>
					<?php esc_html_e( 'All products sold by Molecule are strictly intended for laboratory research use only. They are not approved for human or animal consumption, or for any form of therapeutic or diagnostic use.', 'shadcn' ); ?>
					<br><br>
					<?php esc_html_e( 'We do not provide usage instructions, dosing guidelines, or any advice regarding the application of our products.', 'shadcn' ); ?>
					<br><br>
					<?php esc_html_e( 'This is a research supply company only.', 'shadcn' ); ?>
				</div>
			</div>
		</div>
		<?php
	}
}

PromoPopup::get_instance();
