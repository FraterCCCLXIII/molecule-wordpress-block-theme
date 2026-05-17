<?php
/**
 * Optional subscription via Brevo (WordPress plugin slug mailin).
 *
 * @package MoleculeResearchGate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MRG_Brevo_Newsletter
 */
class MRG_Brevo_Newsletter {

	/**
	 * Parse comma / whitespace-separated Brevo list IDs from settings.
	 *
	 * @return int[]
	 */
	public static function parse_list_ids( string $raw ): array {
		$raw = trim( $raw );
		if ( '' === $raw ) {
			return array();
		}
		$parts = preg_split( '/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY );
		if ( ! is_array( $parts ) ) {
			return array();
		}
		$out = array();
		foreach ( $parts as $p ) {
			$id = absint( $p );
			if ( $id > 0 ) {
				$out[] = $id;
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * Whether MRG newsletter UI should load for the current visitor.
	 *
	 * @param array<string, mixed> $settings Parsed MRG settings.
	 */
	public static function should_offer_opt_in_ui( array $settings ): bool {
		if ( empty( $settings['brevo_newsletter_optin_enabled'] ) ) {
			return false;
		}
		if ( array() === self::parse_list_ids( (string) ( $settings['brevo_newsletter_list_id'] ?? '' ) ) ) {
			return false;
		}
		if ( ! class_exists( 'SIB_Manager' ) || ! class_exists( 'SIB_API_Manager' ) ) {
			return false;
		}
		if ( ! SIB_Manager::is_api_key_set() ) {
			return false;
		}
		return true;
	}

	/**
	 * Add/update contact on Brevo lists (simple subscribe — same mode as non-DOI Brevo forms).
	 *
	 * @param array<string, mixed> $settings Parsed MRG settings.
	 * @return array{ok:bool, reason?:string, code?:string}
	 */
	public static function subscribe_user( int $user_id, array $settings ): array {
		if ( empty( $settings['brevo_newsletter_optin_enabled'] ) ) {
			return array( 'ok' => false, 'reason' => 'disabled' );
		}

		$list_ids = self::parse_list_ids( (string) ( $settings['brevo_newsletter_list_id'] ?? '' ) );
		if ( array() === $list_ids ) {
			return array( 'ok' => false, 'reason' => 'no_list' );
		}

		if ( ! class_exists( 'SIB_Manager' ) || ! class_exists( 'SIB_API_Manager' ) ) {
			return array( 'ok' => false, 'reason' => 'no_brevo_plugin' );
		}

		if ( ! SIB_Manager::is_api_key_set() ) {
			return array( 'ok' => false, 'reason' => 'no_api_key' );
		}

		$user = get_userdata( $user_id );
		if ( ! $user instanceof WP_User || ! is_email( $user->user_email ) ) {
			return array( 'ok' => false, 'reason' => 'bad_user' );
		}

		$info = self::build_contact_attributes( $user_id );

		$result = SIB_API_Manager::create_subscriber( $user->user_email, $list_ids, $info, 'simple', null );
		$result = is_string( $result ) ? $result : '';

		if ( in_array( $result, array( 'success', 'already_exist' ), true ) ) {
			update_user_meta( $user_id, MRG_User_Profile::META_BREVO_NEWSLETTER_OPT_IN_AT, time() );
			return array( 'ok' => true, 'code' => $result );
		}

		return array(
			'ok'     => false,
			'reason' => 'api',
			'code'   => $result,
		);
	}

	/**
	 * Map WP user + MRG profile fields to common Brevo attributes (skipped if empty).
	 *
	 * @return array<string, string>
	 */
	private static function build_contact_attributes( int $user_id ): array {
		$info = array();

		$fn = get_user_meta( $user_id, 'first_name', true );
		$ln = get_user_meta( $user_id, 'last_name', true );
		$fn = is_string( $fn ) ? trim( $fn ) : '';
		$ln = is_string( $ln ) ? trim( $ln ) : '';

		if ( '' === $fn && '' === $ln ) {
			$u = get_userdata( $user_id );
			if ( $u && is_string( $u->display_name ) && '' !== trim( $u->display_name ) ) {
				$parts = preg_split( '/\s+/', trim( $u->display_name ), 2 );
				if ( is_array( $parts ) && isset( $parts[0] ) ) {
					$fn = $parts[0];
					$ln = isset( $parts[1] ) ? $parts[1] : '';
				}
			}
		}

		if ( '' !== $fn ) {
			$info['FIRSTNAME'] = $fn;
		}
		if ( '' !== $ln ) {
			$info['LASTNAME'] = $ln;
		}

		$profile = MRG_User_Profile::get_profile_for_user( $user_id );
		if ( '' !== trim( $profile['org_name'] ) ) {
			$info['COMPANY'] = sanitize_text_field( $profile['org_name'] );
		}

		return $info;
	}
}
