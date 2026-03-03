<?php
/**
 * Title: Header 1
 * Slug: shadcn/header-1
 * Categories: shadcn, header
 * Description: Molecule-inspired top nav with mobile menu, centered logo, and utility icons.
 */
?>

<!-- wp:group {"className":"molecule-top-nav","layout":{"type":"constrained"}} -->
<div class="wp-block-group molecule-top-nav">
	<!-- wp:group {"align":"wide","className":"molecule-top-nav-inner","layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide molecule-top-nav-inner">
		<!-- wp:group {"className":"molecule-top-nav-mobile","layout":{"type":"grid","columnCount":3,"minimumColumnWidth":null}} -->
		<div class="wp-block-group molecule-top-nav-mobile">
			<!-- wp:group {"className":"molecule-top-nav-mobile-menu","layout":{"type":"flex","justifyContent":"left","verticalAlignment":"center"}} -->
			<div class="wp-block-group molecule-top-nav-mobile-menu">
				<!-- wp:navigation {"overlayMenu":"always","openSubmenusOnClick":true,"className":"molecule-mobile-navigation","layout":{"type":"flex","orientation":"vertical"}} -->
					<!-- wp:html -->
					<div class="molecule-mobile-drawer-quick-icons" aria-label="Quick actions">
						<a href="/?s=" aria-label="Search">
							<svg role="presentation" stroke-width="2" focusable="false" width="24" height="24" viewBox="0 0 24 24" aria-hidden="true">
								<circle cx="11" cy="11" r="7" stroke="currentColor" fill="none"></circle>
								<line x1="16.65" y1="16.65" x2="21" y2="21" stroke="currentColor" stroke-linecap="round"></line>
							</svg>
						</a>
						<a href="/my-account" aria-label="Account">
							<svg role="presentation" stroke-width="2" focusable="false" width="24" height="24" viewBox="0 0 24 24" aria-hidden="true">
								<circle cx="12" cy="8" r="4" stroke="currentColor" fill="none"></circle>
								<path d="M4 20c1.6-3.4 4.3-5 8-5s6.4 1.6 8 5" stroke="currentColor" fill="none" stroke-linecap="round"></path>
							</svg>
						</a>
						<a href="/cart" aria-label="Cart">
							<svg role="presentation" stroke-width="2" focusable="false" width="24" height="24" viewBox="0 0 24 24" aria-hidden="true">
								<path d="M12 8H3.902a2 2 0 0 0-1.937 2.497l2.238 8.7A2 2 0 0 0 6.14 20.7h11.72a2 2 0 0 0 1.937-1.503l2.238-8.7A2 2 0 0 0 20.098 8H12Zm0 0V2" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>
							</svg>
						</a>
					</div>
					<!-- /wp:html -->
					<!-- wp:navigation-link {"label":"Home","url":"/","kind":"custom"} /-->
					<!-- wp:navigation-link {"label":"Catalog","url":"/shop","kind":"custom"} /-->
					<!-- wp:navigation-submenu {"label":"Research","url":"/research","kind":"custom"} -->
						<!-- wp:navigation-link {"label":"Peptide Guide","url":"/peptide-guide","kind":"custom"} /-->
						<!-- wp:navigation-link {"label":"Research","url":"/research","kind":"custom"} /-->
					<!-- /wp:navigation-submenu -->
				<!-- /wp:navigation -->
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"className":"molecule-top-nav-logo","layout":{"type":"flex","justifyContent":"center","verticalAlignment":"center"}} -->
			<div class="wp-block-group molecule-top-nav-logo">
				<!-- wp:site-logo {"width":149,"shouldSyncIcon":true} /-->
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"className":"molecule-top-nav-icons","layout":{"type":"flex","justifyContent":"right","verticalAlignment":"center"}} -->
			<div class="wp-block-group molecule-top-nav-icons">
				<!-- wp:search {"label":"Search","showLabel":false,"buttonText":"Search","buttonUseIcon":true,"buttonPosition":"button-only","className":"molecule-header-search"} /-->
				<!-- wp:woocommerce/customer-account {"displayStyle":"icon_only","className":"molecule-header-account"} /-->
				<!-- wp:woocommerce/mini-cart {"className":"molecule-header-cart"} /-->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"molecule-top-nav-desktop","layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center"}} -->
		<div class="wp-block-group molecule-top-nav-desktop">
			<!-- wp:group {"className":"molecule-top-nav-logo","layout":{"type":"flex","justifyContent":"left","verticalAlignment":"center"}} -->
			<div class="wp-block-group molecule-top-nav-logo">
				<!-- wp:site-logo {"width":149,"shouldSyncIcon":true} /-->
			</div>
			<!-- /wp:group -->

			<!-- wp:navigation {"openSubmenusOnClick":false,"className":"molecule-desktop-navigation","layout":{"type":"flex","justifyContent":"center"}} -->
				<!-- wp:navigation-link {"label":"Home","url":"/","kind":"custom"} /-->
				<!-- wp:navigation-link {"label":"Catalog","url":"/shop","kind":"custom"} /-->
				<!-- wp:navigation-submenu {"label":"Research","url":"/research","kind":"custom"} -->
					<!-- wp:navigation-link {"label":"Peptide Guide","url":"/peptide-guide","kind":"custom"} /-->
					<!-- wp:navigation-link {"label":"Research","url":"/research","kind":"custom"} /-->
				<!-- /wp:navigation-submenu -->
			<!-- /wp:navigation -->

			<!-- wp:group {"className":"molecule-top-nav-icons","layout":{"type":"flex","justifyContent":"right","verticalAlignment":"center"}} -->
			<div class="wp-block-group molecule-top-nav-icons">
				<!-- wp:search {"label":"Search","showLabel":false,"buttonText":"Search","buttonUseIcon":true,"buttonPosition":"button-only","className":"molecule-header-search"} /-->
				<!-- wp:woocommerce/customer-account {"displayStyle":"icon_only","className":"molecule-header-account"} /-->
				<!-- wp:woocommerce/mini-cart {"className":"molecule-header-cart"} /-->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
