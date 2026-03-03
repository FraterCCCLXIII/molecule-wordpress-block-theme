<?php
/**
 * Title: Peptide Guide Hero
 * Slug: shadcn/peptide-guide-hero
 * Categories: shadcn, shadcn-banner
 * Description: Hero section for the Peptide Guide page.
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}},"layout":{"type":"constrained","wideSize":"1280px"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10)">
	<!-- wp:group {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|7"}}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group" style="margin-bottom:var(--wp--preset--spacing--7)">
		<!-- wp:group {"className":"molecule-hero-badge","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"left","verticalAlignment":"center"}} -->
		<div class="wp-block-group molecule-hero-badge">
			<!-- wp:paragraph {"className":"molecule-stars"} -->
			<p class="molecule-stars" aria-hidden="true">i</p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-foreground"} -->
			<p class="has-muted-foreground-color has-text-color has-sm-font-size"><?php esc_html_e( 'USA-shipped research peptides for laboratory use only', 'shadcn' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"clamp(2.5rem,5vw,3.75rem)","lineHeight":"1.1","fontWeight":"800"},"spacing":{"margin":{"top":"var:preset|spacing|6","bottom":"var:preset|spacing|6"}}}} -->
		<h1 class="wp-block-heading" style="margin-top:var(--wp--preset--spacing--6);margin-bottom:var(--wp--preset--spacing--6);font-size:clamp(2.5rem,5vw,3.75rem);font-style:normal;font-weight:800;line-height:1.1"><?php esc_html_e( 'Research Guide:', 'shadcn' ); ?><br><span class="has-muted-foreground-color has-text-color"><?php esc_html_e( 'An Online Reference for Researchers', 'shadcn' ); ?></span></h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"xl","textColor":"muted-foreground","style":{"typography":{"lineHeight":"1.65"}}} -->
		<p class="has-muted-foreground-color has-text-color has-xl-font-size" style="line-height:1.65"><?php esc_html_e( 'Research peptides are key to advancing science in biochemistry, cell biology and molecular research. This knowledge center provides laboratory scientists with the technical information and practical guidance to select, handle and work with research-grade peptides for in vitro and ex vivo applications.', 'shadcn' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"molecule-hero-cover"} -->
	<figure class="wp-block-image size-full molecule-hero-cover"><img src="https://images.pexels.com/photos/4033152/pexels-photo-4033152.jpeg?auto=compress&amp;cs=tinysrgb&amp;dpr=2&amp;h=1200&amp;w=2000" alt="<?php esc_attr_e( 'Research laboratory', 'shadcn' ); ?>"/></figure>
	<!-- /wp:image -->
</div>
<!-- /wp:group -->
