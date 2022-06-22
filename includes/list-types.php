<?php

/** @global MG_UPC_List_Type[] $mg_upc_list_types */
$GLOBALS['mg_upc_list_types'] = array();

/** @global stdClass[] $mg_upc_list_statuses */
$GLOBALS['mg_upc_list_statuses'] = array();

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


/**
 * Register a post status. Do not use before init. Based on register_post_status.
 *
 * A simple function for creating or modifying a list status based on the
 * parameters given. The function will accept an array (second optional
 * parameter), along with a string for the list status name.
 *
 * @global stdClass[] $mg_upc_list_statuses Inserts new post status object into the list
 *
 * @param string       $list_status Name of the post status.
 * @param array|string $args {
 *     Optional. Array or string of post status arguments.
 *
 *     @type bool|string $label                     A descriptive name for the post status marked
 *                                                  for translation. Defaults to value of $post_status.
 *     @type bool|array  $label_count               Descriptive text to use for nooped plurals.
 *                                                  Default array of $label, twice.
 *     @type bool        $exclude_from_search       Whether to exclude posts with this post status
 *                                                  from search results. Default is value of $internal.
 *     @type bool        $public                    Whether posts of this status should be shown
 *                                                  in the front end of the site. Default false.
 *     @type bool        $internal                  Whether the status is for internal use only.
 *                                                  Default false.
 *     @type bool        $protected                 Whether posts with this status should be protected.
 *                                                  Default false.
 *     @type bool        $private                   Whether posts with this status should be private.
 *                                                  Default false.
 *     @type bool        $publicly_queryable        Whether posts with this status should be publicly-
 *                                                  queryable. Default is value of $public.
 * }
 * @return object
 */
function mg_upc_register_list_status( $list_status, $args = array() ) {
	global $mg_upc_list_statuses;

	if ( ! is_array( $mg_upc_list_statuses ) ) {
		$mg_upc_list_statuses = array();
	}

	// Args prefixed with an underscore are reserved for internal use.
	$defaults = array(
		'label'               => false,
		'label_count'         => false,
		'exclude_from_search' => null,
		'public'              => null,
		'internal'            => null,
		'protected'           => null,
		'private'             => null,
		'publicly_queryable'  => null,
		'show_in_status_list' => null,
	);
	$args     = wp_parse_args( $args, $defaults );
	$args     = (object) $args;

	$list_status = sanitize_key( $list_status );
	$args->name  = $list_status;

	// Set various defaults.
	if ( null === $args->public && null === $args->internal && null === $args->protected && null === $args->private ) {
		$args->internal = true;
	}

	if ( null === $args->public ) {
		$args->public = false;
	}

	if ( null === $args->private ) {
		$args->private = false;
	}

	if ( null === $args->protected ) {
		$args->protected = false;
	}

	if ( null === $args->internal ) {
		$args->internal = false;
	}

	if ( null === $args->publicly_queryable ) {
		$args->publicly_queryable = $args->public;
	}

	if ( null === $args->exclude_from_search ) {
		$args->exclude_from_search = $args->internal;
	}

	if ( null === $args->show_in_status_list ) {
		$args->show_in_status_list = ! $args->internal;
	}

	if ( false === $args->label ) {
		$args->label = $list_status;
	}

	if ( false === $args->label_count ) {
		// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralSingle,WordPress.WP.I18n.NonSingularStringLiteralPlural
		$args->label_count = _n_noop( $args->label, $args->label );
	}

	$mg_upc_list_statuses[ $list_status ] = $args;

	return $args;
}



/**
 * Determines whether a list type is considered "viewable".
 *
 * The 'publicly_queryable' value will be evaluated.
 *
 * @param string|MG_UPC_List_Type $list_type Post type name or object.
 * @return bool Whether the post type should be considered viewable.
 */
function mg_upc_is_list_type_viewable( $list_type ) {
	if ( is_scalar( $list_type ) ) {
		$list_type = MG_UPC_Helper::get_instance()->get_list_type( $list_type );
		if ( ! $list_type ) {
			return false;
		}
	}

	if ( ! is_object( $list_type ) ) {
		return false;
	}

	$is_viewable = $list_type->publicly_queryable;

	/**
	 * Filters whether a post type is considered "viewable".
	 *
	 * The returned filtered value must be a boolean type to ensure
	 *
	 *
	 * @param bool             $is_viewable Whether the list type is "viewable".
	 * @param MG_UPC_List_Type $list_type   List type object.
	 */
	return true === apply_filters( 'mg_upc_is_list_type_viewable', $is_viewable, $list_type );
}

/**
 * Determine whether a list status is considered "viewable".
 *
 * The 'publicly_queryable' value will be evaluated.
 *
 * @param string|stdClass $list_status List status name or object.
 * @return bool Whether the list status should be considered viewable.
 */
function mg_upc_is_list_status_viewable( $list_status ) {
	if ( is_scalar( $list_status ) ) {
		$list_status = get_post_status_object( $list_status );
		if ( ! $list_status ) {
			return false;
		}
	}

	if (
		! is_object( $list_status ) ||
		$list_status->internal ||
		$list_status->protected
	) {
		return false;
	}

	$is_viewable = $list_status->publicly_queryable;

	/**
	 * Filters whether a list status is considered "viewable".
	 *
	 * The returned filtered value must be a boolean type
	 *
	 * @param bool     $is_viewable Whether the list status is "viewable"
	 * @param stdClass $list_status Post status object.
	 */
	return true === apply_filters( 'mg_upc_is_list_status_viewable', $is_viewable, $list_status );
}

/**
 * Determine whether a list is publicly viewable.
 *
 * Lists are considered publicly viewable if both the list status and post type
 * are viewable.
 *
 * @param array|stdClass|null $list Optional. Post ID or post object. Defaults to global $post.
 * @return bool Whether the post is publicly viewable.
 */
function mg_upc_is_list_publicly_viewable( $list = null ) {
	global $mg_upc_list;

	$list_arr = $list ? (array) $list : $mg_upc_list;

	if ( ! $list_arr ) {
		return false;
	}

	return mg_upc_is_list_type_viewable( $list_arr['type'] ) && mg_upc_is_list_status_viewable( $list_arr['status'] );
}
