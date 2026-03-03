<?php
/**
 * Title: Front Page Hero
 * Slug: shadcn/front-page-hero
 * Categories: shadcn, shadcn-banner
 * Description: Hero banner for the Molecule home page.
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|8","bottom":"var:preset|spacing|4"}}},"layout":{"type":"constrained","wideSize":"1280px"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--8);padding-bottom:var(--wp--preset--spacing--4)">
	<!-- wp:group {"className":"molecule-hero-badge","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"left","verticalAlignment":"center"}} -->
	<div class="wp-block-group molecule-hero-badge">
		<!-- wp:paragraph {"className":"molecule-stars"} -->
		<p class="molecule-stars" aria-hidden="true">★★★★★</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-foreground"} -->
		<p class="has-muted-foreground-color has-text-color has-sm-font-size"><?php esc_html_e( 'USA-shipped research peptides for laboratory use only', 'shadcn' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"clamp(2.25rem,5vw,4rem)","lineHeight":"1.1","fontWeight":"800"},"spacing":{"margin":{"top":"var:preset|spacing|5","bottom":"var:preset|spacing|6"}}}} -->
	<h1 class="wp-block-heading" style="margin-top:var(--wp--preset--spacing--5);margin-bottom:var(--wp--preset--spacing--6);font-size:clamp(2.25rem,5vw,4rem);font-style:normal;font-weight:400;line-height:1.1"><?php esc_html_e( 'Premium Research Peptides', 'shadcn' ); ?><br><span class="has-muted-foreground-color has-text-color"><?php esc_html_e( 'For Laboratory Use', 'shadcn' ); ?></span></h1>
	<!-- /wp:heading -->

	<!-- wp:buttons -->
	<div class="wp-block-buttons">
		<!-- wp:button {"style":{"border":{"radius":"9999px"},"spacing":{"padding":{"top":"0.85rem","bottom":"0.85rem","left":"1.8rem","right":"1.8rem"}}},"fontSize":"lg"} -->
		<div class="wp-block-button has-custom-font-size has-lg-font-size"><a class="wp-block-button__link wp-element-button" href="/catalog" style="border-radius:9999px;padding-top:0.85rem;padding-right:1.8rem;padding-bottom:0.85rem;padding-left:1.8rem"><?php esc_html_e( 'Shop Peptides', 'shadcn' ); ?></a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
