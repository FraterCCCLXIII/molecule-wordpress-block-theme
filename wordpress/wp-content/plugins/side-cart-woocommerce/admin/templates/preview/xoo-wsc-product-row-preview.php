<!-- Delete HTML -->
<?php ob_start(); ?>

<# if ( data.product.showPdel ) { #>

	<# if ( "icon" === data.product.deleteType ) { #>
		<span class="xoo-wsc-smr-del {{data.product.deleteIcon}}"></span>
	<# }else{ #>
		<span class="xoo-wsc-smr-del xoo-wsc-del-txt">{{data.product.deleteText}}</span>
	<# } #>

<# } #>

<?php $deleteHTML = ob_get_clean(); ?>

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


<div class="xoo-wsc-product">

	<# if ( data.product.showPImage ) { #>
		<div class="xoo-wsc-img-col">

			<?php echo $product_thumbnail; ?>
			

			<# if ( "image" === data.product.deletePosition ) { #>

				<?php echo $deleteHTML ?>

			<# } #>

		</div>

	<# } #>

	<div class="xoo-wsc-sum-col">

		<div class="xoo-wsc-sm-info">

			<div class="xoo-wsc-sm-left">

				<# if ( data.product.showPname ) { #>
					<span class="xoo-wsc-pname"><?php echo $product_name; ?></span>
				<# } #>
				
				<# if ( data.product.showPmeta ) { #>
					<?php echo $product_meta ?>
				<# } #>


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

					<?php echo $totalSavingsHTML; ?>

				<# }else{ #>

					<# if ( data.product.showPqty ) { #>
						<span class="xoo-wsc-sml-qty"><?php _e( 'Qty:', 'side-cart-woocommerce' ) ?> <?php echo $product_quantity; ?></span>
					<# } #>

					<div class="xoo-wsc-priceBox">

						<?php echo $priceSavingsHTML; ?>

						<# if ( data.product.showPprice ) { #>
							<div class="xoo-wsc-pprice">
								<?php echo __( 'Price: ', 'side-cart-woocommerce' ); ?>
									<# if( data.product.priceType === "actual" ){ #>
									<?php echo $product_price; ?>
								<# }else{ #>
									<?php echo $product_sale_price ?>
								<# } #>
							</div>
						<# } #>

					</div>

				<# } #>


			</div>

			<!-- End Quantity -->


			<div class="xoo-wsc-sm-right">

				<# if ( "default" === data.product.deletePosition ) { #>

					<?php echo $deleteHTML ?>

				<# } #>

				<# if ( !data.product.oneLiner ) { #>
					<?php echo $totalSavingsHTML; ?>
				<# } #>

				<# if ( data.product.showPtotal && !data.product.oneLiner ) { #>
					<span class="xoo-wsc-smr-ptotal"><?php echo $product_subtotal ?></span>
				<# } #>


			</div>

		</div>

	</div>

</div>