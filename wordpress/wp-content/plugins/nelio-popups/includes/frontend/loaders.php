<?php

namespace Nelio_Popups\Frontend;

defined( 'ABSPATH' ) || exit;

function load_popup() {
	$popup_id = get_the_ID();
	return array(
		'id'      => $popup_id,
		'url'     => get_the_permalink(),
		'name'    => get_the_title(),
		'content' => get_the_content(),
		'config'  => call_user_func_array(
			'array_merge',
			array_map(
				function ( $meta ) use ( $popup_id ) {
					return load_popup_meta( $popup_id, $meta );
				},
				\Nelio_Popups\Popups\get_popup_metas()
			)
		),
	);
}

function load_popup_meta( $popup_id, $key ) {
	$exists = metadata_exists( 'post', $popup_id, "_nelio_popups_{$key}" );
	$value  = get_post_meta( $popup_id, "_nelio_popups_{$key}", true );
	return $exists ? array( $key => $value ) : array();
}
