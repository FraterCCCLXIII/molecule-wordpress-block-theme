<?php
/**
 * Title: Front Page Quality Intro
 * Slug: shadcn/front-page-quality-intro
 * Categories: shadcn
 * Description: Intro section for quality message.
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}},"layout":{"type":"constrained","wideSize":"1280px"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10)">
	<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"clamp(2rem,4vw,3.75rem)","fontWeight":"800","lineHeight":"1.1"},"spacing":{"margin":{"bottom":"var:preset|spacing|6"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="margin-bottom:var(--wp--preset--spacing--6);font-size:clamp(2rem,4vw,3.75rem);font-style:normal;font-weight:400;line-height:1.1"><?php esc_html_e( 'High-Purity Research Peptides', 'shadcn' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","textColor":"muted-foreground","fontSize":"lg","style":{"typography":{"lineHeight":"1.75"},"layout":{"selfStretch":"fit","flexSize":null}}} -->
	<p class="has-text-align-center has-muted-foreground-color has-text-color has-lg-font-size" style="line-height:1.75"><?php esc_html_e( 'Every batch undergoes rigorous third-party testing to verify identity, purity, and composition. We provide full Certificates of Analysis (CoA) for complete transparency and confidence in your research materials.', 'shadcn' ); ?></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
