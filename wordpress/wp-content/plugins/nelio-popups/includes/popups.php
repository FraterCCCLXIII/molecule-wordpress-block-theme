<?php

namespace Nelio_Popups\Popups;

defined( 'ABSPATH' ) || exit;

use Nelio_Popups\Zod\Zod as Z;

function register_popups() {

	$labels = array(
		'name'                  => _x( 'Nelio Popups', 'Post Type General Name', 'nelio-popups' ),
		'singular_name'         => _x( 'Popup', 'Post Type Singular Name', 'nelio-popups' ),
		'menu_name'             => __( 'Nelio Popups', 'nelio-popups' ),
		'name_admin_bar'        => __( 'Popup', 'nelio-popups' ),
		'archives'              => __( 'Popup Archives', 'nelio-popups' ),
		'attributes'            => __( 'Popup Attributes', 'nelio-popups' ),
		'parent_item_colon'     => __( 'Parent Popup:', 'nelio-popups' ),
		'all_items'             => __( 'All Popups', 'nelio-popups' ),
		'add_new_item'          => __( 'Add New Popup', 'nelio-popups' ),
		'add_new'               => __( 'Add New', 'nelio-popups' ),
		'new_item'              => __( 'New Popup', 'nelio-popups' ),
		'edit_item'             => __( 'Edit Popup', 'nelio-popups' ),
		'update_item'           => __( 'Update Popup', 'nelio-popups' ),
		'view_item'             => __( 'View Popup', 'nelio-popups' ),
		'view_items'            => __( 'View Popups', 'nelio-popups' ),
		'search_items'          => __( 'Search Popup', 'nelio-popups' ),
		'not_found'             => __( 'Not found', 'nelio-popups' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'nelio-popups' ),
		'featured_image'        => __( 'Featured Image', 'nelio-popups' ),
		'set_featured_image'    => __( 'Set featured image', 'nelio-popups' ),
		'remove_featured_image' => __( 'Remove featured image', 'nelio-popups' ),
		'use_featured_image'    => __( 'Use as featured image', 'nelio-popups' ),
		'insert_into_item'      => __( 'Insert into popup', 'nelio-popups' ),
		'uploaded_to_this_item' => __( 'Uploaded to this popup', 'nelio-popups' ),
		'items_list'            => __( 'Popups list', 'nelio-popups' ),
		'items_list_navigation' => __( 'Popups list navigation', 'nelio-popups' ),
		'filter_items_list'     => __( 'Filter popups list', 'nelio-popups' ),
	);

	$args = array(
		'can_export'          => true,
		'description'         => __( 'Nelio Popups', 'nelio-popups' ),
		'exclude_from_search' => true,
		'has_archive'         => false,
		'hierarchical'        => false,
		'label'               => __( 'Popup', 'nelio-popups' ),
		'labels'              => $labels,
		'menu_icon'           => get_icon(),
		'menu_position'       => 25,
		'public'              => false,
		'publicly_queryable'  => true,
		'rewrite'             => false,
		'show_in_admin_bar'   => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => false,
		'show_in_rest'        => true,
		'show_ui'             => true,
		'map_meta_cap'        => true,
		'capability_type'     => 'nelio_popup',
		'supports'            => array( 'editor', 'revisions', 'custom-fields', 'title', 'author' ),
	);

	register_post_type( 'nelio_popup', $args );
}
add_action( 'init', __NAMESPACE__ . '\register_popups', 5 );

function get_preview_url( $url, $post ) {
	if ( 'nelio_popup' !== $post->post_type ) {
		return $url;
	}

	$post_id = $post->ID;
	$url     = add_query_arg( 'nelio-popup-preview', $post_id, home_url() );
	$name    = 'nonce';
	$action  = 'nelio-popup-preview_' . $post_id;
	return add_query_arg( $name, wp_create_nonce( $action ), $url );
}
add_filter( 'preview_post_link', __NAMESPACE__ . '\get_preview_url', 10, 2 );

function get_popup_metas() {
	$metas = array(
		'advanced',
		'animation',
		'conditions',
		'display',
		'location',
		'overlay',
		'size',
		'sound',
		'spacing',
		'target',
		'triggers',
		'wrapper',
	);
	/**
	 * Filters the popup metas.
	 *
	 * @param array $metas popup meta names.
	 *
	 * @since 1.0.21
	 */
	return apply_filters( 'nelio_popups_metas', $metas );
}

