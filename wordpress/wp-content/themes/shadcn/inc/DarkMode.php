<?php

namespace Shadcn;

use Shadcn\Traits\SingletonTrait;

class DarkMode {
	use SingletonTrait;

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_head', array( $this, 'clear_dark_mode' ), 1 );
	}

	public function enqueue_scripts() {
		$style_path    = get_stylesheet_directory() . '/style.css';
		$style_version = file_exists( $style_path ) ? (string) filemtime( $style_path ) : wp_get_theme()->get( 'Version' );
		$sticky_script = get_template_directory() . '/assets/js/sticky-top-nav.js';
		$script_ver    = file_exists( $sticky_script ) ? (string) filemtime( $sticky_script ) : $style_version;

		wp_enqueue_style(
			'shadcn-style',
			get_stylesheet_uri(),
			array(),
			$style_version
		);

		wp_enqueue_script(
			'shadcn-sticky-top-nav',
			get_template_directory_uri() . '/assets/js/sticky-top-nav.js',
			array(),
			$script_ver,
			true
		);
	}

	/**
	 * Clear any previously saved dark mode preference and force light mode.
	 */
	public function clear_dark_mode() { ?>
		<script>
			(function() {
				try {
					localStorage.removeItem('shadcn_dark_mode');
				} catch(e) {}
				document.cookie = 'shadcn-theme-mode=light;expires=' + new Date(Date.now() + 365*24*60*60*1000).toUTCString() + ';path=/;SameSite=Lax';
				document.documentElement.classList.remove('dark');
				document.documentElement.style.colorScheme = 'light';
			})();
		</script>
		<?php
	}

}

return DarkMode::get_instance();
