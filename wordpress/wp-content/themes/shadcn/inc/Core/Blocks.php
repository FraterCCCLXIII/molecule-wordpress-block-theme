<?php

namespace Shadcn\Core;

use Shadcn\Traits\SingletonTrait;

class Blocks {
	use SingletonTrait;

	public function __construct() {
		add_action( 'init', array( $this, 'register_pattern_category' ), 1 );
		add_action( 'init', array( $this, 'register_block_settings' ), 1 );
		add_action( 'init', array( $this, 'register_blocks' ), 1 );
		add_filter( 'block_categories_all', array( $this, 'add_block_category' ), 5, 1 );
	}

	public function register_pattern_category() {

		if ( ! class_exists( '\WP_Block_Pattern_Categories_Registry' ) ) {
			return;
		}

		$block_pattern_categories = array(
			'shadcn'        => array( 'label' => __( 'Shadcn Patterns', 'shadcn' ) ),
			'shadcn-banner' => array( 'label' => __( 'Shadcn Banner', 'shadcn' ) ),
		);

		$block_pattern_categories = apply_filters( 'shadcn_block_pattern_categories', $block_pattern_categories );

		foreach ( $block_pattern_categories as $name => $properties ) {
			if ( ! \WP_Block_Pattern_Categories_Registry::get_instance()->is_registered( $name ) ) {
				register_block_pattern_category( $name, $properties ); // phpcs:ignore WPThemeReview.PluginTerritory.ForbiddenFunctions.editor_blocks_register_block_pattern_category
			}
		}
	}

	public function register_block_settings() {
		require_once __DIR__ . '/../BlockSettings/BlockSettingsBootstrap.php';
	}

	public function register_blocks() {
		require_once __DIR__ . '/../Blocks/SvgImage/Caller.php';

		register_block_type( get_template_directory() . '/blocks/mobile-drawer' );
		register_block_type( get_template_directory() . '/blocks/icon-link' );
		register_block_type( get_template_directory() . '/blocks/product-carousel' );
		register_block_type( get_template_directory() . '/blocks/features-list' );
	}

	/**
	 * Register the "molecule" block category so the custom blocks appear
	 * grouped in the block inserter.
	 *
	 * @param array[] $categories Existing block categories.
	 * @return array[]
	 */
	public function add_block_category( $categories ) {
		$names = wp_list_pluck( $categories, 'slug' );

		if ( in_array( 'molecule', $names, true ) ) {
			return $categories;
		}

		return array_merge(
			array(
				array(
					'slug'  => 'molecule',
					'title' => __( 'Molecule', 'shadcn' ),
					'icon'  => null,
				),
			),
			$categories
		);
	}
}

Blocks::get_instance();
