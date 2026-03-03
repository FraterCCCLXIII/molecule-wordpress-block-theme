<?php
/**
 * Title: Header 1
 * Slug: shadcn/header-1
 * Categories: shadcn, header
 * Description: Molecule-inspired top nav with mobile drawer, centered logo, and utility icons.
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
				<!-- wp:html -->
				<button class="molecule-mobile-menu-button" type="button" aria-label="Menu" aria-controls="molecule-mobile-drawer" aria-expanded="false">
					<svg role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">
						<path d="M1 5h20M1 11h20M1 17h20" stroke="currentColor" stroke-linecap="round"></path>
					</svg>
				</button>
				<div class="molecule-mobile-drawer-backdrop" hidden></div>
				<aside id="molecule-mobile-drawer" class="molecule-mobile-drawer" aria-hidden="true">
					<div class="molecule-mobile-drawer-header">
						<button class="molecule-mobile-drawer-close" type="button" aria-label="Close menu">
							<svg role="presentation" stroke-width="2" focusable="false" width="24" height="24" viewBox="0 0 24 24" aria-hidden="true">
								<line x1="18" y1="6" x2="6" y2="18" stroke="currentColor" stroke-linecap="round"></line>
								<line x1="6" y1="6" x2="18" y2="18" stroke="currentColor" stroke-linecap="round"></line>
							</svg>
						</button>
						<div class="molecule-mobile-drawer-quick-icons" aria-label="Quick actions">
							<a href="/?s=" aria-label="Search">
								<svg role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">
									<circle cx="11" cy="10" r="7" fill="none" stroke="currentColor"></circle>
									<path d="m16 15 3 3" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>
								</svg>
							</a>
							<a href="/my-account" aria-label="Account">
								<svg role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">
									<circle cx="11" cy="7" r="4" fill="none" stroke="currentColor"></circle>
									<path d="M3.5 19c1.421-2.974 4.247-5 7.5-5s6.079 2.026 7.5 5" fill="none" stroke="currentColor" stroke-linecap="round"></path>
								</svg>
							</a>
							<a class="molecule-cart-icon-link" href="/cart" aria-label="Cart">
								<svg role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">
									<path d="M11 7H3.577A2 2 0 0 0 1.64 9.497l2.051 8A2 2 0 0 0 5.63 19H16.37a2 2 0 0 0 1.937-1.503l2.052-8A2 2 0 0 0 18.422 7H11Zm0 0V1" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>
								</svg>
							</a>
						</div>
					</div>
					<nav class="molecule-mobile-drawer-nav" aria-label="Mobile Navigation">
						<a href="/">Home</a>
						<a href="/shop">Catalog</a>
						<a href="/peptide-guide">Peptide Guide</a>
						<a href="/research">Research</a>
					</nav>
				</aside>
				<!-- /wp:html -->
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"className":"molecule-top-nav-logo","layout":{"type":"flex","justifyContent":"center","verticalAlignment":"center"}} -->
			<div class="wp-block-group molecule-top-nav-logo">
				<!-- wp:site-logo {"width":149,"shouldSyncIcon":true} /-->
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"className":"molecule-top-nav-icons","layout":{"type":"flex","justifyContent":"right","verticalAlignment":"center"}} -->
			<div class="wp-block-group molecule-top-nav-icons">
				<!-- wp:html -->
				<a class="molecule-icon-link molecule-header-search" href="/?s=" aria-label="Search">
					<svg role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">
						<circle cx="11" cy="10" r="7" fill="none" stroke="currentColor"></circle>
						<path d="m16 15 3 3" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>
					</svg>
				</a>
				<!-- /wp:html -->
				<!-- wp:html -->
				<a class="molecule-icon-link molecule-header-account" href="/my-account" aria-label="Account">
					<svg role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">
						<circle cx="11" cy="7" r="4" fill="none" stroke="currentColor"></circle>
						<path d="M3.5 19c1.421-2.974 4.247-5 7.5-5s6.079 2.026 7.5 5" fill="none" stroke="currentColor" stroke-linecap="round"></path>
					</svg>
				</a>
				<!-- /wp:html -->
				<!-- wp:html -->
				<a class="molecule-icon-link molecule-cart-icon-link molecule-header-cart-mobile" href="/cart" aria-label="Cart">
					<svg role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">
						<path d="M11 7H3.577A2 2 0 0 0 1.64 9.497l2.051 8A2 2 0 0 0 5.63 19H16.37a2 2 0 0 0 1.937-1.503l2.052-8A2 2 0 0 0 18.422 7H11Zm0 0V1" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>
					</svg>
				</a>
				<!-- /wp:html -->
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
				<!-- wp:html -->
				<a class="molecule-icon-link molecule-header-search" href="/?s=" aria-label="Search">
					<svg role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">
						<circle cx="11" cy="10" r="7" fill="none" stroke="currentColor"></circle>
						<path d="m16 15 3 3" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"></path>
					</svg>
				</a>
				<!-- /wp:html -->
				<!-- wp:html -->
				<a class="molecule-icon-link molecule-header-account" href="/my-account" aria-label="Account">
					<svg role="presentation" stroke-width="2" focusable="false" width="22" height="22" viewBox="0 0 22 22" aria-hidden="true">
						<circle cx="11" cy="7" r="4" fill="none" stroke="currentColor"></circle>
						<path d="M3.5 19c1.421-2.974 4.247-5 7.5-5s6.079 2.026 7.5 5" fill="none" stroke="currentColor" stroke-linecap="round"></path>
					</svg>
				</a>
				<!-- /wp:html -->
				<!-- wp:woocommerce/mini-cart {"className":"molecule-header-cart"} /-->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
