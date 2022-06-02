<?php

/** @global MG_UPC_List_Type[] $mg_upc_list_types */
$mg_upc_list_types = array();

/**
 * @param string $list_type Same limit that list_type
 * @param array  $args
 *
 * @return WP_Error|MG_UPC_List_Type
 */
function mg_upc_register_list_type( $list_type, $args = array() ) {
	global $mg_upc_list_types;

	if ( ! is_array( $mg_upc_list_types ) ) {
		$mg_upc_list_types = array();
	}

	// Sanitize list type name.
	$list_type = sanitize_key( $list_type );

	if ( empty( $list_type ) || strlen( $list_type ) > 20 ) {
		return new WP_Error(
			'list_type_length_invalid',
			__( 'List type names must be between 1 and 20 characters in length.', 'user-post-collections' )
		);
	}

	$list_type_object = new MG_UPC_List_Type( $list_type, $args );

	$mg_upc_list_types[ $list_type ] = $list_type_object;

	/**
	 * Fires after a list type is registered.
	 *
	 * @param string       $list_type        List type.
	 * @param MG_UPC_List_Type $list_type_object Arguments used to register the list type.
	 */
	do_action( 'registered_list_type', $list_type, $list_type_object );

	/**
	 * Fires after a specific list type is registered.
	 *
	 * The dynamic portion of the filter name, `$list_type`, refers to the list type key.
	 *
	 * Possible hook names include:
	 *
	 *  - `mg_upc_registered_type_simple`
	 *  - `mg_upc_registered_type_numbered`
	 *
	 * @param string           $list_type        List type.
	 * @param MG_UPC_List_Type $list_type_object Arguments used to register the list type.
	 */
	do_action( "mg_upc_registered_type_{$list_type}", $list_type, $list_type_object );

	return $list_type_object;
}
