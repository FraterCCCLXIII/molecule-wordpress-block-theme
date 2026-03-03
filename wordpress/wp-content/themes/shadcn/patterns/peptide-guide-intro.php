<?php
/**
 * Title: Peptide Guide Intro
 * Slug: shadcn/peptide-guide-intro
 * Categories: shadcn
 * Description: Intro section for the peptide guide page.
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}},"layout":{"type":"constrained","wideSize":"1280px"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10)">
	<!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"clamp(2rem,4vw,3rem)","fontWeight":"800","lineHeight":"1.15"},"spacing":{"margin":{"bottom":"var:preset|spacing|6"}}}} -->
	<h2 class="wp-block-heading" style="margin-bottom:var(--wp--preset--spacing--6);font-size:clamp(2rem,4vw,3rem);font-style:normal;font-weight:800;line-height:1.15"><?php esc_html_e( 'Your Online Peptide Guide for Research Applications', 'shadcn' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"fontSize":"lg","textColor":"muted-foreground","style":{"typography":{"lineHeight":"1.8"},"layout":{"selfStretch":"fit","flexSize":null}}} -->
	<p class="has-muted-foreground-color has-text-color has-lg-font-size" style="line-height:1.8"><?php esc_html_e( 'Whether you are new to peptide research or looking to expand your laboratory capabilities, this guide to peptides is your go-to resource for understanding, synthesizing and working with research peptides. From basics to advanced techniques, we have compiled the most important information to support your scientific investigations.', 'shadcn' ); ?></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
