<?php
/**
 * Title: Peptide Guide Notice
 * Slug: shadcn/peptide-guide-notice
 * Categories: shadcn
 * Description: Compliance notice for the peptide guide page.
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"muted","style":{"spacing":{"padding":{"top":"var:preset|spacing|8","bottom":"var:preset|spacing|8"}}},"layout":{"type":"constrained","contentSize":"900px"}} -->
<div class="wp-block-group alignfull has-muted-background-color has-background" style="padding-top:var(--wp--preset--spacing--8);padding-bottom:var(--wp--preset--spacing--8)">
	<!-- wp:paragraph {"align":"center","textColor":"muted-foreground","style":{"typography":{"lineHeight":"1.8"}}} -->
	<p class="has-text-align-center has-muted-foreground-color has-text-color" style="line-height:1.8"><?php esc_html_e( 'USA-shipped research peptides are for laboratory use only and not for human consumption, clinical use or diagnostic purposes. This guide is dedicated to supporting scientific research through proper peptide selection, handling and experimental design.', 'shadcn' ); ?></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