function register_popups_meta() {
	$fields = get_popup_metas();
	foreach ( $fields as $short_field ) {
		$field = "nelio_popups_{$short_field}";
		register_rest_field(
			'nelio_popup',
			$field,
			array(
				'get_callback'    => function ( $params ) use ( $field ) {
					return get_post_meta( $params['id'], "_{$field}", true );
				},
				'update_callback' => function ( $value, $obj ) use ( $field ) {
					update_post_meta( $obj->ID, "_{$field}", $value );
				},
				'schema'          => array(
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => function ( $value ) use ( $short_field ) {
							return sanitize_popup_field( $short_field, $value );
						},
						'validate_callback' => function ( $value ) use ( $short_field ) {
							return validate_popup_field( $short_field, $value );
						},
					),
					'properties'  => array(),
				),
			)
		);
	}
}
add_action( 'init', __NAMESPACE__ . '\register_popups_meta', 5 );

function register_popups_is_enabled() {
	$field = 'nelio_popups_is_enabled';
	register_rest_field(
		'nelio_popup',
		$field,
		array(
			'get_callback'    => function ( $params ) use ( $field ) {
				return (
					'publish' === get_post_status( $params['id'] ) &&
					! empty( get_post_meta( $params['id'], "_{$field}", true ) )
				);
			},
			'update_callback' => function ( $value, $obj ) use ( $field ) {
				if ( true === $value && 'publish' === get_post_status( $obj->ID ) ) {
					update_post_meta( $obj->ID, "_{$field}", $value );
				} else {
					delete_post_meta( $obj->ID, "_{$field}" );
				}
			},
		)
	);
}
add_action( 'rest_api_init', __NAMESPACE__ . '\register_popups_is_enabled' );

function set_columns( $columns ) {
	$custom_columns = array(
		'cb'           => $columns['cb'],
		'title'        => $columns['title'],
		'author'       => $columns['author'],
		'popup_active' => _x( 'Active', 'text', 'nelio-popups' ),
		'popup_class'  => _x( 'CSS Class', 'text', 'nelio-popups' ),
		'date'         => $columns['date'],
	);
	return $custom_columns;
}
add_filter( 'manage_nelio_popup_posts_columns', __NAMESPACE__ . '\set_columns' );

function set_column_values( $column_name, $post_id ) {
	if ( 'popup_active' === $column_name ) {
		$checked  = (
			(bool) get_post_meta( $post_id, '_nelio_popups_is_enabled', true ) &&
			'publish' === get_post_status( $post_id )
		);
		$disabled = (
			! current_user_can( 'publish_post', $post_id ) ||
			'publish' !== get_post_status( $post_id )
		);
		printf(
			'<span class="nelio-popups-active-wrapper" data-id="%s" data-checked="%s" data-disabled="%s"></span>',
			esc_attr( $post_id ),
			esc_attr( $checked ? 'true' : 'false' ),
			esc_attr( $disabled ? 'true' : 'false' )
		);
	}

	if ( 'popup_class' === $column_name ) {
		printf(
			'<code>nelio-popup-%d</code>',
			esc_html( absint( $post_id ) )
		);
	}
}
add_action( 'manage_nelio_popup_posts_custom_column', __NAMESPACE__ . '\set_column_values', 10, 2 );

function customize_row_actions( $actions, $post ) {
	if ( 'nelio_popup' === $post->post_type ) {
		unset( $actions['inline hide-if-no-js'] );
		$actions['view'] = sprintf(
			'<a href="%1$s" target="_blank">%2$s</a>',
			esc_url( get_preview_url( '', $post ) ),
			esc_html_x( 'Preview', 'command', 'nelio-popups' )
		);
	}

	return $actions;
}
add_filter( 'post_row_actions', __NAMESPACE__ . '\customize_row_actions', 10, 2 );

function remove_view_link() {
	global $wp_admin_bar;

	if ( get_post_type() === 'nelio_popup' ) {
		$wp_admin_bar->remove_menu( 'view' );
	}
}
add_action( 'wp_before_admin_bar_render', __NAMESPACE__ . '\remove_view_link' );

