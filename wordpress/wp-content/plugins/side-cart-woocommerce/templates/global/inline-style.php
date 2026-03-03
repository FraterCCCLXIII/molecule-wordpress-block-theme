<?php

/* Main */
$cartWidth 		= (int) $sy['scm-width'];
$cartheight 	= $sy['scm-height'];
$openFrom 		= $sy['scm-open-from'];
$fontFamily 	= $sy['scm-font'];

/* Basket */
$basketSize 	= $sy['sck-bk-size'];
$BasketMobile 	= $sy['sck-show-mobile'];
$showBasket 	= $sy['sck-enable'];
$basketPosition = $sy['sck-position'];
$basketShape 	= $sy['sck-shape'];
$basketIconSize = $sy['sck-size'];
$basketOffset 	= $sy['sck-offset'];
$basketHOffset 	= $sy['sck-hoffset'];
$countPosition 	= $sy['sck-count-pos'];
$basketBG 		= $sy['sck-basket-bg'];
$basketColor 	= $sy['sck-basket-color'];
$basketShadow 	= $sy['sck-basket-sh'];
$countBG 		= $sy['sck-count-bg'];
$countColor 	= $sy['sck-count-color'];

/* Header */
$headerOldLayout = isset( $sy['sch-new-layout'] ) && $sy['sch-new-layout'] !== "yes";
if( $headerOldLayout ){
	$closeIconAlign = $sy['sch-close-align'];
	$headingAlign 	= $sy['sch-head-align'];
}

$closeIconSize 	= $sy['sch-close-fsize'];
$headFontSize 	= $sy['sch-head-fsize'];
$headBGColor 	= $sy['sch-bgcolor'];
$headTxtColor 	= $sy['sch-txtcolor'];
$headBorder		= $sy['sch-border'];
$headPadding 	= $sy['sch-padding'];
$headIconSize 	= $sy['sch-basket-fsize'];
$headCountSize 	= $sy['sch-count-size'];

/* Body */
$bodyFontSize 	= $sy['scb-fsize'];
$bodyBGColor 	= $sy['scb-bgcolor'];
$bodyTxtColor 	= $sy['scb-txtcolor'];
$bodyIconSize 	= $sy['scb-icon-size'];


/* Product Card */
$bpCardCount 		= (int) $sy['scbp-card-count'];
$bpCardImgColor 	= $sy['scbp-card-img-color'];
$bpCardFrtColor 	= $sy['scbp-card-front-color'];
$bpCardBckColor 	= $sy['scbp-card-back-color'];
$bpCardBckTxtColor 	= $sy['scbp-card-backtxt-color'];
$bpCardTxtColor 	= $sy['scbp-card-txtcolor'];
$bpCardBorder 		= $sy['scbp-card-border'];
$bPCardimgwidth		= (int) $sy['scbp-card-imgw'];
$bPCardimgheight	= (int) $sy['scbp-card-imgh'];
$bPCardpadding 		= $sy['scbp-card-padding'];
$bpCardAnimTim 		= $sy['scbp-card-anim-time'];
$bPCardShadow		= $sy['scbp-card-shadow'];
$bPCardRadTop		= $sy['scbp-card-radius-top'];
$bPCardRadBtm		= $sy['scbp-card-radius-btm'];


/*Product Row */
$bpVarFormat 	= $sy['scbp-var-format'];
$bpDisplay 		= $sy['scbp-display'];
$bPpadding 		= $sy['scbp-padding'];
$bPimgwidth		= (int) $sy['scbp-imgw'];
$bPmargin		= $sy['scbp-margin'];
$bPradius		= (int) $sy['scbp-bradius'];
$bPshadow		= $sy['scbp-shadow'];
$bpBgColor		= $sy['scbp-bgcolor'];


/* Footer */
$footerStick 	= $sy['scf-stick'];
$buttonsOrder  	= $sy['scf-button-pos'];
$buttonPadding 	= $sy['scf-btn-padding'];
$buttonRows 	= $sy['scf-btns-row'];
$buttonTheme 	= $sy['scf-btns-theme'];
$buttonbgColor 	= $sy['scf-btn-bgcolor'];
$buttontxtColor = $sy['scf-btn-txtcolor'];
$buttonBorder 	= $sy['scf-btn-border'];

