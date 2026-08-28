<?php
/**
 * Title: Affiliates Benefits
 * Slug: shadcn/affiliates-benefits
 * Categories: shadcn
 * Description: Affiliate program benefit cards.
 */

$benefit_icons = array(
	'revenue'  => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
	'link'     => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
	'research' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2v6a2 2 0 0 0 .245.96l5.51 10.08A2 2 0 0 1 18 22H6a2 2 0 0 1-1.755-2.96l5.51-10.08A2 2 0 0 0 10 8V2"/><path d="M6.453 15h11.094"/><path d="M8.5 2h7"/></svg>',
);

$benefit_card = static function ( $icon_key, $title, $description ) use ( $benefit_icons ) {
	$icon_markup = $benefit_icons[ $icon_key ] ?? '';
	?>
<!-- wp:group {"className":"molecule-affiliates-benefit-card","style":{"border":{"radius":"16px","width":"1px","color":"#e5e7eb"},"spacing":{"padding":{"top":"var:preset|spacing|6","bottom":"var:preset|spacing|6","left":"var:preset|spacing|6","right":"var:preset|spacing|6"}},"color":{"background":"#f9fafb"}},"layout":{"type":"default"}} -->
<div class="wp-block-group molecule-affiliates-benefit-card has-background" style="border-color:#e5e7eb;border-width:1px;border-radius:16px;background-color:#f9fafb;padding-top:var(--wp--preset--spacing--6);padding-right:var(--wp--preset--spacing--6);padding-bottom:var(--wp--preset--spacing--6);padding-left:var(--wp--preset--spacing--6)">
	<div class="molecule-affiliates-benefit-card__icon" aria-hidden="true"><?php echo $icon_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG markup. ?></div>
	<!-- wp:heading {"level":3} -->
	<h3 class="wp-block-heading molecule-affiliates-benefit-card__title"><?php echo esc_html( $title ); ?></h3>
	<!-- /wp:heading -->
	<!-- wp:paragraph {"textColor":"muted-foreground"} -->
	<p class="has-muted-foreground-color has-text-color molecule-affiliates-benefit-card__text"><?php echo esc_html( $description ); ?></p>
	<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
	<?php
};
?>
<!-- wp:group {"align":"full","className":"molecule-affiliates-benefits","style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}},"layout":{"type":"constrained","wideSize":"1280px"}} -->
<div class="wp-block-group alignfull molecule-affiliates-benefits" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10)">
	<!-- wp:columns {"className":"molecule-affiliates-benefits__grid","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|6"}}}} -->
	<div class="wp-block-columns molecule-affiliates-benefits__grid">
		<!-- wp:column -->
		<div class="wp-block-column">
			<?php $benefit_card( 'revenue', __( 'Generous Revenue Share', 'shadcn' ), __( 'Collect 20% on purchases made through your referral URL, with no ceiling on what you can earn over time.', 'shadcn' ) ); ?>
		</div>
		<!-- /wp:column -->
		<!-- wp:column -->
		<div class="wp-block-column">
			<?php $benefit_card( 'link', __( 'Dedicated Referral URL', 'shadcn' ), __( 'Receive a trackable link built for your account — ideal for newsletters, social posts, or lab community channels.', 'shadcn' ) ); ?>
		</div>
		<!-- /wp:column -->
		<!-- wp:column -->
		<div class="wp-block-column">
			<?php $benefit_card( 'research', __( 'Research-Grade Supply', 'shadcn' ), __( 'Recommend peptides that ship from the U.S. and are backed by third-party testing — a brand built for serious laboratory work.', 'shadcn' ) ); ?>
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
