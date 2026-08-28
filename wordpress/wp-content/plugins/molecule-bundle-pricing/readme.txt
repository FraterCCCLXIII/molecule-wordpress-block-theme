=== Molecule Bundle Pricing ===
Contributors: molecule
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later

Quantity-tier bundle pricing for WooCommerce products (Buy 1 / 3 / 6+ style), with
global defaults, per-product and per-variation overrides, and server-side discount
enforcement.

== Description ==

Adds a tiered quantity selector above the Add to cart button on single product pages.
Customers pick a bundle (e.g. Buy 1 / Buy 3 / Buy 6+) and the matching percentage
discount is applied. Discounts are enforced server-side based on the cart line
quantity, so the displayed savings are always real.

= Configuration =

* Global defaults: WooCommerce > Bundle Pricing.
* Per product: the "Bundle Pricing" tab in the product data panel. Enable/disable and
  optionally define custom tiers. Leave custom tiers empty to inherit the site default.
* Per variation: a "Bundle tier override" block under each variation. Leave empty to
  inherit the parent product / site default tiers.

Resolution order for tiers: variation override, then product override, then global
defaults.

= Styling contract =

The plugin emits markup with `.molecule-bundle-*` class names and ships only minimal
structural CSS. The active theme paints the look. Class-name contract:

* .molecule-bundle-tiers / .molecule-bundle-tiers__list
* .molecule-bundle-tier (+ --active, --open-ended)
* .molecule-bundle-tier__badge / __label / __sublabel / __price / __price-was
* .molecule-bundle-tier__stepper / __step / __qty

== Requirements ==

* WooCommerce (active).

== Changelog ==

= 1.0.1 =
* Hide the redundant single-product Product Price block on products that show bundle tiers.

= 1.0.0 =
* Initial release.