$HVbuttonbgColor 	= $sy['scf-btnhv-bgcolor'];
$HVbuttontxtColor 	= $sy['scf-btnhv-txtcolor'];
$HVbuttonBorder 	= $sy['scf-btnhv-border'];

$ftrPadding 	= $sy['scf-padding'];
$ftrBgColor 	= $sy['scf-bgcolor'];
$ftrTxtColor 	= $sy['scf-txtcolor'];
$ftrFsize 		= $sy['scf-fsize'];
$ftrShadow 		= $sy['scf-shadow'];


/* Shortcode */
$SCbasketSize 	= $sy['shbk-size'];
$SCbasketColor 	= $sy['shbk-color'];
$SCcountBG 		= $sy['shbk-count-bg'];
$SCcountColor 	= $sy['shbk-count-color'];
$SCtxtColor 	= $sy['shbk-txt-color'];

if( $buttonRows === 'three' ){
	$gridCols = '1fr 1fr 1fr';
}
elseif ( $buttonRows === 'two_one' ) {
	$gridCols = '2fr 2fr';
	echo 'a.xoo-wsc-ft-btn:nth-child(3){
		grid-column: 1/-1;
	}';
}
elseif ( $buttonRows === 'one_two' ) {
	$gridCols = '2fr 2fr';
	echo 'a.xoo-wsc-ft-btn:nth-child(1){
		grid-column: 1/-1;
	}';
}
else{
	$gridCols = 'auto';
}

?>



<?php if( $buttonTheme === 'custom' ): ?>

.xoo-wsc-ft-buttons-cont a.xoo-wsc-ft-btn, .xoo-wsc-container .xoo-wsc-btn {
	background-color: <?php echo $buttonbgColor ?>;
	color: <?php echo $buttontxtColor ?>;
	border: <?php echo $buttonBorder ?>;
	padding: <?php echo $buttonPadding ?>;
}

.xoo-wsc-ft-buttons-cont a.xoo-wsc-ft-btn:hover, .xoo-wsc-container .xoo-wsc-btn:hover {
	background-color: <?php echo $HVbuttonbgColor ?>;
	color: <?php echo $HVbuttontxtColor ?>;
	border: <?php echo $HVbuttonBorder ?>;
}

<?php endif; ?> 

.xoo-wsc-footer{
	background-color: <?php echo $ftrBgColor ?>;
	color: <?php echo $ftrTxtColor ?>;
	padding: <?php echo $ftrPadding ?>;
	box-shadow: <?php echo $ftrShadow ?>;
}

.xoo-wsc-footer, .xoo-wsc-footer a, .xoo-wsc-footer .amount{
	font-size: <?php echo $ftrFsize ?>px;
}

.xoo-wsc-btn .amount{
	color: <?php echo $buttontxtColor ?>
}

.xoo-wsc-btn:hover .amount{
	color: <?php echo $HVbuttontxtColor ?>;
}

.xoo-wsc-ft-buttons-cont{
	grid-template-columns: <?php echo $gridCols ?>;
}

.xoo-wsc-basket{
	<?php echo $basketPosition ?>: <?php echo $basketOffset ?>px;
	<?php echo $openFrom ?>: <?php echo $basketHOffset ?>px;
	background-color: <?php echo $basketBG ?>;
	color: <?php echo $basketColor ?>;
	box-shadow: <?php echo $basketShadow ?>;
	border-radius: <?php echo $basketShape === 'round' ? '50%' : '14px' ?>;
	display: <?php echo $showBasket === 'hide_empty' || $showBasket === 'always_hide' ? 'none' : 'flex'; ?>;
	width: <?php echo $basketSize; ?>px;
	height: <?php echo $basketSize; ?>px;
}

<?php if( $BasketMobile !== 'yes' ): ?>

@media only screen and (max-width: 600px) {
	.xoo-wsc-basket, .xoo-wsc-basket[style*='block']  {
		display: none!important;
	}
}

<?php endif; ?>

.xoo-wsc-bki{
	font-size: <?php echo $basketIconSize.'px' ?>
}

