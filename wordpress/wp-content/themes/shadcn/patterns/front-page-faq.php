<?php
/**
 * Title: Front Page FAQ
 * Slug: shadcn/front-page-faq
 * Categories: shadcn
 * Description: FAQ accordion section for home page.
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}},"layout":{"type":"constrained","wideSize":"960px"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10)">
	<!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"clamp(2rem,4vw,3.5rem)","fontWeight":"800","lineHeight":"1.1"},"spacing":{"margin":{"bottom":"var:preset|spacing|7"}}}} -->
	<h2 class="wp-block-heading" style="margin-bottom:var(--wp--preset--spacing--7);font-size:clamp(2rem,4vw,3.5rem);font-style:normal;font-weight:400;line-height:1.1"><?php esc_html_e( "Got questions?", 'shadcn' ); ?><br><span class="has-muted-foreground-color has-text-color"><?php esc_html_e( "We've got answers.", 'shadcn' ); ?></span></h2>
	<!-- /wp:heading -->

	<!-- wp:group {"className":"molecule-faq-list","layout":{"type":"constrained"}} -->
	<div class="wp-block-group molecule-faq-list">
		<!-- wp:details {"className":"molecule-faq-item"} -->
		<details class="wp-block-details molecule-faq-item"><summary><?php esc_html_e( 'Will my order be protected in transit?', 'shadcn' ); ?></summary><p><?php esc_html_e( 'Each shipment is cushioned, sealed, and inspected to safeguard contents from our facility to your destination. Packaging is specified to withstand standard carrier handling without compromising product integrity.', 'shadcn' ); ?></p></details>
		<!-- /wp:details -->

		<!-- wp:details {"className":"molecule-faq-item"} -->
		<details class="wp-block-details molecule-faq-item"><summary><?php esc_html_e( 'How do you verify product quality?', 'shadcn' ); ?></summary><p><?php esc_html_e( 'Every lot undergoes independent testing for identity, purity, and composition prior to release. We source from audited suppliers and maintain documentation confirming each batch meets specification.', 'shadcn' ); ?></p></details>
		<!-- /wp:details -->

		<!-- wp:details {"className":"molecule-faq-item"} -->
		<details class="wp-block-details molecule-faq-item"><summary><?php esc_html_e( 'When should I expect delivery?', 'shadcn' ); ?></summary><p><?php esc_html_e( 'Standard delivery typically arrives within 3-5 business days after order processing. Expedited options are available at checkout for shorter timelines; processing generally requires one business day.', 'shadcn' ); ?></p></details>
		<!-- /wp:details -->

		<!-- wp:details {"className":"molecule-faq-item"} -->
		<details class="wp-block-details molecule-faq-item"><summary><?php esc_html_e( 'How should I store these products?', 'shadcn' ); ?></summary><p><?php esc_html_e( 'Follow the instructions provided on each product page and label. Unless otherwise specified, store items in a cool, dry, dark environment and avoid temperature fluctuations.', 'shadcn' ); ?></p></details>
		<!-- /wp:details -->

		<!-- wp:details {"className":"molecule-faq-item"} -->
		<details class="wp-block-details molecule-faq-item"><summary><?php esc_html_e( 'How do I track an order?', 'shadcn' ); ?></summary><p><?php esc_html_e( 'Once your order ships, a tracking number is emailed to you. You can also log in to your account to view status updates and tracking details.', 'shadcn' ); ?></p></details>
		<!-- /wp:details -->

		<!-- wp:details {"className":"molecule-faq-item"} -->
		<details class="wp-block-details molecule-faq-item"><summary><?php esc_html_e( 'What is your return or refund policy?', 'shadcn' ); ?></summary><p><?php esc_html_e( 'If an issue arises, contact us within 30 days of receipt through our contact page. We will troubleshoot and, when eligible, arrange a replacement or refund. Certain items may be final sale depending on their nature.', 'shadcn' ); ?></p></details>
		<!-- /wp:details -->

		<!-- wp:details {"className":"molecule-faq-item"} -->
		<details class="wp-block-details molecule-faq-item"><summary><?php esc_html_e( 'Which payment methods are available?', 'shadcn' ); ?></summary><p><?php esc_html_e( 'We accept major credit and debit cards as well as PayPal, processed through a secure, encrypted gateway.', 'shadcn' ); ?></p></details>
		<!-- /wp:details -->

		<!-- wp:details {"className":"molecule-faq-item"} -->
		<details class="wp-block-details molecule-faq-item"><summary><?php esc_html_e( 'Do I need an account to purchase?', 'shadcn' ); ?></summary><p><?php esc_html_e( 'Browsing is available without an account. Creating an account streamlines checkout, stores shipping details, and provides access to order history and tracking in one location.', 'shadcn' ); ?></p></details>
		<!-- /wp:details -->

		<!-- wp:details {"className":"molecule-faq-item"} -->
		<details class="wp-block-details molecule-faq-item"><summary><?php esc_html_e( 'Can I place bulk or volume orders?', 'shadcn' ); ?></summary><p><?php esc_html_e( 'Yes. Share your requirements through our contact page and we will provide volume pricing and coordinate any special specifications.', 'shadcn' ); ?></p></details>
		<!-- /wp:details -->

		<!-- wp:details {"className":"molecule-faq-item"} -->
		<details class="wp-block-details molecule-faq-item"><summary><?php esc_html_e( 'Do you ship outside the United States?', 'shadcn' ); ?></summary><p><?php esc_html_e( 'Our primary service area is the United States. We review international requests individually, so please contact us to confirm options for your destination. Import duties, taxes, or customs fees, when applicable, are the responsibility of the recipient.', 'shadcn' ); ?></p></details>
		<!-- /wp:details -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
