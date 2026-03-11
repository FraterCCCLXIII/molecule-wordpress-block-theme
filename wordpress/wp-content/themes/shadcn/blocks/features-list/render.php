<?php
/**
 * Render callback for molecule/features-list block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner block content (unused).
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$heading  = isset( $attributes['heading'] ) ? $attributes['heading'] : 'Why Choose Our Peptides';
$features = isset( $attributes['features'] ) ? $attributes['features'] : array();

if ( empty( $features ) ) {
	return;
}

// Lucide SVG paths keyed by icon type.
$icon_svgs = array(
	'shield-check' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="molecule-features-list__icon-svg" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path><path d="m9 12 2 2 4-4"></path></svg>',
	'truck'        => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="molecule-features-list__icon-svg" aria-hidden="true"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"></path><path d="M15 18H9"></path><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"></path><circle cx="17" cy="18" r="2"></circle><circle cx="7" cy="18" r="2"></circle></svg>',
	'microscope'   => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="molecule-features-list__icon-svg" aria-hidden="true"><path d="M6 18h8"></path><path d="M3 22h18"></path><path d="M14 22a7 7 0 1 0 0-14h-1"></path><path d="M9 14h2"></path><path d="M9 12a2 2 0 0 1-2-2V6h6v4a2 2 0 0 1-2 2Z"></path><path d="M12 6V3a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v3"></path></svg>',
);
?>
<section class="molecule-features-list">
	<div class="molecule-features-list__backdrop" aria-hidden="true"></div>

	<div class="molecule-features-list__inner">
		<div class="molecule-features-list__grid">

			<!-- Left: accordion buttons -->
			<div class="molecule-features-list__text-area">
				<h2 class="molecule-features-list__heading">
					<?php echo esc_html( $heading ); ?>
				</h2>

				<?php foreach ( $features as $index => $feature ) :
					$is_active   = 0 === $index;
					$icon_type   = isset( $feature['iconType'] ) ? sanitize_key( $feature['iconType'] ) : 'shield-check';
					$title       = isset( $feature['title'] ) ? $feature['title'] : '';
					$description = isset( $feature['description'] ) ? $feature['description'] : '';
					$btn_class   = 'molecule-features-list__btn' . ( $is_active ? ' molecule-features-list__btn--active' : '' );
					$icon_svg    = isset( $icon_svgs[ $icon_type ] ) ? $icon_svgs[ $icon_type ] : $icon_svgs['shield-check'];
				?>
				<button
					class="<?php echo esc_attr( $btn_class ); ?>"
					type="button"
					aria-pressed="<?php echo $is_active ? 'true' : 'false'; ?>"
				>
					<div class="molecule-features-list__btn-content">
						<div class="molecule-features-list__icon">
							<?php echo $icon_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup ?>
						</div>
						<div class="molecule-features-list__btn-text-wrap">
							<span class="molecule-features-list__btn-title">
								<?php echo esc_html( $title ); ?>
							</span>
							<p class="molecule-features-list__btn-desc">
								<?php echo esc_html( $description ); ?>
							</p>
						</div>
					</div>
				</button>
				<?php endforeach; ?>
			</div>

			<!-- Right: stacked images, one visible at a time -->
			<div class="molecule-features-list__image-area">
				<?php foreach ( $features as $index => $feature ) :
					$is_active  = 0 === $index;
					$image_url  = isset( $feature['imageUrl'] ) ? $feature['imageUrl'] : '';
					$image_alt  = isset( $feature['imageAlt'] ) ? $feature['imageAlt'] : '';
					$wrap_class = 'molecule-features-list__image-wrapper' . ( $is_active ? ' molecule-features-list__image-wrapper--active' : '' );

					if ( empty( $image_url ) ) {
						continue;
					}
				?>
				<div class="<?php echo esc_attr( $wrap_class ); ?>">
					<img
						src="<?php echo esc_url( $image_url ); ?>"
						alt="<?php echo esc_attr( $image_alt ); ?>"
						loading="lazy"
						class="molecule-features-list__img"
					>
				</div>
				<?php endforeach; ?>
			</div>

		</div>
	</div>
</section>
