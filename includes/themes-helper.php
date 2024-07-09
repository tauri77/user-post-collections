<?php

/**
 * Check if a post already exists on collection
 *
 * @param int $post_id
 * @param int|string $collection If is string use as special type (bookmarks|favorites), else list id
 *
 * @return false|int The collection ID or false
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

	if ( ! $list instanceof MG_UPC_List ) {
		$list = MG_UPC_List::get_instance( $list );
	}

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
	if ( ! $list instanceof MG_UPC_List ) {
		$list = MG_UPC_List::get_instance( $list );
	}

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

	if ( ! $list instanceof MG_UPC_List ) {
		$list = MG_UPC_List::get_instance( $list );
	}

	// Set by Page Controller
	return apply_filters( 'mg_upc_get_the_permalink', '', $list );
}
