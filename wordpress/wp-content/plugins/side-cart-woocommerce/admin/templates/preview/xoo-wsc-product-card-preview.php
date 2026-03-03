<!-- View Link HTML  -->
<?php ob_start(); ?>
<?php printf( '<a class="xoo-wsc-smr-link" href="#">%1$s</a>', '<i class="xoo-wsc-icon-external-link"></i>'. __( 'View', 'side-cart-woocommerce' ) ); ?>
<?php $viewLinkHTML = ob_get_clean(); ?>

<!-- Price HTML  -->
<?php ob_start(); ?>
<# if ( data.product.showPprice && !data.product.oneLiner ) { #>
	<div class="xoo-wsc-card-price">
		<?php echo __( 'Price: ', 'side-cart-woocommerce' ); ?>
			<# if( data.product.priceType === "actual" ){ #>
			<?php echo $product_price; ?>
		<# }else{ #>
			<?php echo $product_sale_price ?>
		<# } #>
	</div>
<# } #>
<?php $priceHTML = ob_get_clean(); ?>

<!-- Total HTML -->
<?php ob_start(); ?>
<# if ( data.product.showPtotal && !data.product.oneLiner ) { #>
	<span class="xoo-wsc-card-ptotal"><?php echo $product_subtotal ?></span>
<# } #>
<?php $totalHTML = ob_get_clean(); ?>


<!-- Name HTML -->
<?php ob_start(); ?>
<# if ( data.product.showPname ) { #>
	<span class="xoo-wsc-pname"><?php echo $product_name; ?></span>
<# } #>
<?php $nameHTML = ob_get_clean(); ?>


<!-- Meta HTML -->
<?php ob_start(); ?>
<# if ( data.product.showPmeta ) { #>
	<?php echo $product_meta ?>
<# } #>
<?php $metaHTML = ob_get_clean(); ?>


<?php $priceSavingsHTML = $totalSavingsHTML = ''; ?>

<?php if( isset( $savings ) ): ?>

	<?php ob_start(); ?>

	<# if ( data.product.showPriceSavings ) { #>

		<div class="xoo-wsc-psavings">
			<# if ( data.product.savingsUnit === "amount" ) { #>
				<?php echo $savings['price_amount'] ?>
			<# }else{ #>
				<?php echo $savings['price_perc'] ?>
			<# } #>
		</div>

	<# } #>

	<?php $priceSavingsHTML = ob_get_clean(); ?>


	<?php ob_start(); ?>

	<# if ( data.product.showTotalSavings ) { #>

		<div class="xoo-wsc-psavings">
			<# if ( data.product.savingsUnit === "amount" ) { #>
				<?php echo $savings['total_amount'] ?>
			<# }else{ #>
				<?php echo $savings['price_perc'] ?>
			<# } #>
		</div>

	<# } #>

	<?php $totalSavingsHTML = ob_get_clean(); ?>

<?php endif; ?>



<!-- Quantity HTML -->
<?php ob_start(); ?>
<div class="xoo-wsc-qty-box-cont">
	<# if ( data.product.oneLiner ) { #>
		<div class="xoo-wsc-qty-price">
			<span><?php echo $product_quantity; ?></span>
			<span>X</span>
			<span>
				<# if( data.product.priceType === "actual" ){ #>
					<?php echo $product_price; ?>
				<# }else{ #>
					<?php echo $product_sale_price ?>
				<# } #>
			</span>
			<span>=</span>
			<span><?php echo $product_subtotal ?></span>
		</div>

	<# }else{ #>

		<# if ( data.product.showPqty ) { #>
			<span class="xoo-wsc-sml-qty"><?php _e( 'Qty:', 'side-cart-woocommerce' ) ?> <?php echo $product_quantity; ?></span>
		<# } #>

	<# } #>

	<?php echo $totalHTML ?>

</div>
<?php $qtyHTML = ob_get_clean(); ?>


<div class="xoo-wsc-product-cont <# if (data.card.hasBack) { #>xoo-wsc-has-back<# } #>">

	<div class="xoo-wsc-product">

		<div class="xoo-wsc-card-cont">

			<# if ( data.product.showPdel ) { #>

				<# if ( "icon" === data.product.deleteType  ) { #>
					<span class="xoo-wsc-smr-del {{data.product.deleteIcon}}"></span>
				<# }else{ #>
					<span class="xoo-wsc-smr-del xoo-wsc-del-txt">{{data.product.deleteText}}</span>
				<# } #>

			<# } #>


			<div class="xoo-wsc-img-col magictime">

				<# if ( data.product.showPImage ) { #>
					<?php echo $product_thumbnail; ?>
				<# } #>

			</div>


			<# if ( data.card.hasBack ) { #>

			<div class="xoo-wsc-sm-back-cont">

				<div class="xoo-wsc-sm-back">

					<# if ( data.card.backShow.name ) { #>
						<?php echo $nameHTML; ?>
					<# } #>

					<# if ( data.card.backShow.meta ) { #>
						<?php echo $metaHTML; ?>
					<# } #>

					<# if ( data.card.backShow.link ) { #>
						<?php echo $viewLinkHTML; ?>
					<# } #>


					<# if ( data.card.backShow.price ) { #>
						<?php echo $priceHTML; ?>
					<# } #>

					<# if ( data.card.backShow.price_save ) { #>
						<?php echo $priceSavingsHTML; ?>
					<# } #>

					<# if ( data.card.backShow.qty ) { #>
						<?php echo $qtyHTML; ?>
					<# } #>

					<# if ( data.card.backShow.total_save ) { #>
						<?php echo $totalSavingsHTML; ?>
					<# } #>


				</div>

			</div>

			<# } #>
			
		</div>


		<div class="xoo-wsc-sm-front">

			<span class="xoo-wsc-sm-emp"></span>

			<# if ( !data.card.backShow.name || data.card.visibility === 'all_on_front' ) { #>
				<?php echo $nameHTML; ?>
			<# } #>

			<# if ( !data.card.backShow.price || data.card.visibility === 'all_on_front' ) { #>
				<?php echo $priceHTML; ?>
			<# } #>

			<# if ( !data.card.backShow.price_save || data.card.visibility === 'all_on_front' ) { #>
				<?php echo $priceSavingsHTML; ?>
			<# } #>

			<# if ( !data.card.backShow.meta || data.card.visibility === 'all_on_front' ) { #>
				<?php echo $metaHTML; ?>
			<# } #>

			<# if ( !data.card.backShow.qty || data.card.visibility === 'all_on_front' ) { #>
				<?php echo $qtyHTML; ?>
			<# } #>

			<# if ( !data.card.backShow.total_save || data.card.visibility === 'all_on_front' ) { #>
				<?php echo $totalSavingsHTML; ?>
			<# } #>

			
		</div>

	</div>

</div>