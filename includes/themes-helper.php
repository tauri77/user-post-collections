<?php

/**
 * Check if a post already exists on collection
 *
 * @param int $post_id
 * @param int|string $collection If is string use as special type (bookmarks|favorites), else list id
 *
 * @return false|int The collection ID or false
 * @throws MG_UPC_Invalid_Field_Exception
 */
function mg_upc_post_in_collection( $post_id, $collection ) {
	global $mg_upc;
	if ( is_string( $collection ) ) {
		$list = $mg_upc->model->find_always_exist( $collection, get_current_user_id() );

		if ( null === $list ) {
			return false;
		}
		$collection = $list->ID;
	}
	if ( $mg_upc->model->items->item_exists( $collection, $post_id ) ) {
		return $collection;
	}
	return false;
}


/**
 * Display the ID of the current list in the Loop.
 *
 */
function mg_upc_the_ID() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	echo (int) mg_upc_get_the_ID();
}

/**
 * Retrieve the ID of the current list in the Loop.
 *
 * @return int|false The ID of the current item in the Loop. False if $mg_upc_list is not set.
 */
function mg_upc_get_the_ID() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	$collection = mg_upc_get_list();
	return ! empty( $collection ) ? $collection->ID : false;
}

/**
 * Retrieve a list
 *
 * This function attempts to fetch a list based on the provided `$collection` parameter.
 * If `$collection` is not specified, it tries to use a global list variable. The function
 * can return the list as an object or as an associative array depending on the `$output` parameter.
 *
 * @param mixed $collection Optional. The identifier for the collection to retrieve. This can be null,
 *                          a list ID, a list object, or an array. If null, the function attempts
 *                          to use a global list variable. Default null.
 * @param string $output    Optional. The desired format of the returned list. Accepts OBJECT for a standard
 *                          object or ARRAY_A for an associative array. Default OBJECT.
 *
 * @return MG_UPC_List|array|null An instance of the list as an object or associative array based on `$output`, or
 *                                null if the list cannot be found or `$collection` is invalid.
 */
function mg_upc_get_list( $collection = null, $output = OBJECT ) {
	if ( empty( $collection ) && isset( $GLOBALS['mg_upc_list'] ) ) {
		$collection = $GLOBALS['mg_upc_list'];
	}

	if ( $collection instanceof MG_UPC_List ) {
		$_collection = $collection;
	} else {
		$_collection = MG_UPC_List::get_instance( $collection );
	}

	if ( ! $_collection ) {
		return null;
	}

	if ( ARRAY_A === $output ) {
		return $_collection->to_array();
	}

	return $_collection;
}


/**
 * Get/print UPC Title
 *
 * @param string $before_escaped Optional. Code before.
 * @param string $after_escaped  Optional. Code after.
 * @param bool   $echo           Optional. Print? (on false return code)
 *
 * @return string|void
 */
function mg_upc_the_title( $before_escaped = '', $after_escaped = '', $echo = true ) {
	$title = mg_upc_get_the_title();

	if ( strlen( $title ) === 0 ) {
		return '';
	}

	if ( $echo ) {
		// phpcs:ignore
		echo $before_escaped . esc_html( $title ) . $after_escaped;
	}

	return $before_escaped . esc_html( $title ) . $after_escaped;
}

/**
 * Retrieve list title.
 *
 * @param int|MG_UPC_List $list Optional. List ID or MG_UPC_List object. Default is global $post.
 *
 * @return string
 */
function mg_upc_get_the_title( $list = 0 ) {

	$list = mg_upc_get_list( $list );

	$title = $list->title ?? '';
	$id    = $list->ID ?? 0;

	return apply_filters( 'mg_upc_the_title', $title, $id );
}



/**
 * Display the list content.
 */
function mg_upc_the_content() {
	$content = mg_upc_get_the_content();

	/**
	 * Filters the list content.
	 *
	 * @param string $content Content of the current content.
	 */
	$content = apply_filters( 'mg_upc_the_content', $content );

	if ( strpos( $content, '<' ) !== false ) {
		$content = force_balance_tags( $content );
	}

	echo wp_kses( nl2br( $content ), MG_UPC_List_Controller::get_instance()->list_allowed_tags() );
}

/**
 * Retrieve the list content.
 *
 * @return string
 */
function mg_upc_get_the_content( $list = 0 ) {
	$list = mg_upc_get_list( $list );

	return $list->content;
}

/**
 * Displays the permalink for the current list.
 *
 * @param int|array|object $list Optional. List ID or list object. Default is the global `$mg_upc_list`.
 */
function mg_upc_the_permalink( $list = 0 ) {
	echo esc_url( apply_filters( 'mg_upc_the_permalink', mg_upc_get_the_permalink( $list ), $list ) );
}

/**
 * Retrieve list title.
 *
 * @param int|array|object $list Optional. List ID or list object. Default is global $mg_upc_list.
 * @return string
 */
function mg_upc_get_the_permalink( $list = 0 ) {
	$list = mg_upc_get_list( $list );

	// Set by Page Controller
	return apply_filters( 'mg_upc_get_the_permalink', '', $list );
}

/**
 * Check if the actual query is the main query.
 *
 * @global MG_UPC_Query $mg_upc_query
 */
function mg_upc_is_main_query() {
	global $mg_upc_query;
	return $mg_upc_query->is_main_query();
}

/**
 * Destroys the previous query and sets up a new query.
 *
 * @global MG_UPC_Query $mg_upc_query
 * @global MG_UPC_Query $mg_upc_the_query
 */
function mg_upc_reset_query() {
	$GLOBALS['mg_upc_query'] = $GLOBALS['mg_upc_the_query'];
	mg_upc_reset_listdata();
}

/**
 * After looping through a separate query, this function restores
 * the $mg_upc_item global to the current post in the main query.
 *
 * @global MG_UPC_Query $mg_upc_the_query MG_UPC_Query Query object.
 */
function mg_upc_reset_listdata() {
	/** @global MG_UPC_Query $mg_upc_the_query MG_UPC_Query Query object. */
	global $mg_upc_the_query;

	if ( isset( $mg_upc_the_query ) ) {
		$mg_upc_the_query->reset_listdata();
	}
}