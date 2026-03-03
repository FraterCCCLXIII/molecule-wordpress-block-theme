<?php
/**
 * Title: Blog 1
 * Slug: shadcn/blog-1
 * Categories: shadcn, blog
 * Description: A research-style post grid.
 */

?>

<!-- wp:query {"queryId":14,"query":{"perPage":12,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":true},"className":"molecule-research-grid-query","metadata":{"categories":["posts"],"patternName":"core/query-standard-posts","name":"Research Grid"}} -->
<div class="wp-block-query molecule-research-grid-query"><!-- wp:post-template {"layout":{"type":"grid","columnCount":3,"minimumColumnWidth":"18rem"}} -->
<!-- wp:group {"className":"molecule-research-card","layout":{"type":"constrained"}} -->
<div class="wp-block-group molecule-research-card"><!-- wp:post-featured-image {"isLink":true,"className":"molecule-research-card-image"} /-->

<!-- wp:group {"className":"molecule-research-card-content","style":{"spacing":{"blockGap":"var:preset|spacing|3"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group molecule-research-card-content"><!-- wp:post-title {"isLink":true,"level":3,"style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} /-->

<!-- wp:post-excerpt {"excerptLength":26,"style":{"elements":{"link":{"color":{"text":"var:preset|color|primary"}}}},"textColor":"muted-foreground","fontSize":"sm"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
<!-- /wp:post-template --></div>
<!-- /wp:query -->
