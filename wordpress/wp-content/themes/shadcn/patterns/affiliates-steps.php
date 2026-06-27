<?php
/**
 * Title: Affiliates How It Works
 * Slug: shadcn/affiliates-steps
 * Categories: shadcn
 * Description: Step-by-step affiliate program overview.
 */

$step_card = static function ( $number, $title, $text ) {
	?>
<!-- wp:group {"className":"molecule-affiliates-step","style":{"border":{"radius":"16px","width":"1px","color":"#e5e7eb"},"spacing":{"padding":{"top":"var:preset|spacing|6","bottom":"var:preset|spacing|6","left":"var:preset|spacing|6","right":"var:preset|spacing|6"}}},"backgroundColor":"background","layout":{"type":"default"}} -->
<div class="wp-block-group molecule-affiliates-step has-background-background-color has-background" style="border-color:#e5e7eb;border-width:1px;border-radius:16px;padding-top:var(--wp--preset--spacing--6);padding-right:var(--wp--preset--spacing--6);padding-bottom:var(--wp--preset--spacing--6);padding-left:var(--wp--preset--spacing--6)">
	<!-- wp:paragraph {"className":"molecule-affiliates-step__number"} -->
	<p class="molecule-affiliates-step__number"><?php echo esc_html( $number ); ?></p>
	<!-- /wp:paragraph -->
	<!-- wp:heading {"level":3,"className":"molecule-affiliates-step__title"} -->
	<h3 class="wp-block-heading molecule-affiliates-step__title"><?php echo esc_html( $title ); ?></h3>
	<!-- /wp:heading -->
	<!-- wp:paragraph {"textColor":"muted-foreground"} -->
	<p class="has-muted-foreground-color has-text-color molecule-affiliates-step__text"><?php echo esc_html( $text ); ?></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
	<?php
};
?>
<!-- wp:group {"align":"full","className":"molecule-affiliates-steps","style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}},"color":{"background":"#f9fafb"},"border":{"top":{"color":"var:preset|color|border","width":"1px"},"bottom":{"color":"var:preset|color|border","width":"1px"}}},"layout":{"type":"constrained","wideSize":"1280px"}} -->
<div class="wp-block-group alignfull molecule-affiliates-steps has-background" style="border-top-color:var(--wp--preset--color--border);border-top-width:1px;border-bottom-color:var(--wp--preset--color--border);border-bottom-width:1px;background-color:#f9fafb;padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10)">
	<!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"clamp(2rem,4vw,2.5rem)","fontWeight":"800"},"spacing":{"margin":{"bottom":"var:preset|spacing|3"}}}} -->
	<h2 class="wp-block-heading" style="margin-bottom:var(--wp--preset--spacing--3);font-size:clamp(2rem,4vw,2.5rem);font-style:normal;font-weight:800;line-height:1.1"><?php esc_html_e( 'Getting Started', 'shadcn' ); ?></h2>
	<!-- /wp:heading -->
	<!-- wp:paragraph {"textColor":"muted-foreground","className":"molecule-affiliates-steps__intro","style":{"typography":{"lineHeight":"1.65"},"spacing":{"margin":{"bottom":"var:preset|spacing|7"}}}} -->
	<p class="molecule-affiliates-steps__intro has-muted-foreground-color has-text-color" style="margin-bottom:var(--wp--preset--spacing--7);line-height:1.65"><?php esc_html_e( 'Four straightforward steps from application to your first commission payout.', 'shadcn' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:columns {"className":"molecule-affiliates-steps__grid","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|6"}}}} -->
	<div class="wp-block-columns molecule-affiliates-steps__grid">
		<!-- wp:column -->
		<div class="wp-block-column">
			<?php $step_card( '01', __( 'Apply', 'shadcn' ), __( 'Submit the short application on this page. Our team reviews each request by hand.', 'shadcn' ) ); ?>
		</div>
		<!-- /wp:column -->
		<!-- wp:column -->
		<div class="wp-block-column">
			<?php $step_card( '02', __( 'Get approved', 'shadcn' ), __( 'After approval, we send your personal referral code and shareable link.', 'shadcn' ) ); ?>
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->

	<!-- wp:columns {"className":"molecule-affiliates-steps__grid","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|6"},"margin":{"top":"var:preset|spacing|6"}}}} -->
	<div class="wp-block-columns molecule-affiliates-steps__grid" style="margin-top:var(--wp--preset--spacing--6)">
		<!-- wp:column -->
		<div class="wp-block-column">
			<?php $step_card( '03', __( 'Share your link', 'shadcn' ), __( 'Publish your link wherever your audience lives — email lists, social channels, or your own site.', 'shadcn' ) ); ?>
		</div>
		<!-- /wp:column -->
		<!-- wp:column -->
		<div class="wp-block-column">
			<?php $step_card( '04', __( 'Get paid', 'shadcn' ), __( 'Receive 20% on completed orders from your referrals, deposited through Venmo each month.', 'shadcn' ) ); ?>
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
