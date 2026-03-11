<?php

defined( 'ABSPATH' ) || exit;

function nelio_popups_get_script_version( $name ) {
	$file = nelio_popups_path() . "/dist/nelio-popups-{$name}.asset.php";
	if ( ! file_exists( $file ) ) {
		return nelio_popups_version();
	}
	$asset = include $file;
	return $asset['version'];
}

function nelio_popups_register_script( $name ) {
	$file = nelio_popups_path() . "/dist/nelio-popups-{$name}.asset.php";
	if ( ! file_exists( $file ) ) {
		return;
	}

	$asset = include $file;
	// NOTE Bug fix with @wordpress/core-data package.
	$asset['dependencies'] = array_map(
		function ( $dep ) {
			return str_replace( 'wp-coreData', 'wp-core-data', $dep );
		},
		$asset['dependencies']
	);

	/**
	 * Filters the list of extra dependencies the given script has.
	 *
	 * @param array  $dependencies List of extra dependencies.
	 * @param string $handler      Script handler.
	 *
	 * @since 1.0.12
	 */
	$extra_deps = apply_filters( 'nelio_popups_extra_dependencies', array(), "nelio-popups-{$name}" );

	$asset['dependencies'] = array_merge( $asset['dependencies'], $extra_deps );
	wp_register_script(
		"nelio-popups-{$name}",
		nelio_popups_url() . "/dist/nelio-popups-{$name}.js",
		array_unique( $asset['dependencies'] ),
		$asset['version'],
		true
	);

	if ( in_array( 'wp-i18n', $asset['dependencies'], true ) ) {
		wp_set_script_translations( "nelio-popups-{$name}", 'nelio-popups' );
	}
}

function nelio_popups_enqueue_script( $name ) {
	nelio_popups_register_script( $name );
	wp_enqueue_script( "nelio-popups-{$name}" );
}

function nelio_popups_register_style( $name, $deps = array() ) {
	wp_register_style(
		"nelio-popups-{$name}",
		nelio_popups_url() . "/dist/nelio-popups-{$name}.css",
		$deps,
		nelio_popups_get_script_version( $name )
	);
}

function nelio_popups_enqueue_style( $name, $deps = array() ) {
	nelio_popups_register_style( $name, $deps );
	wp_enqueue_style( "nelio-popups-{$name}" );
}
