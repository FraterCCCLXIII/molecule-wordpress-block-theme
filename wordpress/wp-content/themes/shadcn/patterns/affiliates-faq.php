<?php
/**
 * Title: Affiliates FAQ
 * Slug: shadcn/affiliates-faq
 * Categories: shadcn
 * Description: FAQ section for the Affiliate Program page.
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|8"}},"color":{"background":"#f9fafb"},"border":{"top":{"color":"var:preset|color|border","width":"1px"}}},"layout":{"type":"constrained","wideSize":"960px"}} -->
<div class="wp-block-group alignfull has-background" style="border-top-color:var(--wp--preset--color--border);border-top-width:1px;background-color:#f9fafb;padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--8)">
	<!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"clamp(1.25rem,2.2vw,1.75rem)","fontWeight":"800"},"spacing":{"margin":{"bottom":"var:preset|spacing|7"}}}} -->
	<h2 class="wp-block-heading" style="margin-bottom:var(--wp--preset--spacing--7);font-size:clamp(1.25rem,2.2vw,1.75rem);font-style:normal;font-weight:800;line-height:1.1"><?php esc_html_e( 'Common Questions', 'shadcn' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:group {"className":"molecule-faq-list","layout":{"type":"constrained"}} -->
	<div class="wp-block-group molecule-faq-list">
		<!-- wp:details {"className":"molecule-faq-item"} -->
		<details class="wp-block-details molecule-faq-item"><summary><?php esc_html_e( 'What percentage do partners earn?', 'shadcn' ); ?></summary><!-- wp:paragraph -->
<p><?php esc_html_e( 'Approved partners earn 20% on orders completed through their referral link. Earnings are not capped and do not time out.', 'shadcn' ); ?></p>
<!-- /wp:paragraph --></details>
		<!-- /wp:details -->

		<!-- wp:details {"className":"molecule-faq-item"} -->
		<details class="wp-block-details molecule-faq-item"><summary><?php esc_html_e( 'How are payouts handled?', 'shadcn' ); ?></summary><!-- wp:paragraph -->
<p><?php esc_html_e( 'We settle partner balances once per month through Venmo, after your account meets the minimum payout amount.', 'shadcn' ); ?></p>
<!-- /wp:paragraph --></details>
		<!-- /wp:details -->

		<!-- wp:details {"className":"molecule-faq-item"} -->
		<details class="wp-block-details molecule-faq-item"><summary><?php esc_html_e( 'What is the minimum payout amount?', 'shadcn' ); ?></summary><!-- wp:paragraph -->
<p><?php esc_html_e( 'Payments are released when your balance reaches $50. Anything below that threshold carries forward to the following month.', 'shadcn' ); ?></p>
<!-- /wp:paragraph --></details>
		<!-- /wp:details -->

		<!-- wp:details {"className":"molecule-faq-item"} -->
		<details class="wp-block-details molecule-faq-item"><summary><?php esc_html_e( 'How are referrals tracked?', 'shadcn' ); ?></summary><!-- wp:paragraph -->
<p><?php esc_html_e( 'Your custom link tags incoming traffic to your partner account. When a referred visitor checks out, the sale is credited to you automatically.', 'shadcn' ); ?></p>
<!-- /wp:paragraph --></details>
		<!-- /wp:details -->

		<!-- wp:details {"className":"molecule-faq-item"} -->
		<details class="wp-block-details molecule-faq-item"><summary><?php esc_html_e( 'When will I hear back after applying?', 'shadcn' ); ?></summary><!-- wp:paragraph -->
<p><?php esc_html_e( 'Most applications receive a response within two to three business days. Accepted partners get onboarding details and their referral link by email.', 'shadcn' ); ?></p>
<!-- /wp:paragraph --></details>
		<!-- /wp:details -->
	</div>
	<!-- /wp:group -->

	<!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|7"}}}} -->
	<p style="margin-top:var(--wp--preset--spacing--7)"><?php esc_html_e( 'Need something else answered?', 'shadcn' ); ?> <a href="/contact"><?php esc_html_e( 'Reach out to our team', 'shadcn' ); ?></a>.</p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
