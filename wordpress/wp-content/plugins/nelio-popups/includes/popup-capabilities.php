<?php

namespace Nelio_Popups\Popup_Capabilities;

defined( 'ABSPATH' ) || exit;

register_activation_hook( 'nelio-popups/nelio-popups.php', __NAMESPACE__ . '\plugin_activation' );
register_deactivation_hook( 'nelio-popups/nelio-popups.php', __NAMESPACE__ . '\plugin_deactivation' );
add_action( 'wpmu_new_blog', __NAMESPACE__ . '\new_blog_creation' );

function plugin_activation( $network_wide ) {
	if ( is_multisite() && $network_wide ) {
		foreach ( get_sites( array( 'fields' => 'ids' ) ) as $blog_id ) {
			switch_to_blog( $blog_id );
			add_capabilities();
			restore_current_blog();
		}
	} else {
		add_capabilities();
	}
}

function plugin_deactivation( $network_wide ) {
	if ( is_multisite() && $network_wide ) {
		foreach ( get_sites( array( 'fields' => 'ids' ) ) as $blog_id ) {
			switch_to_blog( $blog_id );
			remove_capabilities();
			restore_current_blog();
		}
	} else {
		remove_capabilities();
	}
}

function new_blog_creation( $blog_id ) {
	if ( is_plugin_active_for_network( 'nelio-popups/nelio-popups.php' ) ) {
		switch_to_blog( $blog_id );
		add_capabilities();
		restore_current_blog();
	}
}

function add_capabilities() {
	$role = get_role( 'administrator' );
	if ( $role ) {
		foreach ( get_editor_caps() as $cap ) {
			$role->add_cap( $cap );
		}
	}

	if ( is_multisite() ) {
		$caps         = get_editor_caps();
		$super_admins = get_super_admins();
		foreach ( $super_admins as $username ) {
			$user = get_user_by( 'login', $username );
			if ( $user ) {
				foreach ( $caps as $cap ) {
					$user->add_cap( $cap );
				}
			}
		}
	}

	$role = get_role( 'shop_manager' );
	if ( $role ) {
		foreach ( get_editor_caps() as $cap ) {
			$role->add_cap( $cap );
		}
	}

	$role = get_role( 'editor' );
	if ( $role ) {
		foreach ( get_editor_caps() as $cap ) {
			$role->add_cap( $cap );
		}
	}

	$role = get_role( 'author' );
	if ( $role ) {
		foreach ( get_author_caps() as $cap ) {
			$role->add_cap( $cap );
		}
	}

	$role = get_role( 'contributor' );
	if ( $role ) {
		foreach ( get_contributor_caps() as $cap ) {
			$role->add_cap( $cap );
		}
	}
}

function remove_capabilities() {
	$role = get_role( 'administrator' );
	if ( $role ) {
		foreach ( get_editor_caps() as $cap ) {
			$role->remove_cap( $cap );
		}
	}

	if ( is_multisite() ) {
		$caps         = get_editor_caps();
		$super_admins = get_super_admins();
		foreach ( $super_admins as $username ) {
			$user = get_user_by( 'login', $username );
			if ( $user ) {
				foreach ( $caps as $cap ) {
					$user->remove_cap( $cap );
				}
			}
		}
	}

	$role = get_role( 'shop_manager' );
	if ( $role ) {
		foreach ( get_editor_caps() as $cap ) {
			$role->remove_cap( $cap );
		}
	}

	$role = get_role( 'editor' );
	if ( $role ) {
		foreach ( get_editor_caps() as $cap ) {
			$role->remove_cap( $cap );
		}
	}

	$role = get_role( 'author' );
	if ( $role ) {
		foreach ( get_author_caps() as $cap ) {
			$role->remove_cap( $cap );
		}
	}

	$role = get_role( 'contributor' );
	if ( $role ) {
		foreach ( get_contributor_caps() as $cap ) {
			$role->remove_cap( $cap );
		}
	}
}

function get_contributor_caps() {
	return array(
		'edit_nelio_popups',
		'delete_nelio_popups',
	);
}

function get_author_caps() {
	return array_merge(
		get_contributor_caps(),
		array(
			'edit_nelio_popups',
			'delete_nelio_popups',
			'delete_published_nelio_popups',
			'publish_nelio_popups',
			'edit_published_nelio_popups',
		)
	);
}

function get_editor_caps() {
	return array_merge(
		get_author_caps(),
		array(
			'edit_others_nelio_popups',
			'delete_others_nelio_popups',
			'delete_private_nelio_popups',
			'edit_private_nelio_popups',
			'read_private_nelio_popups',
		)
	);
}
