<?php
/**
 * Title: Front Page Featured Products
 * Slug: shadcn/front-page-featured-products
 * Categories: shadcn
 * Description: Featured peptides grid using WooCommerce products.
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|8","bottom":"var:preset|spacing|10"}}},"layout":{"type":"constrained","wideSize":"1280px"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--8);padding-bottom:var(--wp--preset--spacing--10)">
	<!-- wp:group {"layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center","flexWrap":"wrap"}} -->
	<div class="wp-block-group">
		<!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"clamp(1.8rem,3.2vw,2.5rem)","fontWeight":"800","lineHeight":"1.2"}}} -->
		<h2 class="wp-block-heading" style="font-size:clamp(1.8rem,3.2vw,2.5rem);font-style:normal;font-weight:400;line-height:1.2"><?php esc_html_e( 'Featured Peptides', 'shadcn' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"style":{"typography":{"fontWeight":"600"}}} -->
		<p style="font-weight:600"><a href="/catalog"><?php esc_html_e( 'Shop All Peptides →', 'shadcn' ); ?></a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:query {"queryId":21,"query":{"perPage":"12","pages":0,"offset":0,"postType":"product","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"displayLayout":{"type":"flex","columns":4},"align":"wide","className":"molecule-product-grid"} -->
	<div class="wp-block-query alignwide molecule-product-grid">
		<!-- wp:post-template {"style":{"spacing":{"blockGap":"var:preset|spacing|6"}},"layout":{"type":"grid","columnCount":4}} -->
		<!-- wp:group {"className":"molecule-product-card","style":{"spacing":{"blockGap":"var:preset|spacing|3"}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group molecule-product-card">
			<!-- wp:woocommerce/product-image {"imageSizing":"thumbnail","isDescendentOfQueryLoop":true,"showSaleBadge":false} /-->

			<!-- wp:post-title {"isLink":true,"fontSize":"base","style":{"typography":{"fontWeight":"600","lineHeight":"1.35"}}} /-->

			<!-- wp:woocommerce/product-price {"isDescendentOfQueryLoop":true,"fontSize":"sm","textAlign":"left"} /-->
		</div>
		<!-- /wp:group -->
		<!-- /wp:post-template -->
	</div>
	<!-- /wp:query -->
</div>
<!-- /wp:group -->
