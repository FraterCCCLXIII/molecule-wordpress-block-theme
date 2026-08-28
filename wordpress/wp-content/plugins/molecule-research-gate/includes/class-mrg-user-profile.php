<?php
/**
 * User meta helpers and validation enums.
 *
 * @package MoleculeResearchGate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MRG_User_Profile
 */
class MRG_User_Profile {

	public const META_RUO_ACCEPTED_AT         = 'molecule_ruo_accepted_at';
	public const META_RUO_POLICY_VERSION      = 'molecule_ruo_policy_version';
	public const META_ENTITY_TYPE             = 'molecule_entity_type';
	public const META_RESEARCH_SETTING        = 'molecule_research_setting';
	public const META_RESEARCH_SETTING_OTHER  = 'molecule_research_setting_other';
	public const META_ORG_NAME                = 'molecule_org_name';
	public const META_ROLE_TITLE              = 'molecule_role_title';
	public const META_PROFILE_COMPLETED_AT    = 'molecule_research_profile_completed_at';

	/** Unix time when user opted into marketing email via verified-step checkbox (Brevo sync attempted). */
	public const META_BREVO_NEWSLETTER_OPT_IN_AT = 'molecule_brevo_newsletter_opt_in_at';

	public const RESEARCH_OTHER_VALUE = 'Other Qualified Research (specify)';

	/**
	 * Allowed entity types (must match front-end selects).
	 *
	 * @return string[]
	 */
	public static function get_entity_types(): array {
		return array(
			'University or Academic Laboratory',
			'Government Laboratory or Agency',
			'Biotechnology or Pharmaceutical Company',
			'Contract Research Organization (CRO)',
			'Analytical or Testing Laboratory',
			'Industrial or Manufacturing R&D',
			'Registered Business Research Group',
			'Independent Laboratory',
		);
	}

	/**
	 * Allowed research setting values.
	 *
	 * @return string[]
	 */
	public static function get_research_settings(): array {
		return array(
			'Pharmacology / Drug Discovery',
			'Biochemistry & Molecular Biology',
			'Academic Research',
			'Contract Research Organization (CRO)',
			'Analytical Chemistry Laboratory',
			self::RESEARCH_OTHER_VALUE,
		);
	}

	/**
	 * Whether the user has completed the research profile (server-side).
	 *
	 * @param int $user_id User ID.
	 */
	public static function is_profile_complete( int $user_id ): bool {
		$completed = get_user_meta( $user_id, self::META_PROFILE_COMPLETED_AT, true );
		return ! empty( $completed );
	}

	/**
	 * Profile field bag for localization / REST responses.
	 *
	 * @param int $user_id User ID.
	 * @return array<string, string>
	 */
	public static function get_profile_for_user( int $user_id ): array {
		return array(
			'entity_type'            => (string) get_user_meta( $user_id, self::META_ENTITY_TYPE, true ),
			'research_setting'       => (string) get_user_meta( $user_id, self::META_RESEARCH_SETTING, true ),
			'research_setting_other' => (string) get_user_meta( $user_id, self::META_RESEARCH_SETTING_OTHER, true ),
			'org_name'               => (string) get_user_meta( $user_id, self::META_ORG_NAME, true ),
			'role_title'             => (string) get_user_meta( $user_id, self::META_ROLE_TITLE, true ),
		);
	}

	/**
	 * Validate and normalize POST-like profile array.
	 *
	 * @param array<string, mixed> $data Input.
	 * @return array{ok:bool, errors:string[], normalized: array<string,string>}
	 */
	public static function validate_profile_payload( array $data ): array {
		$errors     = array();
		$normalized = array(
			'entity_type'            => '',
			'research_setting'       => '',
			'research_setting_other' => '',
			'org_name'               => '',
			'role_title'             => '',
		);

		$entity = isset( $data['entity_type'] ) ? sanitize_text_field( (string) $data['entity_type'] ) : '';
		if ( ! in_array( $entity, self::get_entity_types(), true ) ) {
			$errors[] = __( 'Please select a valid organization / lab type.', 'molecule-research-gate' );
		} else {
			$normalized['entity_type'] = $entity;
		}

		$research = isset( $data['research_setting'] ) ? sanitize_text_field( (string) $data['research_setting'] ) : '';
		if ( ! in_array( $research, self::get_research_settings(), true ) ) {
			$errors[] = __( 'Please select a valid field of qualified research.', 'molecule-research-gate' );
		} else {
			$normalized['research_setting'] = $research;
		}

		$other = isset( $data['research_setting_other'] ) ? sanitize_text_field( (string) $data['research_setting_other'] ) : '';
		if ( self::RESEARCH_OTHER_VALUE === $normalized['research_setting'] ) {
			if ( '' === $other ) {
				$errors[] = __( 'Please specify your qualified research area.', 'molecule-research-gate' );
			} else {
				$normalized['research_setting_other'] = $other;
			}
		}

		$normalized['org_name']   = isset( $data['org_name'] ) ? sanitize_text_field( (string) $data['org_name'] ) : '';
		$normalized['role_title'] = isset( $data['role_title'] ) ? sanitize_text_field( (string) $data['role_title'] ) : '';

		return array(
			'ok'         => array() === $errors,
			'errors'     => $errors,
			'normalized' => $normalized,
		);
	}

	/**
	 * Persist validated profile for user.
	 *
	 * @param int                  $user_id User ID.
	 * @param array<string,string> $normalized From validate_profile_payload.
	 * @param string               $policy_version Optional policy version string from settings.
	 */
	public static function save_profile( int $user_id, array $normalized, string $policy_version = '' ): void {
		update_user_meta( $user_id, self::META_ENTITY_TYPE, $normalized['entity_type'] );
		update_user_meta( $user_id, self::META_RESEARCH_SETTING, $normalized['research_setting'] );
		update_user_meta( $user_id, self::META_RESEARCH_SETTING_OTHER, $normalized['research_setting_other'] );
		update_user_meta( $user_id, self::META_ORG_NAME, $normalized['org_name'] );
		update_user_meta( $user_id, self::META_ROLE_TITLE, $normalized['role_title'] );
		update_user_meta( $user_id, self::META_RUO_ACCEPTED_AT, time() );
		if ( '' !== $policy_version ) {
			update_user_meta( $user_id, self::META_RUO_POLICY_VERSION, sanitize_text_field( $policy_version ) );
		}
		update_user_meta( $user_id, self::META_PROFILE_COMPLETED_AT, time() );
	}
}
