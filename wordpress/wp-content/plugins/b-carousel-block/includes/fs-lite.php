<?php
if ( !defined( 'ABSPATH' ) ) { exit; }

if ( ! function_exists( 'bicb_fs' ) ) {
	function bicb_fs() {
		global $bicb_fs;

		if ( !isset( $bicb_fs ) ) {
			require_once BICB_DIR_PATH . '/vendor/freemius-lite/start.php';

			$bicb_fs = fs_lite_dynamic_init( [
				'id'					=> '15342',
				'slug'					=> 'b-carousel-block',
				'__FILE__'				=> BICB_DIR_PATH . 'index.php',
				'premium_slug'			=> 'b-carousel-block-pro',
				'type'					=> 'plugin',
				'public_key'			=> 'pk_a45f62e2b56488230717561f70db4',
				'is_premium'			=> false,
				'premium_suffix'		=> 'Pro',
				'has_premium_version'	=> true,
				'has_addons'			=> false,
				'has_paid_plans'		=> true,
				'menu'					=> [
					'slug'			=> 'carousel-block',
					'first-path'	=> 'tools.php?page=carousel-block',
					'parent'		=> [
						'slug'	=> 'tools.php'
					],
					'contact'		=> false,
					'support'		=> false
				]
			] );
		}

		return $bicb_fs;
	}

	bicb_fs();
	do_action( 'bicb_fs_loaded' );
}
