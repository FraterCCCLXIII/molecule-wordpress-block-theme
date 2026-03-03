<?php
/**
 * Title: Front Page Hero Image
 * Slug: shadcn/front-page-hero-image
 * Categories: shadcn
 * Description: Large supporting image below hero.
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|7","bottom":"var:preset|spacing|8"}}},"layout":{"type":"constrained","wideSize":"1280px"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--7);padding-bottom:var(--wp--preset--spacing--8)">
	<!-- wp:cover {"url":"https://store.moleculepeptides.com/wp-content/uploads/2026/02/glove.jpg","dimRatio":0,"isUserOverlayColor":true,"focalPoint":{"x":0.5,"y":0.5},"minHeight":520,"minHeightUnit":"px","isDark":false,"className":"molecule-hero-cover","layout":{"type":"constrained"}} -->
	<div class="wp-block-cover is-light molecule-hero-cover" style="min-height:520px"><img class="wp-block-cover__image-background" alt="<?php esc_attr_e( 'Laboratory glove holding vial', 'shadcn' ); ?>" src="https://store.moleculepeptides.com/wp-content/uploads/2026/02/glove.jpg" style="object-position:50% 50%" data-object-fit="cover" data-object-position="50% 50%"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-0 has-background-dim"></span><div class="wp-block-cover__inner-container"></div></div>
	<!-- /wp:cover -->
</div>
<!-- /wp:group -->