.xoo-wsc-items-count{
	<?php echo $countPosition === 'top_right' || $countPosition === 'top_left' ? 'top' : 'bottom' ?>: -9px;
	<?php echo $countPosition === 'top_right' || $countPosition === 'bottom_right' ? 'right' : 'left' ?>: -8px;
}

.xoo-wsc-items-count, .xoo-wsch-items-count{
	background-color: <?php echo $countBG ?>;
	color: <?php echo $countColor ?>;
}

.xoo-wsc-container, .xoo-wsc-slider{
	max-width: <?php echo $cartWidth ?>px;
	<?php echo $openFrom ?>: <?php echo -$cartWidth ?>px;
	<?php echo $cartheight === 'full' ? 'top: 0;bottom: 0' : 'max-height: 100vh' ?>;
	<?php echo $basketPosition ?>: 0;
	font-family: <?php echo $fontFamily; ?>
}


.xoo-wsc-cart-active .xoo-wsc-container, .xoo-wsc-slider-active .xoo-wsc-slider{
	<?php echo $openFrom ?>: 0;
}

<?php if( $footerStick !== 'yes' ): ?>

.xoo-wsc-container {
    overflow: auto;
}

.xoo-wsc-body{
	overflow: unset;
	flex-grow: 0;
}
.xoo-wsc-footer{
	flex-grow: 1;
}

<?php endif; ?>

.xoo-wsc-cart-active .xoo-wsc-basket{
	<?php echo $openFrom ?>: <?php echo $cartWidth ?>px;
}

.xoo-wsc-slider{
	right: -<?php echo $cartWidth ?>px;
}

span.xoo-wsch-close {
    font-size: <?php echo $closeIconSize ?>px;
}

<?php if( $headerOldLayout ): ?>
	.xoo-wsch-top{
		justify-content: <?php echo $headingAlign ?>;
	}
	span.xoo-wsch-close {
	    <?php echo $closeIconAlign ?>: 10px;
	}
<?php endif; ?>

.xoo-wsch-text{
	font-size: <?php echo $headFontSize ?>px;
}

.xoo-wsc-header{
	color: <?php echo $headTxtColor ?>;
	background-color: <?php echo $headBGColor ?>;
	border-bottom: <?php echo $headBorder ?>;
	padding: <?php echo $headPadding ?>;
}


.xoo-wsc-body{
	background-color: <?php echo $bodyBGColor ?>;
}

.xoo-wsc-products:not(.xoo-wsc-pattern-card), .xoo-wsc-products:not(.xoo-wsc-pattern-card) span.amount, .xoo-wsc-products:not(.xoo-wsc-pattern-card) a{
	font-size: <?php echo $bodyFontSize ?>px;
	color: <?php echo $bodyTxtColor ?>;
}

.xoo-wsc-products:not(.xoo-wsc-pattern-card) .xoo-wsc-product{
	padding: <?php echo $bPpadding ?>;
	margin: <?php echo $bPmargin ?>;
	border-radius: <?php echo $bPradius ?>px;
	box-shadow: <?php echo $bPshadow ?>;
	background-color: <?php echo $bpBgColor ?>;
}

<?php if( $bpDisplay === 'stretched' ): ?>
.xoo-wsc-sm-info{
	flex-grow: 1;
    align-self: stretch;
}
.xoo-wsc-sm-left {
    justify-content: space-evenly;
}
<?php else: ?>
.xoo-wsc-sum-col{
	justify-content: <?php echo $bpDisplay ?>;
}
<?php endif; ?>


/** Shortcode **/
.xoo-wsc-sc-count{
	background-color: <?php echo $SCcountBG ?>;
	color: <?php echo $SCcountColor ?>;
}

.xoo-wsc-sc-bki{
	font-size: <?php echo $SCbasketSize ?>px;
	color: <?php echo $SCbasketColor ?>;
}
.xoo-wsc-sc-cont{
	color: <?php echo $SCtxtColor ?>;
}

<?php if( $gl['m-viewcart-del'] === 'yes' ): ?>
.added_to_cart{
	display: none!important;
}
<?php endif; ?>

.xoo-wsc-product dl.variation {
	display: <?php echo $bpVarFormat === 'one_line' ? 'flex' : 'block' ?>;
}


