<?php
namespace BICB\Admin;

if ( !defined( 'ABSPATH' ) ) { exit; }

class SubMenu {
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'adminMenu' ] );
	}

	function adminMenu(){
		add_submenu_page(
			'tools.php',
			__('Carousel Block - bPlugins', 'carousel-block'),
			__('Carousel Block', 'carousel-block'),
			'manage_options',
			'carousel-block',
			[ \BICBPlugin::class, 'renderDashboard' ]
		);
	}
}
new SubMenu();