<?php
/**
 * Side Cart Header
 *
 * This template can be overridden by copying it to yourtheme/templates/side-cart-woocommerce/xoo-wsc-header.php.
 *
 * HOWEVER, on occasion we will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen.
 * @see     https://docs.xootix.com/side-cart-woocommerce/
 * @version 2.7.1
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

extract( Xoo_Wsc_Template_Args::cart_header() );

$headingHTML = $basketHTML = $closeHTML = $saveHTML = '';

?>


<?php ob_start(); ?>

<?php if( $showBasket ): ?>

<div class="xoo-wsch-basket">

	<span class="xoo-wsch-bki <?php echo esc_html($basketIcon) ?> xoo-wsch-icon"></span>
	
	<span class="xoo-wsch-items-count"><?php echo xoo_wsc_cart()->get_cart_count() ?></span>
</div>
<?php endif; ?>

<?php $basketHTML = ob_get_clean(); ?>



<?php ob_start(); ?>

<?php if( $heading ): ?>
	<span class="xoo-wsch-text"><?php echo $heading ?></span>
<?php endif; ?>

<?php $headingHTML = ob_get_clean(); ?>



<?php ob_start(); ?>

<?php if( $showCloseIcon ): ?>
	<span class="xoo-wsch-close <?php echo  $close_icon ?> xoo-wsch-icon"></span>
<?php endif; ?>
<?php $closeHTML = ob_get_clean(); ?>


<div class="xoo-wsch-top xoo-wsch-new">

	<?php if( $showNotifications ): ?>
		<?php xoo_wsc_cart()->print_notices_html( 'cart' ); ?>
	<?php endif; ?>	

	<?php foreach ( $headerLayout as $section => $elements ): ?>
		
		<div class="xoo-wsch-section xoo-wsch-sec-<?php echo $section ?>">
			<?php foreach ( $elements as $element ){
				echo ${$element.'HTML'};
			}
			?>
		</div>


	<?php endforeach; ?>

</div>