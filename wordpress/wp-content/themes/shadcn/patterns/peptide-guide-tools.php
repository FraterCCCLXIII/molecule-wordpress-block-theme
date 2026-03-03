<?php
/**
 * Title: Peptide Guide Tools
 * Slug: shadcn/peptide-guide-tools
 * Categories: shadcn
 * Description: Tools and resources section for the peptide guide page.
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}},"layout":{"type":"constrained","wideSize":"1280px"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10)">
	<!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"clamp(1.9rem,3.6vw,2.9rem)","fontWeight":"800","lineHeight":"1.2"},"spacing":{"margin":{"bottom":"var:preset|spacing|3"}}}} -->
	<h2 class="wp-block-heading" style="margin-bottom:var(--wp--preset--spacing--3);font-size:clamp(1.9rem,3.6vw,2.9rem);font-style:normal;font-weight:800;line-height:1.2"><?php esc_html_e( 'Tools and Resources for Peptide Researchers', 'shadcn' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"textColor":"muted-foreground","fontSize":"lg","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|8"}}}} -->
	<p class="has-muted-foreground-color has-text-color has-lg-font-size" style="margin-bottom:var(--wp--preset--spacing--8)"><?php esc_html_e( 'Discover our growing library of tools and resources', 'shadcn' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:columns -->
	<div class="wp-block-columns">
		<!-- wp:column {"width":"66.66%"} -->
		<div class="wp-block-column" style="flex-basis:66.66%">
			<!-- wp:group {"style":{"border":{"radius":"16px","width":"1px"},"spacing":{"padding":{"top":"var:preset|spacing|5","right":"var:preset|spacing|5","bottom":"var:preset|spacing|5","left":"var:preset|spacing|5"},"blockGap":"var:preset|spacing|4"}},"borderColor":"secondary","layout":{"type":"constrained"}} -->
			<div class="wp-block-group has-border-color has-secondary-border-color" style="border-width:1px;border-radius:16px;padding-top:var(--wp--preset--spacing--5);padding-right:var(--wp--preset--spacing--5);padding-bottom:var(--wp--preset--spacing--5);padding-left:var(--wp--preset--spacing--5)">
				<!-- wp:image {"width":"100%","sizeSlug":"full","linkDestination":"none","className":"molecule-hero-cover"} -->
				<figure class="wp-block-image size-full is-resized molecule-hero-cover"><img src="https://images.pexels.com/photos/25626518/pexels-photo-25626518.jpeg?auto=compress&amp;cs=tinysrgb&amp;dpr=2&amp;h=1000&amp;w=1600" alt="<?php esc_attr_e( 'Peptide calculator', 'shadcn' ); ?>" style="width:100%"/></figure>
				<!-- /wp:image -->

				<!-- wp:heading {"level":3,"fontSize":"2-xl"} -->
				<h3 class="wp-block-heading has-2-xl-font-size"><?php esc_html_e( 'Peptide Calculator', 'shadcn' ); ?></h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"textColor":"muted-foreground","style":{"typography":{"lineHeight":"1.8"}}} -->
				<p class="has-muted-foreground-color has-text-color" style="line-height:1.8"><?php esc_html_e( 'Accurately reconstitute lyophilized peptides for research applications with this easy-to-use dilution calculator and guide. Learn proper techniques for dissolving freeze-dried peptides, choosing appropriate diluents, calculating concentrations and avoiding common mistakes.', 'shadcn' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:buttons -->
				<div class="wp-block-buttons">
					<!-- wp:button -->
					<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/calculator"><?php esc_html_e( 'Open Calculator', 'shadcn' ); ?></a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
