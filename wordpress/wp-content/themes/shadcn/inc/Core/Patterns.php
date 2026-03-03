<?php

namespace Shadcn\Core;

use Shadcn\Traits\SingletonTrait;

class Patterns {
	use SingletonTrait;

	public function __construct() {
		add_action( 'init', array( $this, 'register_patterns' ) );
	}

	public function register_patterns() {
		$patterns = array(
			'shadcn/banner-heading' => array(
				'title'       => 'Banner Heading',
				'description' => 'A banner heading',
				'categories'  => array( 'shadcn', 'shadcn-banner' ),
				'content'     => '<!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontWeight":"700","lineHeight":"1.1","fontStyle":"normal"}},"fontSize":"fluid-7xl"} -->
				<h1 class="wp-block-heading has-text-align-center has-fluid-7-xl-font-size" style="font-style:normal;font-weight:400;line-height:1.1">' . esc_html__( 'Welcome to Our Website', 'shadcn' ) . '</h1>
				<!-- /wp:heading -->',
			),
		);

		foreach ( $patterns as $pattern => $pattern_data ) {
			if ( ! \WP_Block_Patterns_Registry::get_instance()->is_registered( $pattern ) ) {
				register_block_pattern( $pattern, $pattern_data );
			}
		}

		$file_based_patterns = array(
			'shadcn/peptide-guide-hero'             => 'patterns/peptide-guide-hero.php',
			'shadcn/peptide-guide-notice'           => 'patterns/peptide-guide-notice.php',
			'shadcn/peptide-guide-intro'            => 'patterns/peptide-guide-intro.php',
			'shadcn/peptide-guide-research-sections'=> 'patterns/peptide-guide-research-sections.php',
			'shadcn/peptide-guide-essential-guides' => 'patterns/peptide-guide-essential-guides.php',
			'shadcn/peptide-guide-tools'            => 'patterns/peptide-guide-tools.php',
			'shadcn/peptide-guide-disclaimer'       => 'patterns/peptide-guide-disclaimer.php',
		);

		foreach ( $file_based_patterns as $slug => $relative_path ) {
			if ( \WP_Block_Patterns_Registry::get_instance()->is_registered( $slug ) ) {
				continue;
			}

			$content = $this->render_pattern_file( $relative_path );
			if ( empty( $content ) ) {
				continue;
			}

			register_block_pattern(
				$slug,
				array(
					'title'      => ucwords( str_replace( array( 'shadcn/', '-' ), array( '', ' ' ), $slug ) ),
					'categories' => array( 'shadcn' ),
					'content'    => $content,
				)
			);
		}
	}

	private function render_pattern_file( $relative_path ) {
		$path = trailingslashit( get_template_directory() ) . ltrim( $relative_path, '/' );
		if ( ! file_exists( $path ) ) {
			return '';
		}

		ob_start();
		include $path;
		return trim( (string) ob_get_clean() );
	}
}

Patterns::get_instance();
