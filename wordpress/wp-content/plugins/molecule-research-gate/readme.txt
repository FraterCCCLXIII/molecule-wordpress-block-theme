=== Molecule Research Gate ===
Contributors: molecule
Tags: woocommerce, gate, research, compliance, modal
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.4
License: GPLv2 or later

Fullscreen WooCommerce catalog gate with Research Use Only acknowledgment, lab profile capture, and optional Brevo newsletter opt-in.

== Description ==

Molecule Research Gate intercepts guests on selected storefront URLs and opens a modal that explains Research Use Only expectations and sends them through WooCommerce login or registration.

Logged-in customers complete a short research-entity profile (organization type, qualified research area, optional lab name and role). Answers are saved to WordPress user meta. Administrators can review them on each user’s profile screen in wp-admin.

Optional integration with the [Brevo WordPress plugin](https://wordpress.org/plugins/mailin/) adds a marketing checkbox on the final “verified” step when list IDs are configured under Settings → Molecule Research Gate.

In-depth instructions appear at the top of **Settings → Molecule Research Gate** in your WordPress admin.

== Installation ==

1. Install and activate WooCommerce.
2. Upload this plugin or install it from your copy of the codebase, then activate “Molecule Research Gate”.
3. Go to **Settings → Molecule Research Gate**, read the guide at the top, then configure URLs, branding, gated views, and optional coupon / Brevo lists.
4. Test as a logged-out visitor on a gated shop or product URL.

== Frequently Asked Questions ==

= Where is customer research data stored? =

In WordPress user meta (keys prefixed with `molecule_`). Open **Users → All Users**, edit the customer, and scroll to **Molecule research gate**.

= Do I need Brevo for the gate to work? =

No. Brevo is optional and only required if you enable the newsletter checkbox and list IDs on this plugin’s settings screen.

== Changelog ==

= 1.0.4 =
* Added admin instructions panel on the settings screen and this readme.
