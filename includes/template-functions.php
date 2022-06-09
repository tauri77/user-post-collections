<?php

if ( ! function_exists( 'mg_upc_template_single_title' ) ) {

	/**
	 * Output the list title.
	 */
	function mg_upc_template_single_title() {
		mg_upc_get_template( 'single-mg-upc/title.php' );
	}
}

if ( ! function_exists( 'mg_upc_template_single_author' ) ) {

	/**
	 * Output the list author.
	 */
	function mg_upc_template_single_author() {
		mg_upc_get_template( 'single-mg-upc/author.php' );
	}
}

if ( ! function_exists( 'mg_upc_template_single_description' ) ) {

	/**
	 * Output the list author.
	 */
	function mg_upc_template_single_description() {
		mg_upc_get_template( 'single-mg-upc/description.php' );
	}
}


if ( ! function_exists( 'mg_upc_template_single_items' ) ) {

	/**
	 * Output the list items.
	 */
	function mg_upc_template_single_items() {
		mg_upc_get_template( 'single-mg-upc/items.php' );
	}
}



if ( ! function_exists( 'mg_upc_template_items_pagination' ) ) {

	/**
	 * Output the list items.
	 */
	function mg_upc_template_items_pagination() {
		global $mg_upc_list;

		$args = array(
			'total'   => $mg_upc_list['items_page']['X-WP-TotalPages'],
			'current' => $mg_upc_list['items_page']['X-WP-Page'],
			'base'    => esc_url_raw( add_query_arg( 'list-page', '%#%', false ) ),
			'format'  => '?list-page=%#%',
		);

		mg_upc_get_template( 'single-mg-upc/pagination.php', $args );
	}
}

if ( ! function_exists( 'mg_upc_single_product_button' ) ) {

	/**
	 * Output the button "Add to list..."
	 */
	function mg_upc_single_product_button() {
		mg_upc_get_template( 'mg-upc-wc/single-product-button.php' );
	}
}

if ( ! function_exists( 'mg_upc_single_item_vote_button' ) ) {

	/**
	 * Output the button "Vote"
	 */
	function mg_upc_single_item_vote_button() {
		global $mg_upc_list;
		if ( MG_UPC_Helper::get_instance()->list_type_support( $mg_upc_list['type'], 'vote', true ) ) {
			mg_upc_get_template( 'single-mg-upc/item/actions/vote.php' );
		}
	}
}


if ( ! function_exists( 'mg_upc_single_list_item_vote_data' ) ) {

	/**
	 * Output the vote data
	 */
	function mg_upc_single_list_item_vote_data() {
		global $mg_upc_list;
		if ( MG_UPC_Helper::get_instance()->list_type_support( $mg_upc_list['type'], 'vote', true ) ) {
			mg_upc_get_template( 'single-mg-upc/item/vote-data.php' );
		}
	}
}

if ( ! function_exists( 'mg_upc_single_list_item_numbered_position' ) ) {

	/**
	 * Output the position number
	 */
	function mg_upc_single_list_item_numbered_position() {
		global $mg_upc_list;
		if ( 'numbered' === $mg_upc_list['type'] ) {
			mg_upc_get_template( 'single-mg-upc/item/position-number.php' );
		}
	}
}
