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

if ( ! function_exists( 'mg_upc_template_single_sharing' ) ) {

	/**
	 * Output the share buttons.
	 */
	function mg_upc_template_single_sharing() {
		mg_upc_get_template( 'single-mg-upc/sharing.php' );
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

if ( ! function_exists( 'mg_upc_loop_product_button' ) ) {

	/**
	 * Output the button "Add to list..." on loop product
	 */
	function mg_upc_loop_product_button() {
		mg_upc_get_template( 'mg-upc-wc/loop-product-button.php' );
	}
}

if ( ! function_exists( 'mg_upc_btn_classes' ) ) {
	/**
	 * Get the classes for buttons
	 *
	 * @param string $class
	 *
	 * @return string
	 */
	function mg_upc_btn_classes( $class ) {
		//TODO: option to add or not this classes
		return $class . ' button wp-block-button__link';
	}
}

if ( ! function_exists( 'mg_upc_get_theme_slug_for_templates' ) ) {
	/**
	 * Get a slug identifying the current theme.
	 *
	 * @return string
	 */
	function mg_upc_get_theme_slug_for_templates() {
		return apply_filters( 'mg_upc_theme_slug_for_templates', get_option( 'template' ) );
	}
}

if ( ! function_exists( 'mg_upc_output_content_wrapper' ) ) {

	/**
	 * Output the start of the page wrapper.
	 */
	function mg_upc_output_content_wrapper() {
		mg_upc_get_template( 'global/wrapper-start.php' );
	}
}

if ( ! function_exists( 'mg_upc_output_content_wrapper_end' ) ) {

	/**
	 * Output the end of the page wrapper.
	 */
	function mg_upc_output_content_wrapper_end() {
		mg_upc_get_template( 'global/wrapper-end.php' );
	}
}

if ( ! function_exists( 'mg_upc_get_text' ) ) {

	/**
	 * Get a text mutated by translate or by settings.
	 *
	 * @param string $text
	 * @param string $context
	 *
	 * @return string
	 */
	function mg_upc_get_text( $text, $context = 'mg_upc_list' ) {
		return MG_UPC_Texts::get( $text, $context );
	}
}


if ( ! function_exists( 'mg_upc_show_item_quantity' ) ) {

	/**
	 * Show item quantity.
	 *
	 * @return string
	 */
	function mg_upc_show_item_quantity() {
		global $mg_upc_list;
		if ( MG_UPC_Helper::get_instance()->list_type_support( $mg_upc_list['type'], 'quantity', true ) ) {
			mg_upc_get_template( 'single-mg-upc/item/quantity.php' );
		}
	}
}