.xoo-wsc-product-cont{
	padding: <?php echo $bPCardpadding ?>;
}

.xoo-wsc-products:not(.xoo-wsc-pattern-card) .xoo-wsc-img-col{
	width: <?php echo $bPimgwidth ?>%;
}

.xoo-wsc-pattern-card .xoo-wsc-img-col img{
	max-width: <?php echo $bPCardimgwidth ?>%;
	height: <?php echo ($bPCardimgheight > 0 ? $bPCardimgheight.'px' : 'auto') ?>;
}

.xoo-wsc-products:not(.xoo-wsc-pattern-card) .xoo-wsc-sum-col{
	width: <?php echo 100-$bPimgwidth ?>%;
}

.xoo-wsc-pattern-card .xoo-wsc-product-cont{
	width: <?php echo 100/$bpCardCount ?>% 
}

<?php if( $bpCardCount > 1 ): ?>
@media only screen and (max-width: 600px) {
	.xoo-wsc-pattern-card .xoo-wsc-product-cont  {
		width: 50%;
	}
}

<?php endif; ?>

.xoo-wsc-pattern-card .xoo-wsc-product{
	border: <?php echo $bpCardBorder ?>;
	box-shadow: <?php echo $bPCardShadow ?>;
}

<?php if( $bPCardimgwidth < 100 ): ?>
.xoo-wsc-pattern-card .xoo-wsc-img-col{
	background-color: <?php echo $bpCardImgColor ?>;
}
<?php endif; ?>

.xoo-wsc-sm-front{
	background-color: <?php echo $bpCardFrtColor ?>;
}
.xoo-wsc-pattern-card, .xoo-wsc-sm-front{
	border-bottom-left-radius: <?php echo $bPCardRadBtm; ?>px;
	border-bottom-right-radius: <?php echo $bPCardRadBtm; ?>px;
}
.xoo-wsc-pattern-card, .xoo-wsc-img-col img, .xoo-wsc-img-col, .xoo-wsc-sm-back-cont{
	border-top-left-radius: <?php echo $bPCardRadTop; ?>px;
	border-top-right-radius: <?php echo $bPCardRadTop; ?>px;
}
.xoo-wsc-sm-back{
	background-color: <?php echo $bpCardBckColor ?>;
}
.xoo-wsc-pattern-card, .xoo-wsc-pattern-card a, .xoo-wsc-pattern-card .amount{
	font-size: <?php echo $bodyFontSize ?>px;
}

.xoo-wsc-sm-front, .xoo-wsc-sm-front a, .xoo-wsc-sm-front .amount{
	color: <?php echo $bpCardTxtColor ?>;
}

.xoo-wsc-sm-back, .xoo-wsc-sm-back a, .xoo-wsc-sm-back .amount{
	color: <?php echo $bpCardBckTxtColor ?>;
}


.magictime {
    animation-duration: <?php echo $bpCardAnimTim ?>s;
}

<?php if( wp_is_mobile() && $sy['scb-playout'] === 'cards' && $sy['scbp-card-visible'] === 'back_hover' ) :?>
.xoo-wsc-img-col a{
	pointer-events: none;
}
<?php endif; ?>

<?php

if( WC()->cart->get_cart_contents_count() === 0 ){

	$shortcodeEls = xoo_wsc_frontend()->shortcodeEls;

	$hideEls = array();

	foreach ($gl['shbk-hide'] as $scElement ) {
		if( isset( $shortcodeEls[ $scElement ] ) ){
			$hideEls[] = $shortcodeEls[ $scElement ];
		}
	}

	if( !empty( $hideEls ) ){
		?>
		<?php echo implode(',', $hideEls ); ?>{
			display: none;
		}
		<?php
	}
}

?>

span.xoo-wsch-items-count{
	height: <?php echo $headCountSize ?>px;
	line-height: <?php echo $headCountSize ?>px;
	width: <?php echo $headCountSize ?>px;
}

span.xoo-wsch-icon{
	font-size: <?php echo $headIconSize; ?>px
}

.xoo-wsc-smr-del{
	font-size: <?php echo $bodyIconSize; ?>px
}