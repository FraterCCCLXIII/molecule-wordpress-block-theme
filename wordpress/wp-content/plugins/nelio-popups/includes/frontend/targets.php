<?php

namespace Nelio_Popups\Frontend;

defined( 'ABSPATH' ) || exit;

function filter_active_popups( $popup_ids, $popups, $context ) {

	$popups_by_id = array();
	foreach ( $popups as $popup ) {
		$popups_by_id[ $popup['id'] ] = $popup;
	}

	$active_ids = array();
	foreach ( $popup_ids as $popup_id ) {
		if ( ! isset( $popups_by_id[ $popup_id ] ) ) {
			continue;
		}

		$popup = $popups_by_id[ $popup_id ];

		if ( does_target_apply( $popup, $context ) ) {
			$active_ids[] = $popup_id;
		}
	}

	return $active_ids;
}
add_filter( 'nelio_popups_active_popups', __NAMESPACE__ . '\filter_active_popups', 10, 3 );

function does_target_apply( array $popup, array $context ) {

	if ( empty( $popup['config']['display']['isLocationCheckInServer'] ) ) {
		return true; // Check needs to be done in the client.
	}

	if ( ! isset( $popup['config']['target'] ) ) {
		return true;
	}

	$target = $popup['config']['target'];

	if ( 'auto' !== $context['postPopups'] ) {
		if ( empty( $context['postPopups'] ) || ! is_array( $context['postPopups'] ) ) {
			return false;
		}

		return in_array( $popup['id'], $context['postPopups'], true );
	}

	if ( 'full-site-target' === $target['type'] ) {
		return true;
	}

	if ( 'manual-target' === $target['type'] ) {
		return true; // Check needs to be done in the client.
	}

	if (
		'condition-based-target' !== $target['type'] ||
		empty( $target['groups'] ) ||
		! is_array( $target['groups'] )
	) {
		return false;
	}

	foreach ( $target['groups'] as $group ) {

		if ( empty( $group ) || ! is_array( $group ) ) {
			continue;
		}

		$all_conditions_match = true;
		foreach ( $group as $condition ) {
			$type = isset( $condition['type'] ) ? $condition['type'] : '';
			if ( ! $type ) {
				$all_conditions_match = false;
				break;
			}


			/**
			 * Filters whether a specific Nelio Popups target of a given type applies.
			 *
			 * This is a dynamic filter whose name is formed as: "nelio_popups_does_{$type}_target_apply".
			 * Use this filter to override or augment the evaluation of a target condition of the
			 * specified $type in the current $context.
			 *
			 * @since 1.3.2
			 *
			 * @param bool  $applies   Whether the target applies. Default false.
			 * @param mixed $condition The target condition to evaluate. The structure and type of this
			 *                         value depends on the target $type.
			 * @param array $context   Contextual information used to evaluate the condition (for example,
			 *                         current post, user, request parameters). Contents vary by invocation.
			 * @return bool Filtered boolean indicating whether the target applies.
			 */
			$applies = apply_filters(
				"nelio_popups_does_{$type}_target_apply",
				false,
				$condition,
				$context
			);

			if ( empty( $applies ) ) {
				$all_conditions_match = false;
				break;
			}
		}

		if ( $all_conditions_match ) {
			return true;
		}
	}

	return false;
}

function does_content_target_apply( $result, $condition, $context ) {
	switch ( $condition['value'] ?? '' ) {
		case '404-page':
		case 'blog-page':
		case 'home-page':
		case 'search-result-page':
			return isset( $context['specialPage'] )
				&& $context['specialPage'] === $condition['value'];
	}

	$post_type = $condition['postType'] ?? null;
	if ( empty( $post_type ) || ( $context['postType'] ?? null ) !== $post_type ) {
		return false;
	}

	$post_value = $condition['postValue'] ?? array();

	switch ( $post_value['type'] ?? '' ) {
		case 'all-posts':
			return ! empty( $context['isSingular'] );

		case 'selected-posts':
			return ! empty( $context['isSingular'] )
				&& in_array(
					(int) $context['postId'],
					array_map( 'intval', (array) $post_value['postIds'] ),
					true
				);

		case 'children':
			$parents = array_map( 'intval', (array) ( $context['parents'] ?? array() ) );
			$allowed = array_map( 'intval', (array) $post_value['postIds'] );

			return ! empty( $context['isSingular'] )
				&& count( array_intersect( $parents, $allowed ) ) > 0;

		case 'template':
			return ! empty( $context['isSingular'] )
				&& isset( $context['template'], $post_value['template'] )
				&& $context['template'] === $post_value['template'];

		case 'selected-terms':
			// Delegate to taxonomy condition logic.
			return (bool) apply_filters(
				'nelio_popups_does_taxonomy_target_apply',
				false,
				array(
					'type'  => 'taxonomy',
					'value' => $condition['postValue'],
				),
				$context
			);
	}

	return false;
}
add_filter(
	'nelio_popups_does_content_target_apply',
	__NAMESPACE__ . '\does_content_target_apply',
	10,
	3
);

function does_excluded_content_target_apply( $result, $condition, $context ) {
	return ! does_content_target_apply( $result, $condition, $context );
}
add_filter(
	'nelio_popups_does_excluded-content_target_apply',
	__NAMESPACE__ . '\does_excluded_content_target_apply',
	10,
	3
);
