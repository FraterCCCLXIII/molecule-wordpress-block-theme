<?php

namespace Nelio_Popups\Compat;

defined( 'ABSPATH' ) || exit;

function sitemap_exclude_post_type( $excluded, $post_type ) {
	return $excluded || 'nelio_popup' === $post_type;
}
add_filter( 'wpseo_sitemap_exclude_post_type', __NAMESPACE__ . '\sitemap_exclude_post_type', 10, 2 );

add_filter(
	'wp_sitemaps_post_types',
	function ( $post_types ) {
		unset( $post_types['nelio_popup'] );
		return $post_types;
	}
);

add_filter(
	'wp_robots',
	function ( $robots ) {
		if ( is_singular( 'nelio_popup' ) ) {
			$robots['noindex'] = true;
		}
		return $robots;
	}
);
