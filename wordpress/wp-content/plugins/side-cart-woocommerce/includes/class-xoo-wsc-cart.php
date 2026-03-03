<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class Xoo_Wsc_Cart{

	protected static $_instance = null;

	public $notices = array();
	public $glSettings;
	public $coupons = array();
	public $addedToCart = false;
	public $bundleItems = array();


	public static function get_instance(){
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	
	public function __construct(){
		$this->glSettings = xoo_wsc_helper()->get_general_option();
		$this->hooks();
	}

	public function hooks(){
		add_action( 'wc_ajax_xoo_wsc_update_item_quantity', array( $this, 'update_item_quantity' ) );

		add_action( 'wc_ajax_xoo_wsc_refresh_fragments', array( $this, 'get_refreshed_fragments' ) );

		add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'set_ajax_fragments' ) );
		
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'set_ajax_fragments' ) );


		add_action( 'wc_ajax_xoo_wsc_add_to_cart', array( $this, 'add_to_cart' ) );

		add_action( 'woocommerce_add_to_cart', array( $this, 'added_to_cart' ), 10, 6 );

		add_filter( 'pre_option_woocommerce_cart_redirect_after_add', array( $this, 'prevent_cart_redirect' ), 20 );

	}

	public function prevent_cart_redirect( $value ){

		$ajaxAtc = $this->glSettings['m-ajax-atc'];

		if( $ajaxAtc !== 'no' ){
			$value = 'no';
		}

		return $value;		
	}

	/* Add to cart is performed by woocommerce as 'add-to-cart' is passed */
	public function add_to_cart(){

		if( !isset( $_POST['add-to-cart'] ) ) return;

		if( $this->addedToCart ){
			// trigger action for added to cart in ajax
			do_action( 'woocommerce_ajax_added_to_cart', intval( $_POST['add-to-cart'] ) );
			$this->get_refreshed_fragments();
		}
		else{
			ob_start();
			xoo_wsc_helper()->get_template('global/markup-notice.php');
			$notice = ob_get_clean();

			wp_send_json(array(
				'error' 	=> 1,
				'notice' 	=> $notice
			));
		}

	}


	public function added_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ){
		//$this->set_notice( __( 'Item added to cart', 'side-cart-woocommerce' ), 'sucess' );
		$this->addedToCart = 'yes';
	}


	public function set_notice( $notice, $type = 'success' ){
		$this->notices[] = xoo_wsc_notice_html( $notice, $type );
	}



	public function print_notices_html( $section = 'cart', $wc_cart_notices = true, $clean = true ){

		if( isset( $_POST['noticeSection'] ) && $_POST['noticeSection'] !== $section ) return;

		if( $wc_cart_notices ){

			do_action( 'woocommerce_check_cart_items' );

			//Add WC notices
			$wc_notices = wc_get_notices( 'error' );

			foreach ( $wc_notices as $wc_notice ) {
				$this->set_notice( $wc_notice['notice'], 'error' );
			}

			wc_clear_notices();

		}

		$notices = apply_filters( 'xoo_wsc_notices_before_print', $this->notices, $section );

		$notices_html = sprintf( '<div class="xoo-wsc-notice-container" data-section="%1$s"><ul class="xoo-wsc-notices">%2$s</ul></div>', $section, implode( '' , $notices )  );

		echo apply_filters( 'xoo_wsc_print_notices_html', $notices_html, $notices, $section );
		
		if( $clean ){
			$this->notices = array();
		}

	}




	public function update_item_quantity(){


		$cart_key 	= sanitize_text_field( $_POST['cart_key'] );
		$new_qty 	= (float) $_POST['qty'];

		if( !is_numeric( $new_qty ) || $new_qty < 0 || !$cart_key ){
			//$this->set_notice( __( 'Something went wrong', 'side-cart-woocommerce' ) );
		}
		
		$validated = apply_filters( 'xoo_wsc_update_quantity', true, $cart_key, $new_qty );

		if( $validated && !empty( WC()->cart->get_cart_item( $cart_key ) ) ){

			$updated = $new_qty == 0 ? WC()->cart->remove_cart_item( $cart_key ) : WC()->cart->set_quantity( $cart_key, $new_qty );

			if( $updated ){

				if( $new_qty == 0 ){

					$notice = __( 'Item removed', 'side-cart-woocommerce' );

					$notice .= '<span class="xoo-wsc-undo-item" data-key="'.$cart_key.'">'.__('Undo?','side-cart-woocommerce').'</span>';  

				}
				else{
					$notice = __( 'Item updated', 'side-cart-woocommerce' );
				}

				//$this->set_notice( $notice, 'success' );
				
			}
		}


		$this->get_refreshed_fragments();

		die();
	}


	public function set_ajax_fragments($fragments){

		WC()->cart->calculate_totals();
		
		ob_start();
		xoo_wsc_helper()->get_template( 'xoo-wsc-container.php' );
		$container = ob_get_clean();

		ob_start();
		xoo_wsc_helper()->get_template( 'xoo-wsc-slider.php' );
		$slider = ob_get_clean();

		ob_start();
		xoo_wsc_helper()->get_template( 'xoo-wsc-shortcode.php' );
		$shortcode = ob_get_clean();

		$fragments['div.xoo-wsc-container'] 		= $container; //Cart content
		$fragments['div.xoo-wsc-slider'] 			= $slider;// Slider
		$fragments['div.xoo-wsc-sc-cont'] 			= $shortcode;
		
		return $fragments;

	}

	public function get_refreshed_fragments(){
		WC_AJAX::get_refreshed_fragments();
	}


	public function get_cart_count(){
		if( $this->glSettings['m-bk-count'] === 'items' ){
			return count( WC()->cart->get_cart() );
		}
		else{
			return WC()->cart->get_cart_contents_count();
		}
	}


	public function get_totals(){

		$totals = array();

		if( WC()->cart->is_empty() ) return $totals;

		$show = $this->glSettings['scf-show'];

		$showSubtotal 	= in_array( 'subtotal', $show );
		$showSavings 	= in_array( 'savings', $show );

		if( $showSavings ){

			$savings = $this->get_cart_total_savings();

			if( $savings ){
				$totals['savings'] = array(
					'label' 	=> $this->glSettings['sct-savings'],
					'value' 	=> wc_price( $savings ),
					'action' 	=> 'less'
				);
			}

		}

		if( $showSubtotal ){
			$totals['subtotal'] = array(
				'label' 	=> xoo_wsc_helper()->get_general_option('sct-subtotal'),
				'value' 	=> WC()->cart->get_cart_subtotal(),
			);
		}

		return apply_filters( 'xoo_wsc_cart_totals', $totals );

	}


	public function get_bundle_items(){

		if( !empty( $this->bundleItems ) ){
			return $this->bundleItems;
		}

		$data = array(

			'bundled_items' => array(
				'key' 		=> 'bundled_items',
				'type' 		=> 'parent',
				'delete' 	=> true,
				'qtyUpdate' => true,
				'image' 	=> true,
				'link' 		=> true	
			),

			'bundled_by' => array(
				'key' 		=> 'bundled_by',
				'type' 		=> 'child',
				'delete' 	=> false,
				'qtyUpdate' => false,
				'image' 	=> true,
				'link' 		=> true
			),


			'mnm_contents' => array(
				'key' 		=> 'mnm_contents',
				'type' 		=> 'parent',
				'delete' 	=> true,
				'qtyUpdate' => true,
				'image' 	=> true,
				'link' 		=> true
			),


			'mnm_container' => array(
				'key' 		=> 'mnm_container',
				'type' 		=> 'child',
				'delete' 	=> false,
				'qtyUpdate' => false,
				'image' 	=> true,
				'link' 		=> true
			),

			'composite_children' => array(
				'key' 		=> 'composite_children',
				'type' 		=> 'parent',
				'delete' 	=> true,
				'qtyUpdate' => true,
				'image' 	=> true,
				'link' 		=> true
			),


			'composite_parent' => array(
				'key' 		=> 'composite_parent',
				'type' 		=> 'child',
				'delete' 	=> false,
				'qtyUpdate' => false,
				'image' 	=> true,
				'link' 		=> true
			),

			'woosb_ids' => array(
				'key' 		=> 'woosb_ids',
				'type' 		=> 'parent',
				'delete' 	=> true,
				'qtyUpdate' => true,
				'image' 	=> true,
				'link' 		=> true
			),

			'woosb_parent_id' => array(
				'key' 		=> 'woosb_parent_id',
				'type' 		=> 'child',
				'delete' 	=> false,
				'qtyUpdate' => false,
				'image' 	=> true,
				'link' 		=> true
			),
			
		);

		$this->bundleItems = apply_filters( 'xoo_wsc_product_bundle_items', $data );

		return $this->bundleItems;

	}


	public function is_bundle_item( $cart_item ){

		$bundleItems = $this->get_bundle_items();
		$isBundle = array_intersect_key( $bundleItems , $cart_item );
		return !empty( $isBundle ) ? array_values( array_intersect_key( $bundleItems , $cart_item ) )[0] : $isBundle;

	}


	public function get_cart_total_savings(){
		$savings = $this->get_cart_total_product_savings();
		return apply_filters( 'xoo_wsc_cart_savings', $savings  );
	}

	public function get_cart_total_product_savings() {

    	$savings = 0;

		foreach ( WC()->cart->get_cart() as $cart_item ) {

			$product = $cart_item['data'];

			// Regular price (could be variable/simple product)
			$regular_price = (float) $product->get_regular_price();
			$sale_price    = (float) $product->get_price();

			// If discounted
			if ( $regular_price > $sale_price ) {
			$savings += ( $regular_price - $sale_price ) * $cart_item['quantity'];
			}
		}

    	return apply_filters( 'xoo_wsc_cart_total_product_savings', $savings );
	}


	public function get_cart_item_savings( $cart_item ){

		$cart_item_key = $cart_item['key'];

		$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

		$price_savings 	= $total_savings = 0;

		$data = array();

		$product_regular_price 		= wc_prices_include_tax() ? wc_get_price_including_tax( $_product, [ 'price' => $_product->get_regular_price() ] ) : wc_get_price_excluding_tax( $_product, [ 'price' => $_product->get_regular_price() ] );

		$product_price         		= wc_prices_include_tax() ? wc_get_price_including_tax( $_product, [ 'price' => $_product->get_price() ] ) : wc_get_price_excluding_tax( $_product, [ 'price' => $_product->get_price() ] );


		$price_savings = $this->get_savings_data( $product_price, $product_regular_price );
		
		if( !empty( $price_savings ) ){
			$data['price'] = $price_savings;
		}


		$product_regular_total 		= $product_regular_price * $cart_item['quantity'];
		$product_subtotal 			= $product_price * $cart_item['quantity'];

		$total_savings = $this->get_savings_data( $product_subtotal, $product_regular_total );

		if( !empty( $total_savings ) ){
			$data['total'] = $total_savings;
		}

		
		return apply_filters( 'xoo_wsc_cart_item_savings', $data, $cart_item );

		
	}


	public function get_savings_data( $new_amount, $base_amount, $unit = '' ){

		$return 	= array();
		$savings 	= 0;

		if( $base_amount > $new_amount ){
			$savings = $base_amount - $new_amount;
		}

		if( $savings ){

			if( !$unit ){
				$unit = $this->glSettings['scb-prod-savings'];
			}

			if( $unit === 'amount' ){
				$price_savings_text = wc_price( $savings );
				
			}
			else{
				$price_savings_text = '<span>'.round( ($savings/$base_amount) * 100 ) .'%</span>';
			}

			$return['value'] 	= $savings;
			$return['text'] 	= sprintf( __( '<span %1$s>Save</span> %2$s', 'side-cart-woocommerce' ), 'class="xoo-wsc-psavlabel"', $price_savings_text  );

		}

		return $return;

		
	}


}

function xoo_wsc_cart(){
	return Xoo_Wsc_Cart::get_instance();
}
xoo_wsc_cart();
