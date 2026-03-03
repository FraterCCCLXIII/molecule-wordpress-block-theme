<?php
/**
 * Title: Peptide Guide Disclaimer
 * Slug: shadcn/peptide-guide-disclaimer
 * Categories: shadcn
 * Description: Footer disclaimer section for the peptide guide page.
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"muted","style":{"border":{"top":{"color":"var:preset|color|secondary","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|7","bottom":"var:preset|spacing|7"}}},"layout":{"type":"constrained","contentSize":"900px"}} -->
<div class="wp-block-group alignfull has-muted-background-color has-background" style="border-top-color:var(--wp--preset--color--secondary);border-top-width:1px;padding-top:var(--wp--preset--spacing--7);padding-bottom:var(--wp--preset--spacing--7)">
	<!-- wp:paragraph {"align":"center","fontSize":"sm","textColor":"muted-foreground"} -->
	<p class="has-text-align-center has-muted-foreground-color has-text-color has-sm-font-size"><?php esc_html_e( 'Research peptides are for laboratory use only and not intended for human consumption or clinical applications.', 'shadcn' ); ?></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
