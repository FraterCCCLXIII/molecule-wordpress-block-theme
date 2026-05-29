<?php
/**
 * Shared admin view helpers (kept separate from the data model and controllers).
 *
 * @package MoleculeBundlePricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MBP_Admin_View
 */
class MBP_Admin_View {

	/**
	 * Render a fixed-row editable tier table.
	 *
	 * Each row maps to a tier; empty quantity rows are ignored on save. This avoids
	 * any dependency on an ACF/Pro repeater while keeping config flexible.
	 *
	 * @param string                           $name_base Field name base, e.g. "mbp_settings[tiers]" or "_mbp_tiers".
	 * @param array<int, array<string, mixed>> $tiers     Current tiers (sorted).
	 * @param array<string, mixed>             $args      Optional: 'compact' => bool for narrow (variation) layout.
	 * @return void
	 */
	public static function tier_rows( $name_base, $tiers, $args = array() ) {
		$compact = ! empty( $args['compact'] );
		$rows    = array();

		// Seed visible rows: existing tiers first, then pad to MAX_TIERS with blanks.
		foreach ( $tiers as $tier ) {
			$rows[] = $tier;
		}
		for ( $i = count( $rows ); $i < MBP_Tiers::MAX_TIERS; $i++ ) {
			$rows[] = MBP_Tiers::blank_tier();
		}

		$wrap_class = 'mbp-tier-table' . ( $compact ? ' mbp-tier-table--compact' : '' );
		?>
		<table class="<?php echo esc_attr( $wrap_class ); ?>">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Qty', 'molecule-bundle-pricing' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Discount %', 'molecule-bundle-pricing' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Label', 'molecule-bundle-pricing' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Sublabel', 'molecule-bundle-pricing' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Badge', 'molecule-bundle-pricing' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Stepper', 'molecule-bundle-pricing' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $index => $tier ) : ?>
					<?php
					$tier      = wp_parse_args( $tier, MBP_Tiers::blank_tier() );
					$row_base  = $name_base . '[' . $index . ']';
					$qty_value = (int) $tier['quantity'] > 0 ? (int) $tier['quantity'] : '';
					?>
					<tr>
						<td>
							<input
								type="number"
								min="1"
								step="1"
								name="<?php echo esc_attr( $row_base . '[quantity]' ); ?>"
								value="<?php echo esc_attr( $qty_value ); ?>"
								class="small-text"
								inputmode="numeric"
							/>
						</td>
						<td>
							<input
								type="number"
								min="0"
								max="100"
								step="0.01"
								name="<?php echo esc_attr( $row_base . '[discount_percent]' ); ?>"
								value="<?php echo esc_attr( (float) $tier['discount_percent'] ); ?>"
								class="small-text"
								inputmode="decimal"
							/>
						</td>
						<td>
							<input
								type="text"
								name="<?php echo esc_attr( $row_base . '[label]' ); ?>"
								value="<?php echo esc_attr( $tier['label'] ); ?>"
								placeholder="<?php esc_attr_e( 'Buy 3', 'molecule-bundle-pricing' ); ?>"
							/>
						</td>
						<td>
							<input
								type="text"
								name="<?php echo esc_attr( $row_base . '[sublabel]' ); ?>"
								value="<?php echo esc_attr( $tier['sublabel'] ); ?>"
								placeholder="<?php esc_attr_e( 'Save 5%', 'molecule-bundle-pricing' ); ?>"
							/>
						</td>
						<td>
							<input
								type="text"
								name="<?php echo esc_attr( $row_base . '[badge]' ); ?>"
								value="<?php echo esc_attr( $tier['badge'] ); ?>"
								placeholder="<?php esc_attr_e( 'Popular', 'molecule-bundle-pricing' ); ?>"
							/>
						</td>
						<td style="text-align:center;">
							<input
								type="checkbox"
								name="<?php echo esc_attr( $row_base . '[open_ended]' ); ?>"
								value="1"
								<?php checked( ! empty( $tier['open_ended'] ) ); ?>
							/>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<p class="description">
			<?php esc_html_e( 'Leave the Qty empty to skip a row. "Stepper" marks the open-ended tier (e.g. "Buy 6+") that reveals a quantity selector.', 'molecule-bundle-pricing' ); ?>
		</p>
		<?php
	}
}
