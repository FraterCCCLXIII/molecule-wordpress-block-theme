<?php
/**
 * Show research gate data on WP user edit / profile screens.
 *
 * @package MoleculeResearchGate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MRG_Admin_User_Profile_Display
 */
class MRG_Admin_User_Profile_Display {

	/**
	 * Hooks.
	 */
	public function register(): void {
		add_action( 'edit_user_profile', array( $this, 'render_section' ), 20 );
		add_action( 'show_user_profile', array( $this, 'render_section' ), 20 );
	}

	/**
	 * @param WP_User $user User object for the profile being viewed.
	 */
	public function render_section( WP_User $user ): void {
		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			return;
		}

		$profile   = MRG_User_Profile::get_profile_for_user( (int) $user->ID );
		$accepted  = get_user_meta( $user->ID, MRG_User_Profile::META_RUO_ACCEPTED_AT, true );
		$policy_raw = get_user_meta( $user->ID, MRG_User_Profile::META_RUO_POLICY_VERSION, true );
		$policy_ver = is_scalar( $policy_raw ) ? (string) $policy_raw : '';

		?>
		<h2><?php esc_html_e( 'Molecule research gate', 'molecule-research-gate' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Data collected when this user completed the storefront research-access profile.', 'molecule-research-gate' ); ?></p>

		<?php if ( ! MRG_User_Profile::is_profile_complete( (int) $user->ID ) ) : ?>
		<p class="description"><?php esc_html_e( 'This user has not completed the storefront research-access profile yet. Incomplete fields below appear empty.', 'molecule-research-gate' ); ?></p>
		<?php endif; ?>

		<table class="form-table" role="presentation">
			<tbody>
			<tr>
				<th scope="row" style="width: 260px;">
					<?php esc_html_e( 'Organization / Lab Type', 'molecule-research-gate' ); ?>
				</th>
				<td><?php echo wp_kses_post( self::cell_text( $profile['entity_type'] ) ); ?></td>
			</tr>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Field of Qualified Research', 'molecule-research-gate' ); ?>
				</th>
				<td><?php echo wp_kses_post( self::cell_text( $profile['research_setting'] ) ); ?></td>
			</tr>
			<?php if ( '' !== trim( $profile['research_setting_other'] ) ) : ?>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Specify other qualified research', 'molecule-research-gate' ); ?>
					</th>
					<td><?php echo wp_kses_post( self::cell_text( $profile['research_setting_other'] ) ); ?></td>
				</tr>
			<?php endif; ?>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Organization / Lab Name', 'molecule-research-gate' ); ?>
				</th>
				<td><?php echo wp_kses_post( self::cell_text( $profile['org_name'] ) ); ?></td>
			</tr>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Role / Title', 'molecule-research-gate' ); ?>
				</th>
				<td><?php echo wp_kses_post( self::cell_text( $profile['role_title'] ) ); ?></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Marketing newsletter (Brevo)', 'molecule-research-gate' ); ?></th>
				<td><?php echo esc_html( self::format_timestamp_cell( get_user_meta( $user->ID, MRG_User_Profile::META_BREVO_NEWSLETTER_OPT_IN_AT, true ) ) ); ?></td>
			</tr>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Profile submitted', 'molecule-research-gate' ); ?>
				</th>
				<td><?php echo esc_html( self::format_timestamp_cell( get_user_meta( $user->ID, MRG_User_Profile::META_PROFILE_COMPLETED_AT, true ) ) ); ?></td>
			</tr>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Research Use Only acknowledgment', 'molecule-research-gate' ); ?>
				</th>
				<td><?php echo esc_html( self::format_timestamp_cell( $accepted ) ); ?></td>
			</tr>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Acknowledged policy version', 'molecule-research-gate' ); ?>
				</th>
				<td><?php echo wp_kses_post( self::cell_text( $policy_ver ) ); ?></td>
			</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Printable cell with empty fallback.
	 */
	private static function cell_text( string $value ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return '<span aria-hidden="true">&mdash;</span><span class="screen-reader-text">' . esc_html__( 'Empty', 'molecule-research-gate' ) . '</span>';
		}

		return esc_html( $value );
	}

	/**
	 * @param mixed $raw Unix timestamp saved in meta.
	 */
	private static function format_timestamp_cell( $raw ): string {
		$t = is_numeric( $raw ) ? absint( $raw ) : 0;
		if ( $t <= 0 ) {
			return self::plaintext_empty_fallback();
		}

		return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $t );
	}

	private static function plaintext_empty_fallback(): string {
		return _x( '—', 'Placeholder meaning no date recorded (em dash)', 'molecule-research-gate' );
	}
}
