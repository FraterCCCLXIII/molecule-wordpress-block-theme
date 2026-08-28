<?php
/**
 * Tier data model, sanitization, and resolution.
 *
 * A "tier" is an associative array:
 *   quantity         (int)    Quantity threshold for this tier (>= 1).
 *   discount_percent (float)  Percentage off the per-unit price (0-100).
 *   label            (string) Primary label, e.g. "Buy 3".
 *   sublabel         (string) Secondary label, e.g. "Save 5%".
 *   badge            (string) Optional badge, e.g. "Popular".
 *   open_ended       (bool)   When true, the card reveals a quantity stepper.
 *
 * @package MoleculeBundlePricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MBP_Tiers
 */
class MBP_Tiers {

	/**
	 * Maximum number of tier slots exposed in admin (fixed-row UI, no ACF repeater needed).
	 */
	const MAX_TIERS = 6;

	/**
	 * Empty-but-shaped tier used to seed admin rows.
	 *
	 * @return array<string, mixed>
	 */
	public static function blank_tier() {
		return array(
			'quantity'         => 0,
			'discount_percent' => 0.0,
			'label'            => '',
			'sublabel'         => '',
			'badge'            => '',
			'open_ended'       => false,
		);
	}

	/**
	 * Sanitize a single raw tier row into the canonical shape.
	 *
	 * @param array<string, mixed> $raw Raw input row.
	 * @return array<string, mixed>
	 */
	public static function sanitize_tier( $raw ) {
		$raw = is_array( $raw ) ? $raw : array();

		$quantity = isset( $raw['quantity'] ) ? absint( $raw['quantity'] ) : 0;
		$discount = isset( $raw['discount_percent'] ) ? (float) $raw['discount_percent'] : 0.0;
		$discount = max( 0.0, min( 100.0, $discount ) );

		return array(
			'quantity'         => $quantity,
			'discount_percent' => $discount,
			'label'            => isset( $raw['label'] ) ? sanitize_text_field( (string) $raw['label'] ) : '',
			'sublabel'         => isset( $raw['sublabel'] ) ? sanitize_text_field( (string) $raw['sublabel'] ) : '',
			'badge'            => isset( $raw['badge'] ) ? sanitize_text_field( (string) $raw['badge'] ) : '',
			'open_ended'       => ! empty( $raw['open_ended'] ),
		);
	}

	/**
	 * Sanitize a list of raw tier rows: drop empty rows (quantity < 1), sort ascending by quantity,
	 * and de-duplicate quantities (last write wins).
	 *
	 * @param mixed $rows Raw rows array.
	 * @return array<int, array<string, mixed>>
	 */
	public static function sanitize_tiers( $rows ) {
		if ( ! is_array( $rows ) ) {
			return array();
		}

		$by_quantity = array();
		foreach ( $rows as $row ) {
			$tier = self::sanitize_tier( $row );
			if ( $tier['quantity'] < 1 ) {
				continue;
			}
			$by_quantity[ $tier['quantity'] ] = $tier;
		}

		if ( empty( $by_quantity ) ) {
			return array();
		}

		ksort( $by_quantity, SORT_NUMERIC );

		return array_values( $by_quantity );
	}

	/**
	 * Resolve the effective tiers for a product/variation.
	 *
	 * Resolution order (empty = inherit):
	 *   1. Variation override (when variation id given and it has custom tiers).
	 *   2. Parent/simple product override.
	 *   3. Global default tiers.
	 *
	 * Returns an empty array when bundle pricing is disabled for the product.
	 *
	 * @param int $product_id   Parent (or simple) product id.
	 * @param int $variation_id Optional variation id.
	 * @return array<int, array<string, mixed>>
	 */
	public static function resolve( $product_id, $variation_id = 0 ) {
		$product_id   = absint( $product_id );
		$variation_id = absint( $variation_id );

		if ( ! $product_id || ! self::is_enabled_for_product( $product_id ) ) {
			return array();
		}

		if ( $variation_id ) {
			$variation_tiers = self::get_meta_tiers( $variation_id );
			if ( ! empty( $variation_tiers ) ) {
				return $variation_tiers;
			}
		}

		$product_tiers = self::get_meta_tiers( $product_id );
		if ( ! empty( $product_tiers ) ) {
			return $product_tiers;
		}

		return MBP_Settings::get_global_tiers();
	}

	/**
	 * Whether bundle pricing is enabled for a given product.
	 *
	 * Per-product flag wins; an unset flag falls back to the global "enable by default" setting.
	 *
	 * @param int $product_id Product id.
	 * @return bool
	 */
	public static function is_enabled_for_product( $product_id ) {
		$flag = get_post_meta( absint( $product_id ), MBP_META_ENABLED, true );

		if ( 'yes' === $flag ) {
			return true;
		}

		if ( 'no' === $flag ) {
			return false;
		}

		return MBP_Settings::enabled_by_default();
	}

	/**
	 * Read and sanitize tiers stored on a post (product or variation).
	 *
	 * @param int $post_id Post id.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_meta_tiers( $post_id ) {
		$stored = get_post_meta( absint( $post_id ), MBP_META_TIERS, true );

		return self::sanitize_tiers( $stored );
	}

	/**
	 * Find the tier applicable to a given purchase quantity (highest threshold <= qty).
	 *
	 * @param array<int, array<string, mixed>> $tiers    Sorted tiers.
	 * @param int                              $quantity Purchase quantity.
	 * @return array<string, mixed>|null
	 */
	public static function applicable_tier( $tiers, $quantity ) {
		$quantity = max( 0, (int) $quantity );
		$match    = null;

		foreach ( $tiers as $tier ) {
			if ( $quantity >= (int) $tier['quantity'] ) {
				$match = $tier;
			}
		}

		return $match;
	}
}
