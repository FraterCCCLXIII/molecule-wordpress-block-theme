<?php
/**
 * Title: Front Page Why Choose
 * Slug: shadcn/front-page-why-choose
 * Categories: shadcn
 * Description: Why choose us section with key benefits.
 */
?>
<!-- wp:group {"align":"full","className":"molecule-why-choose","style":{"spacing":{"padding":{"top":"var:preset|spacing|9","bottom":"var:preset|spacing|9"}}},"layout":{"type":"constrained","wideSize":"1280px"}} -->
<div class="wp-block-group alignfull molecule-why-choose" style="padding-top:var(--wp--preset--spacing--9);padding-bottom:var(--wp--preset--spacing--9)">
	<!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|8"}}}} -->
	<div class="wp-block-columns are-vertically-aligned-center">
		<!-- wp:column {"verticalAlignment":"center","width":"55%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:55%">
			<!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"clamp(2rem,4vw,3.5rem)","fontWeight":"800","lineHeight":"1.1"},"spacing":{"margin":{"bottom":"var:preset|spacing|6"}}}} -->
			<h2 class="wp-block-heading" style="margin-bottom:var(--wp--preset--spacing--6);font-size:clamp(2rem,4vw,3.5rem);font-style:normal;font-weight:400;line-height:1.1"><?php esc_html_e( 'Why Choose Our Peptides', 'shadcn' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:details {"showContent":true,"className":"molecule-feature-panel is-open"} -->
			<details class="wp-block-details molecule-feature-panel is-open" open>
				<summary><?php esc_html_e( 'Third-Party Tested', 'shadcn' ); ?></summary>
				<!-- wp:paragraph {"textColor":"muted-foreground"} -->
				<p class="has-muted-foreground-color has-text-color"><?php esc_html_e( 'Every batch of our peptides undergoes independent laboratory testing to verify identity, purity, and composition. We provide full Certificates of Analysis (CoA) for complete transparency.', 'shadcn' ); ?></p>
				<!-- /wp:paragraph -->
			</details>
			<!-- /wp:details -->

			<!-- wp:details {"className":"molecule-feature-panel"} -->
			<details class="wp-block-details molecule-feature-panel">
				<summary><?php esc_html_e( 'USA-Shipped & Fast Delivery', 'shadcn' ); ?></summary>
				<!-- wp:paragraph {"textColor":"muted-foreground"} -->
				<p class="has-muted-foreground-color has-text-color"><?php esc_html_e( 'We ship directly from our U.S. facility, ensuring fast delivery times and eliminating customs delays. Professional packaging and tracking updates help your materials arrive safely and on time.', 'shadcn' ); ?></p>
				<!-- /wp:paragraph -->
			</details>
			<!-- /wp:details -->

			<!-- wp:details {"className":"molecule-feature-panel"} -->
			<details class="wp-block-details molecule-feature-panel">
				<summary><?php esc_html_e( 'Research-Grade Quality', 'shadcn' ); ?></summary>
				<!-- wp:paragraph {"textColor":"muted-foreground"} -->
				<p class="has-muted-foreground-color has-text-color"><?php esc_html_e( 'All products are intended for laboratory research use only. We prioritize purity, consistency, and reliability so your research work is backed by dependable materials.', 'shadcn' ); ?></p>
				<!-- /wp:paragraph -->
			</details>
			<!-- /wp:details -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center","width":"45%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:45%">
			<!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"molecule-feature-image"} -->
			<figure class="wp-block-image size-full molecule-feature-image"><img src="https://store.moleculepeptides.com/wp-content/uploads/2025/12/pexels-polina-tankilevitch-3735766.jpg" alt="<?php esc_attr_e( 'Scientist preparing samples in the lab', 'shadcn' ); ?>"/></figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
