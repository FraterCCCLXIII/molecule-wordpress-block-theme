<?php

$headingHTML = $basketHTML = $closeHTML = '';

?>


<?php ob_start(); ?>

<!-- Heading Icon -->
<# if ( data.header.showBasketIcon ) { #>

<div class="xoo-wsch-basket">
	<span class="xoo-wsch-bki {{data.basket.icon}} xoo-wsch-icon"></span>
	<span class="xoo-wsch-items-count"><?php echo $quantity; ?></span>
</div>

<# } #>

<?php $basketHTML = ob_get_clean(); ?>


<!-- Heading -->
<?php ob_start(); ?>

<# if ( data.header.heading ) { #>
	<span class="xoo-wsch-text">{{data.header.heading}}</span>
<# } #>

<?php $headingHTML = ob_get_clean(); ?>



<!-- Close Icon -->
<?php ob_start(); ?>

<# if ( data.header.showCloseIcon ) { #>
	<span class="xoo-wsch-close {{data.header.closeIcon}} xoo-wsch-icon"></span>
<# } #>

<?php $closeHTML = ob_get_clean(); ?>



<div class="xoo-wsch-top xoo-wsch-new">
	
	<# _.each( data.header.layout, function( elements, section ) { #>

		<div class="xoo-wsch-section xoo-wsch-sec-{{section}}">
			<# _.each( elements, function( element ) { #>

				<# if( element === "basket" ){ #>
					<?php echo $basketHTML; ?>
				<# } #>

				<# if( element === "close" ){ #>
					<?php echo $closeHTML; ?>
				<# } #>

				<# if( element === "heading" ){ #>
					<?php echo $headingHTML; ?>
				<# } #>


			<# }) #>
			
		</div>

	<# }) #>
	
	

</div>