function maybe_enqueue_popups_list_assets() {
	$screen = get_current_screen();
	if ( empty( $screen ) ) {
		return;
	}

	$haystack = $screen->id;
	$needle   = 'edit-nelio_popup';
	if ( 0 !== substr_compare( $haystack, $needle, -strlen( $needle ) ) ) {
		return;
	}

	wp_enqueue_style( 'wp-components' );
	nelio_popups_register_script( 'popup-list' );
	nelio_popups_enqueue_script( 'popup-list' );
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\maybe_enqueue_popups_list_assets' );

function allowed_block_types( $allowed_block_types, $editor_context ) {
	if ( isset( $editor_context->post ) && 'nelio_popup' === $editor_context->post->post_type ) {
		/**
		* Filters the allowed blocks in a popup.
		*
		* @param bool|string[] $allowed_block_types Array of block type slugs,
		* or boolean to enable/disable all. Default true (all registered block
		* types supported)
		*
		* @since 1.1.0
		*/
		return apply_filters( 'nelio_popups_allowed_blocks', $allowed_block_types );
	}

	return $allowed_block_types;
}
add_filter( 'allowed_block_types_all', __NAMESPACE__ . '\allowed_block_types', 10, 2 );

// =======
// HELPERS
// =======
// phpcs:ignore
function get_icon() {
	$icon = nelio_popups_path() . '/includes/icon.svg';
	return ! file_exists( $icon )
		? 'dashicons-align-wide'
		: 'data:image/svg+xml;base64,' . base64_encode( file_get_contents( $icon ) ); // phpcs:ignore
}

function get_field_schema( $field ) {
	static $schemas = null;
	if ( ! is_null( $schemas ) ) {
		return $schemas[ $field ];
	}

	/**
	 * Short-circuits the field schemas.
	 *
	 * @param null|array<string, \Nelio_Popups\Zod\Schema > $schemas The field schemas or null to use the default ones.
	 */
	$schemas = apply_filters( 'nelio_popups_field_schemas_pre', null );
	if ( ! is_null( $schemas ) ) {
		return $schemas[ $field ];
	}

	// --------------------------------------------------
	// Helpers
	// --------------------------------------------------

	$time_value_schema = Z::object(
		array(
			'value' => Z::number(),
			'unit'  => Z::enum( array( 'seconds', 'minutes', 'hours', 'days', 'months' ) ),
		)
	);

	$css_size_unit_schema = Z::object(
		array(
			'value' => Z::number(),
			'unit'  => Z::enum( array( 'px', '%', 'em', 'rem' ) ),
		)
	);

	$hex_color_schema = Z::string()->regex( '/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/' );

	$string_match_type_schema = Z::enum( array( 'is', 'is-not', 'includes', 'does-not-include', 'regex' ) );

	$string_match_schema = Z::object(
		array(
			'matchType'  => $string_match_type_schema,
			'matchValue' => Z::string(),
		)
	);

	// --------------------------------------------------
	// Advanced
	// --------------------------------------------------

	$cookie_settings_schema = Z::union(
		array(
			Z::object(
				array(
					'isEnabled' => Z::literal( false ),
				)
			),
			Z::object(
				array(
					'isEnabled' => Z::literal( true ),
					'name'      => Z::string(),
					'isSession' => Z::literal( true ),
				)
			),
			Z::object(
				array(
					'isEnabled'  => Z::literal( true ),
					'name'       => Z::string(),
					'isSession'  => Z::literal( false ),
					'expiration' => $time_value_schema,
				)
			),
		)
	);

	$advanced_schema = Z::object(
		array(
			'isBodyScrollLocked'    => Z::boolean(),
			'closeOnEscPressed'     => Z::boolean(),
			'closeOnOverlayClicked' => Z::boolean(),
			'popupOpenedCookie'     => $cookie_settings_schema,
		)
	);

	// --------------------------------------------------
	// Animation
	// --------------------------------------------------

	$animation_schema = Z::union(
		array(
			Z::object(
				array(
					'type' => Z::literal( 'none' ),
				)
			),
			Z::object(
				array(
					'type'  => Z::enum(
						array(
							'fade',
							'slide',
							'slide-and-fade',
							'zoom',
							'back',
							'bounce',
							'flip',
							'flip-y',
							'roll',
							'rotate',
						)
					),
					'delay' => Z::enum( array( 'none', 'short', 'medium', 'long' ) ),
					'speed' => Z::enum( array( 'default', 'slower', 'slow', 'fast', 'faster' ) ),
				)
			),
		)
	);

	// --------------------------------------------------
	// Conditions
	// --------------------------------------------------

	// Cookie.
	$cookie_exists_schema = Z::object(
		array(
			'type'   => Z::literal( 'cookie' ),
			'exists' => Z::literal( true ),
			'key'    => Z::string(),
			'value'  => $string_match_schema->optional(),
		)
	);

	$cookie_not_exists_schema = Z::object(
		array(
			'type'   => Z::literal( 'cookie' ),
			'exists' => Z::literal( false ),
			'key'    => Z::string(),
		)
	);

	// Referrer.
	$referrer_condition_schema = Z::object(
		array(
			'type'       => Z::literal( 'referrer' ),
			'matchType'  => $string_match_type_schema,
			'matchValue' => Z::string(),
		)
	);

	// Adblock detection.
	$adblock_condition_schema = Z::object(
		array(
			'type' => Z::literal( 'adblock-detection' ),
		)
	);

	// Browser (premium: matchType, matchValue).
	$browser_condition_schema = Z::object(
		array(
			'type'       => Z::literal( 'browser' ),
			'matchType'  => $string_match_type_schema->optional(),
			'matchValue' => Z::string()->optional(),
		)
	);

	$custom_condition_schema = Z::object(
		array(
			'type'         => Z::literal( 'custom' ),
			'functionName' => Z::string()->optional(),
		)
	);

	// Age gate: requires theme's age verification to be passed (Molecule/Shadcn storage keys).
	$age_gate_condition_schema = Z::object(
		array(
			'type' => Z::literal( 'age-gate' ),
		)
	);

	$date_condition_schema = Z::object(
		array(
			'type'  => Z::literal( 'date' ),
			'start' => Z::string()->optional(),
			'end'   => Z::string()->optional(),
		)
	);

	$day_of_week_condition_schema = Z::object(
		array(
			'type' => Z::literal( 'day-of-week' ),
			'days'  => Z::array( Z::number() )->optional(),
		)
	);

	$device_condition_schema = Z::object(
		array(
			'type'       => Z::literal( 'device' ),
			'matchType'  => $string_match_type_schema->optional(),
			'matchValue' => Z::string()->optional(),
			'value'      => Z::string()->optional(),
		)
	);

	$geo_condition_schema = Z::object(
		array(
			'type'       => Z::literal( 'geolocation' ),
			'matchType'  => $string_match_type_schema->optional(),
			'matchValue' => Z::string()->optional(),
			'country'    => Z::string()->optional(),
		)
	);

	$language_condition_schema = Z::object(
		array(
			'type'       => Z::literal( 'language' ),
			'matchType'  => $string_match_type_schema->optional(),
			'matchValue' => Z::string()->optional(),
			'value'      => Z::string()->optional(),
		)
	);

	$os_condition_schema = Z::object(
		array(
			'type'       => Z::literal( 'os' ),
			'matchType'  => $string_match_type_schema->optional(),
			'matchValue' => Z::string()->optional(),
			'value'      => Z::string()->optional(),
		)
	);

	$time_condition_schema = Z::object(
		array(
			'type'  => Z::literal( 'time' ),
			'start' => Z::number()->optional(),
			'end'   => Z::number()->optional(),
		)
	);

	$query_arg_schema = Z::object(
		array(
			'type'       => Z::literal( 'query-arg' ),
			'key'        => Z::string()->optional(),
			'matchType'  => $string_match_type_schema->optional(),
			'matchValue' => Z::string()->optional(),
			'value'      => Z::string()->optional(),
		)
	);

	// Visitor (new vs returning).
	$visitor_schema = Z::object(
		array(
			'type'       => Z::literal( 'visitor' ),
			'matchValue' => Z::string()->optional(),
			'value'      => Z::string()->optional(),
		)
	);

	// Window width.
	$window_width_condition_schema = Z::object(
		array(
			'type' => Z::literal( 'window-width' ),
			'min'  => Z::number()->optional(),
			'max'  => Z::number()->optional(),
		)
	);

	// WooCommerce (conditions).
	$woocommerce_condition_schema = Z::object(
		array(
			'type'     => Z::literal( 'woocommerce' ),
			'subType'  => Z::string()->optional(),
			'operator' => Z::string()->optional(),
			'value'    => Z::number()->optional(),
		)
	);

	// Union for a single condition node.
	$condition_group_schema = Z::union(
		array(
			$cookie_exists_schema,
			$cookie_not_exists_schema,
			$referrer_condition_schema,
			$adblock_condition_schema,
			$age_gate_condition_schema,
			$browser_condition_schema,
			$custom_condition_schema,
			$date_condition_schema,
			$day_of_week_condition_schema,
			$device_condition_schema,
			$geo_condition_schema,
			$language_condition_schema,
			$os_condition_schema,
			$query_arg_schema,
			$time_condition_schema,
			$visitor_schema,
			$window_width_condition_schema,
			$woocommerce_condition_schema,
		)
	);

	$conditions_schema = Z::array(
		Z::array( $condition_group_schema )
	);

	// --------------------------------------------------
	// Display
	// --------------------------------------------------

	$trigger_limit_schema = Z::object(
		array(
			'type'  => Z::literal( 'unlimited' ),
			'delay' => $time_value_schema,
		)
	);

	$frequency_schema = Z::object(
		array(
			'audience'              => Z::enum( array( 'first-time-only', 'returning-with-cooldown', 'always' ) )->default( 'always' )->optional(),
			'cooldownDays'          => Z::number()->default( 7 )->optional(),
			'maxDisplaysPerVisit'   => Z::union(
				array(
					Z::literal( 'unlimited' ),
					Z::number(),
				)
			)->default( 'unlimited' )->optional(),
		)
	);

	$display_schema = Z::object(
		array(
			'disablesOtherPopupOpenings' => Z::boolean(),
			'isDisabledIfOpenedPopups'   => Z::boolean(),
			'isDisabledOnMobile'         => Z::boolean(),
			'isLocationCheckInServer'   => Z::boolean()->default( true )->optional(),
			'triggerLimit'              => $trigger_limit_schema,
			'zIndex'                    => Z::number(),
			'frequency'                 => $frequency_schema->optional(),
		)
	);

	// --------------------------------------------------
	// Location
	// --------------------------------------------------

	$location_schema = Z::enum(
		array(
			'bottom',
			'bottom-left',
			'bottom-right',
			'center',
			'left',
			'right',
			'top',
			'top-left',
			'top-right',
		)
	);

	// --------------------------------------------------
	// Overlay
	// --------------------------------------------------

	$overlay_schema = Z::union(
		array(
			Z::object(
				array(
					'isEnabled' => Z::literal( false ),
				)
			),
			Z::object(
				array(
					'isEnabled' => Z::literal( true ),
					'color'     => $hex_color_schema,
				)
			),
		)
	);

	// --------------------------------------------------
	// Size
	// --------------------------------------------------

	$size_schema = Z::union(
		array(
			Z::object(
				array(
					'type' => Z::literal( 'fullscreen' ),
				)
			),
			Z::object(
				array(
					'type'  => Z::literal( 'auto' ),
					'value' => Z::enum( array( 'tiny', 'small', 'medium', 'normal', 'large' ) ),
				)
			),
			Z::object(
				array(
					'type'   => Z::literal( 'custom' ),
					'width'  => $css_size_unit_schema,
					'height' => Z::union(
						array(
							Z::object(
								array(
									'type' => Z::literal( 'auto-adjust' ),
								)
							),
							Z::object(
								array(
									'type'                => Z::literal( 'custom-height' ),
									'value'               => $css_size_unit_schema,
									'isContentScrollable' => Z::boolean(),
								)
							),
						)
					),
				)
			),
		)
	);

	// --------------------------------------------------
	// Sound
	// --------------------------------------------------

	$sound_schema = Z::union(
		array(
			Z::object(
				array(
					'type' => Z::enum(
						array(
							'none',
							'beep',
							'beep-two',
							'beep-three',
							'beep-four',
							'beep-five',
							'chimes',
							'correct',
							'cute',
							'success',
							'success-two',
						)
					),
				)
			),
			Z::object(
				array(
					'type'      => Z::literal( 'custom' ),
					'customUrl' => Z::string(),
				)
			),
		)
	);

	// --------------------------------------------------
	// Spacing
	// --------------------------------------------------

	$full_css_size_unit_schema = Z::object(
		array(
			'top'    => $css_size_unit_schema,
			'bottom' => $css_size_unit_schema,
			'left'   => $css_size_unit_schema,
			'right'  => $css_size_unit_schema,
		)
	);

	$spacing_schema = Z::object(
		array(
			'margin'  => $full_css_size_unit_schema,
			'padding' => $full_css_size_unit_schema,
		)
	);

	// --------------------------------------------------
	// Target
	// --------------------------------------------------

	$content_target_condition_schema = Z::union(
		array(
			Z::object(
				array(
					'type'  => Z::enum( array( 'content', 'excluded-content' ) ),
					'value' => Z::enum(
						array(
							'404-page',
							'blog-page',
							'home-page',
							'search-result-page',
						)
					),
				)
			),
			Z::object(
				array(
					'type'      => Z::enum( array( 'content', 'excluded-content' ) ),
					'value'     => Z::literal( 'post-type' ),
					'postType'  => Z::string(),
					'postValue' => Z::union(
						array(
							Z::object( array( 'type' => Z::literal( 'all-posts' ) ) ),
							Z::object(
								array(
									'type'    => Z::literal( 'selected-posts' ),
									'postIds' => Z::array( Z::number() ),
								)
							),
							Z::object(
								array(
									'type'    => Z::literal( 'children' ),
									'postIds' => Z::array( Z::number() ),
								)
							),
							Z::object(
								array(
									'type'     => Z::literal( 'template' ),
									'template' => Z::string()->optional(),
								)
							),
							Z::object(
								array(
									'type'         => Z::literal( 'selected-terms' ),
									'taxonomyName' => Z::string(),
									'termIds'      => Z::array( Z::number() ),
								)
							),
						)
					),
				)
			),
		)
	);

	$taxonomy_target_schema = Z::object(
		array(
			'type' => Z::enum( array( 'taxonomy', 'excluded-taxonomy' ) ),
		)
	);

	$url_target_schema = Z::object(
		array(
			'type' => Z::literal( 'url' ),
		)
	);

	$target_condition_schema = Z::union(
		array(
			$content_target_condition_schema,
			$taxonomy_target_schema,
			$url_target_schema,
		)
	);

	$target_condition_group_schema = Z::array( $target_condition_schema );

	$target_schema = Z::union(
		array(
			Z::object(
				array(
					'type' => Z::literal( 'full-site-target' ),
				)
			),
			Z::object(
				array(
					'type' => Z::literal( 'manual-target' ),
				)
			),
			Z::object(
				array(
					'type'   => Z::literal( 'condition-based-target' ),
					'groups' => Z::array( $target_condition_group_schema ),
				)
			),
		)
	);

	// --------------------------------------------------
	// Triggers
	// --------------------------------------------------

	$mouse_trigger_schema = Z::object(
		array(
			'type'            => Z::literal( 'mouse' ),
			'mode'            => Z::enum( array( 'click', 'hover' ) ),
			'elementSelector' => Z::string(),
		)
	);

	$page_view_trigger_schema = Z::object(
		array(
			'type' => Z::literal( 'page-view' ),
		)
	);

	$scroll_trigger_schema = Z::object(
		array(
			'type'  => Z::literal( 'scroll' ),
			'value' => $css_size_unit_schema,
		)
	);

	$time_trigger_schema = Z::object(
		array(
			'type'    => Z::literal( 'time' ),
			'seconds' => Z::number(),
		)
	);

	$exit_intent_trigger_schema = Z::object(
		array(
			'type' => Z::literal( 'exit-intent' ),
		)
	);

	$html_element_trigger_schema = Z::object(
		array(
			'type' => Z::literal( 'html-element' ),
		)
	);

	$inactivity_trigger_schema = Z::object(
		array(
			'type'    => Z::literal( 'inactivity' ),
			'seconds' => Z::number(),
		)
	);

	$manual_trigger_schema = Z::object(
		array(
			'type' => Z::literal( 'manual' ),
		)
	);

	$time_on_site_trigger_schema = Z::object(
		array(
			'type' => Z::literal( 'time-on-site' ),
		)
	);

	$woocommerce_trigger_schema = Z::object(
		array(
			'type' => Z::literal( 'woocommerce' ),
		)
	);

	$triggers_schema = Z::array(
		Z::union(
			array(
				$mouse_trigger_schema,
				$page_view_trigger_schema,
				$scroll_trigger_schema,
				$time_trigger_schema,
				$exit_intent_trigger_schema,
				$html_element_trigger_schema,
				$inactivity_trigger_schema,
				$manual_trigger_schema,
				$time_on_site_trigger_schema,
				$woocommerce_trigger_schema,
			)
		)
	);

	// --------------------------------------------------
	// Wrapper
	// --------------------------------------------------

	$border_schema = Z::union(
		array(
			Z::object(
				array(
					'isEnabled' => Z::literal( false ),
				)
			),
			Z::object(
				array(
					'isEnabled' => Z::literal( true ),
					'radius'    => $css_size_unit_schema,
					'color'     => $hex_color_schema,
					'width'     => $css_size_unit_schema,
				)
			),
		)
	);

	$close_button_schema = Z::union(
		array(
			Z::object(
				array(
					'isEnabled' => Z::literal( false ),
				)
			),
			Z::object(
				array(
					'isEnabled'       => Z::literal( true ),
					'icon'            => Z::string(),
					'label'           => Z::string(),
					'size'            => $css_size_unit_schema,
					'delayInMillis'   => Z::number(),
					'position'        => Z::enum( array( 'top-left', 'top-right', 'bottom-left', 'bottom-right' ) ),
					'color'           => $hex_color_schema,
					'backgroundColor' => $hex_color_schema,
				)
			),
		)
	);

	$shadow_schema = Z::union(
		array(
			Z::object(
				array(
					'isEnabled' => Z::literal( false ),
				)
			),
			Z::object(
				array(
					'isEnabled' => Z::literal( true ),
					'blur'      => $css_size_unit_schema,
					'color'     => $hex_color_schema,
					'offsetX'   => $css_size_unit_schema,
					'offsetY'   => $css_size_unit_schema,
				)
			),
		)
	);

	$border_radius_schema = Z::object(
		array(
			'value' => Z::number(),
			'unit'  => Z::enum( array( 'px', 'em', 'rem' ) ),
		)
	)->optional();

	$wrapper_schema = Z::object(
		array(
			'border'       => $border_schema,
			'borderRadius' => $border_radius_schema,
			'closeButton' => $close_button_schema,
			'shadow'      => $shadow_schema,
		)
	);

	$schemas = array(
		'advanced'           => $advanced_schema,
		'animation'          => $animation_schema,
		'conditions'         => $conditions_schema,
		'display'            => $display_schema,
		'location'           => $location_schema,
		'overlay'            => $overlay_schema,
		'size'               => $size_schema,
		'sound'              => $sound_schema,
		'spacing'            => $spacing_schema,
		'target'             => $target_schema,
		'triggers'           => $triggers_schema,
		'wrapper'            => $wrapper_schema,
		'analytics_settings' => Z::object(
			array(
				'isTrackingEnabled'    => Z::boolean()->optional(),
				'trackClicksOnLinks'   => Z::boolean(),
				'trackClicksOnButtons' => Z::boolean(),
				'trackFormSubmissions' => Z::boolean(),
			)
		),
	);

	return $schemas[ $field ];
}

function validate_popup_field( $field, $value ) {
	$schema = get_field_schema( $field );
	$result = $schema->safe_parse( $value );
	return $result['success'] ? true : new \WP_Error( 'parse-error', $result['error'] );
}

function sanitize_popup_field( $field, $value ) {
	$schema = get_field_schema( $field );
	return $schema->parse( $value );
}
