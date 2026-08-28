<?php
/**
 * Title: Affiliates Application
 * Slug: shadcn/affiliates-application
 * Categories: shadcn
 * Description: Affiliate application form section.
 */

$affiliate_form_markup = do_shortcode( '[molecule_affiliate_application_form]' );
?>
<!-- wp:group {"align":"full","className":"molecule-affiliates-application","anchor":"apply","style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}},"layout":{"type":"constrained","wideSize":"1280px"}} -->
<div id="apply" class="wp-block-group alignfull molecule-affiliates-application" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10)">
	<!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|10"}}}} -->
	<div class="wp-block-columns">
		<!-- wp:column {"width":"40%"} -->
		<div class="wp-block-column" style="flex-basis:40%">
			<!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"clamp(1.25rem,2.2vw,1.75rem)","fontWeight":"800"},"spacing":{"margin":{"bottom":"var:preset|spacing|4"}}}} -->
			<h2 class="wp-block-heading" style="margin-bottom:var(--wp--preset--spacing--4);font-size:clamp(1.25rem,2.2vw,1.75rem);font-style:normal;font-weight:800;line-height:1.1"><?php esc_html_e( 'Request Partner Access', 'shadcn' ); ?></h2>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"fontSize":"lg","textColor":"muted-foreground","style":{"typography":{"lineHeight":"1.65"}}} -->
			<p class="has-muted-foreground-color has-text-color has-lg-font-size" style="line-height:1.65"><?php esc_html_e( 'We look for partners who genuinely fit our audience — lab professionals, science educators, clinicians, and creators working in research-focused communities.', 'shadcn' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"60%"} -->
		<div class="wp-block-column" style="flex-basis:60%">
			<!-- wp:html -->
			<?php echo $affiliate_form_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- form markup is escaped in renderer. ?>
			<!-- /wp:html -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
