<?php
/**
 * Title: Affiliates Hero
 * Slug: shadcn/affiliates-hero
 * Categories: shadcn, shadcn-banner
 * Description: Hero section for the Affiliate Program page.
 */
?>
<!-- wp:group {"align":"full","className":"molecule-affiliates-hero","style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|8"}},"border":{"bottom":{"color":"var:preset|color|border","width":"1px"}}},"layout":{"type":"constrained","wideSize":"1280px"}} -->
<div class="wp-block-group alignfull molecule-affiliates-hero" style="border-bottom-color:var(--wp--preset--color--border);border-bottom-width:1px;padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--8)">
	<!-- wp:paragraph {"className":"molecule-affiliates-badge"} -->
	<p class="molecule-affiliates-badge"><span class="molecule-affiliates-badge__label"><?php esc_html_e( 'Affiliate Partner Program', 'shadcn' ); ?></span></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"clamp(2.5rem,5vw,3.75rem)","lineHeight":"1.1","fontWeight":"800"},"spacing":{"margin":{"top":"var:preset|spacing|6","bottom":"var:preset|spacing|6"}}}} -->
	<h1 class="wp-block-heading" style="margin-top:var(--wp--preset--spacing--6);margin-bottom:var(--wp--preset--spacing--6);font-size:clamp(2.5rem,5vw,3.75rem);font-style:normal;font-weight:800;line-height:1.1"><?php esc_html_e( 'Share Molecule. Earn Commission.', 'shadcn' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"fontSize":"xl","textColor":"muted-foreground","style":{"typography":{"lineHeight":"1.65"}}} -->
	<p class="has-muted-foreground-color has-text-color has-xl-font-size" style="line-height:1.65"><?php esc_html_e( 'Join our partner network and receive 20% on orders driven by your referral link. One URL, transparent attribution, and monthly Venmo payments.', 'shadcn' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|7"}}}} -->
	<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--7)">
		<!-- wp:button {"className":"is-style-fill"} -->
			<div class="wp-block-button is-style-fill"><a class="wp-block-button__link wp-element-button" href="#apply"><?php esc_html_e( 'Start Your Application', 'shadcn' ); ?></a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
