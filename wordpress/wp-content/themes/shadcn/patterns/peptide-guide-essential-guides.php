<?php
/**
 * Title: Peptide Guide Essential Guides
 * Slug: shadcn/peptide-guide-essential-guides
 * Categories: shadcn
 * Description: Essential peptide research guides section with post cards.
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"muted","style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}},"layout":{"type":"constrained","wideSize":"1280px"}} -->
<div class="wp-block-group alignfull has-muted-background-color has-background" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10)">
	<!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"clamp(1.9rem,3.6vw,2.9rem)","fontWeight":"800","lineHeight":"1.2"},"spacing":{"margin":{"bottom":"var:preset|spacing|3"}}}} -->
	<h2 class="wp-block-heading" style="margin-bottom:var(--wp--preset--spacing--3);font-size:clamp(1.9rem,3.6vw,2.9rem);font-style:normal;font-weight:800;line-height:1.2"><?php esc_html_e( 'Essential Peptide Research Guides', 'shadcn' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"textColor":"muted-foreground","fontSize":"lg","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|8"}}}} -->
	<p class="has-muted-foreground-color has-text-color has-lg-font-size" style="margin-bottom:var(--wp--preset--spacing--8)"><?php esc_html_e( 'Choose Your Focus', 'shadcn' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:query {"queryId":55,"query":{"perPage":6,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false}} -->
	<div class="wp-block-query">
		<!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->
			<!-- wp:group {"style":{"border":{"radius":"16px","width":"1px"},"spacing":{"padding":{"top":"var:preset|spacing|4","right":"var:preset|spacing|4","bottom":"var:preset|spacing|4","left":"var:preset|spacing|4"},"blockGap":"var:preset|spacing|3"}},"borderColor":"secondary","backgroundColor":"background","layout":{"type":"constrained"}} -->
			<div class="wp-block-group has-border-color has-secondary-border-color has-background-background-color has-background" style="border-width:1px;border-radius:16px;padding-top:var(--wp--preset--spacing--4);padding-right:var(--wp--preset--spacing--4);padding-bottom:var(--wp--preset--spacing--4);padding-left:var(--wp--preset--spacing--4)">
				<!-- wp:post-featured-image {"isLink":true,"height":"220px","style":{"border":{"radius":"12px"}}} /-->
				<!-- wp:post-title {"isLink":true,"fontSize":"xl"} /-->
				<!-- wp:post-excerpt {"moreText":"Read More","showMoreOnNewLine":false} /-->
			</div>
			<!-- /wp:group -->
		<!-- /wp:post-template -->

		<!-- wp:query-no-results -->
			<!-- wp:paragraph {"align":"center","textColor":"muted-foreground"} -->
			<p class="has-text-align-center has-muted-foreground-color has-text-color"><?php esc_html_e( 'No blog articles available yet. Check back soon.', 'shadcn' ); ?></p>
			<!-- /wp:paragraph -->
		<!-- /wp:query-no-results -->
	</div>
	<!-- /wp:query -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|7"}}}} -->
	<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--7)">
		<!-- wp:button {"className":"is-style-outline"} -->
		<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/research"><?php esc_html_e( 'View More', 'shadcn' ); ?></a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
