<?php
/**
 * Render callback for molecule/product-carousel block.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Rendered InnerBlocks HTML (user-designed header area).
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wc_get_products' ) ) {
	return;
}

$per_page = isset( $attributes['perPage'] ) ? (int) $attributes['perPage'] : 12;

$products = wc_get_products(
	array(
		'limit'   => $per_page,
		'status'  => 'publish',
		'orderby' => 'date',
		'order'   => 'DESC',
	)
);

if ( empty( $products ) ) {
	return;
}
?>
<section class="molecule-product-carousel">
	<div class="molecule-product-carousel__inner">

		<?php if ( ! empty( trim( $content ) ) ) : ?>
		<div class="molecule-product-carousel__header-blocks">
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- sanitized by WP block renderer ?>
		</div>
		<?php endif; ?>

		<div class="molecule-product-carousel__wrap">

			<button class="molecule-product-carousel__btn molecule-product-carousel__btn--left"
					type="button"
					aria-label="<?php esc_attr_e( 'Scroll left', 'shadcn' ); ?>">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
					 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<path d="M15 18l-6-6 6-6"/>
				</svg>
			</button>

			<button class="molecule-product-carousel__btn molecule-product-carousel__btn--right"
					type="button"
					aria-label="<?php esc_attr_e( 'Scroll right', 'shadcn' ); ?>">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
					 stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<path d="M9 18l6-6-6-6"/>
				</svg>
			</button>

			<div class="molecule-product-carousel__track">
				<?php foreach ( $products as $product ) :
					$product_id   = $product->get_id();
					$product_name = $product->get_name();
					$product_link = get_permalink( $product_id );
					$image_id     = $product->get_image_id();
					$image_url    = $image_id
						? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' )
						: wc_placeholder_img_src( 'woocommerce_thumbnail' );

					if ( $product->is_type( 'variable' ) ) {
						/** @var WC_Product_Variable $product */
						$min = wc_price( $product->get_variation_price( 'min' ) );
						$max = wc_price( $product->get_variation_price( 'max' ) );
						$price_text = ( $min !== $max )
							? wp_strip_all_tags( $min ) . ' – ' . wp_strip_all_tags( $max )
							: wp_strip_all_tags( $min );
					} else {
						$price_text = wp_strip_all_tags( wc_price( $product->get_price() ) );
					}
				?>
				<div class="molecule-product-carousel__item">
					<div class="molecule-product-carousel__card-group">
						<div class="molecule-product-carousel__image-wrap">
							<a href="<?php echo esc_url( $product_link ); ?>" tabindex="-1" aria-hidden="true">
								<img src="<?php echo esc_url( $image_url ); ?>"
									 alt="<?php echo esc_attr( $product_name ); ?>"
									 loading="lazy"
									 class="molecule-product-carousel__img">
							</a>
						</div>
						<a href="<?php echo esc_url( $product_link ); ?>" class="molecule-product-carousel__info-link">
							<div class="molecule-product-carousel__info">
								<div class="molecule-product-carousel__meta">
									<p class="molecule-product-carousel__name"><?php echo esc_html( $product_name ); ?></p>
									<span class="molecule-product-carousel__price"><?php echo esc_html( $price_text ); ?></span>
								</div>
							</div>
						</a>
					</div>
				</div>
				<?php endforeach; ?>
			</div>

		</div>
	</div>
</section>
