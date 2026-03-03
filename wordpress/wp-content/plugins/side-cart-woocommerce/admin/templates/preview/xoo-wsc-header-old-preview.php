<div class="xoo-wsch-top">

	<# if ( data.header.showBasketIcon ) { #>

	<div class="xoo-wsch-basket">
		<span class="xoo-wscb-icon xoo-wsc-icon-bag2"></span>
		<span class="xoo-wscb-count"><?php echo $quantity ?></span>
	</div>

	<# } #>
	
	<span class="xoo-wsch-text">{{data.header.heading}}</span>
	
	<# if ( data.header.showCloseIcon ) { #>
	<span class="xoo-wsch-close {{data.header.closeIcon}}"></span>
	<# } #>


</div>