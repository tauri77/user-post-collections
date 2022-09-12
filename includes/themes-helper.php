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
	if ( is_string( $collection ) ) {
		global $mg_upc;
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


