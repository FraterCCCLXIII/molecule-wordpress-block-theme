<?php

namespace Nelio_Popups\REST_API;

defined( 'ABSPATH' ) || exit;

function add_title_search_limit_arg( $args, $request ) {
	$args['nelio_popups_search_by_title'] = isset( $request['nelio_popups_search_by_title'] );
	return $args;
}
add_filter( 'rest_page_query', __NAMESPACE__ . '\add_title_search_limit_arg', 10, 2 );
add_filter( 'rest_post_query', __NAMESPACE__ . '\add_title_search_limit_arg', 10, 2 );

function maybe_search_by_title( $where, $wp_query ) {
	$search     = $wp_query->get( 's' );
	$only_title = ! empty( $wp_query->get( 'nelio_popups_search_by_title' ) );
	if ( $only_title ) {
		global $wpdb;
		$where .= sprintf(
			" AND {$wpdb->posts}.post_title LIKE '%%%s%%' ",
			esc_sql( $search )
		);
	}
	return $where;
}
add_filter( 'posts_where', __NAMESPACE__ . '\maybe_search_by_title', 10, 2 );
