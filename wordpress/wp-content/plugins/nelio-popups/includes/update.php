<?php

namespace Nelio_Popups\Update;

defined( 'ABSPATH' ) || exit;

function plugin_update() {
	if ( version_compare( get_option( 'nelio_popups_version', '0.0.0' ), '1.0.14', '<' ) ) {
		do_update();
		update_option( 'nelio_popups_version', nelio_popups_version() );
	}
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\plugin_update' );

function do_update() {
	$popups = get_posts(
		array(
			'post_type'      => 'nelio_popup',
			'posts_per_page' => -1,
		)
	);

	foreach ( $popups as $popup ) {
		$target = get_post_meta( $popup->ID, '_nelio_popups_target', true );

		$groups = array();
		if ( is_array( $target ) && ! empty( $target['type'] ) && 'condition-based-target' === $target['type'] && ! empty( $target['groups'] ) ) {
			$groups = $target['groups'];
		}

		$triggers   = get_post_meta( $popup->ID, '_nelio_popups_triggers', true );
		$conditions = create_conditions( $groups, $triggers );
		if ( ! empty( $conditions ) ) {
			update_post_meta( $popup->ID, '_nelio_popups_conditions', $conditions );
		}

		$triggers = fix_triggers( $triggers );
		update_post_meta( $popup->ID, '_nelio_popups_triggers', $triggers );

		if ( ! empty( $groups ) ) {
			$groups = fix_groups( $groups );

			if ( count( $groups ) ) {
				$target['groups'] = $groups;
				update_post_meta( $popup->ID, '_nelio_popups_target', $target );
			} else {
				update_post_meta(
					$popup->ID,
					'_nelio_popups_target',
					array(
						'type' => 'full-site-target',
					)
				);
			}
		}
	}
}

function fix_groups( $groups ) {
	$groups = array_map(
		function ( $group ) {
			return array_values(
				array_filter(
					$group,
					function ( $item ) {
						return in_array( $item['type'], array( 'content', 'taxonomy', 'url' ), true );
					}
				)
			);
		},
		$groups
	);

	return array_values(
		array_filter(
			$groups,
			function ( $group ) {
				return ! empty( $group );
			}
		)
	);
}

function create_conditions( $groups, $triggers ) {
	$triggers_to_transform = array_values(
		array_filter(
			$triggers,
			function ( $trigger ) {
				return in_array( $trigger['type'], array( 'adblock-detection', 'geolocation' ), true );
			}
		)
	);

	$groups = array_map(
		function ( $group ) {
			return array_values(
				array_filter(
					$group,
					function ( $item ) {
						return ! in_array( $item['type'], array( 'content', 'taxonomy', 'url' ), true );
					}
				)
			);
		},
		$groups
	);

	$groups = array_values(
		array_filter(
			$groups,
			function ( $group ) {
				return ! empty( $group );
			}
		)
	);

	if ( empty( $groups ) && ! empty( $triggers_to_transform ) ) {
		return array( $triggers_to_transform );
	}

	if ( empty( $triggers_to_transform ) ) {
		return $groups;
	}

	return array_map(
		function ( $group ) use ( $triggers_to_transform ) {
			foreach ( $triggers_to_transform as $trigger ) {
				array_push( $group, $trigger );
			}
			return $group;
		},
		$groups
	);
}

function fix_triggers( $triggers ) {
	$triggers = array_values(
		array_filter(
			$triggers,
			function ( $trigger ) {
				return ! in_array( $trigger['type'], array( 'adblock-detection', 'geolocation' ), true );
			}
		)
	);
	if ( empty( $triggers ) ) {
		$triggers = array( array( 'type' => 'page-view' ) );
	}
	return $triggers;
}
