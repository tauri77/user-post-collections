<?php

/**
 * List content.
 *
 * @see mg_upc_template_single_title()
 * @see mg_upc_template_single_author()
 * @see mg_upc_template_single_description()
 * @see mg_upc_template_single_items()
 */
add_action( 'mg_upc_single_list_content', 'mg_upc_template_single_title', 5 );
add_action( 'mg_upc_single_list_content', 'mg_upc_template_single_author', 10 );
//add_action( 'mg_upc_single_list_content', 'mg_upc_template_single_sharing', 10 );
add_action( 'mg_upc_single_list_content', 'mg_upc_template_single_description', 20 );
add_action( 'mg_upc_single_list_content', 'mg_upc_template_single_items', 30 );



add_action( 'mg_upc_after_single_list_content', 'mg_upc_template_items_pagination', 10 );


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
