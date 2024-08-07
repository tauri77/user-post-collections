<?php

/**
 * List content.
 *
 * @see mg_upc_template_single_title()
 * @see mg_upc_template_single_author()
 * @see mg_upc_template_single_description()
 * @see MG_UPC_Woocommerce::item_cart_all_button()
 * @see mg_upc_template_single_items()
 */
add_action( 'mg_upc_single_list_content', 'mg_upc_template_single_title', 5 );
add_action( 'mg_upc_single_list_content', 'mg_upc_template_single_author', 10 );
add_action( 'mg_upc_single_list_content', 'mg_upc_template_single_sharing', 15 );
add_action( 'mg_upc_single_list_content', 'mg_upc_template_single_description', 20 );
add_action( 'mg_upc_single_list_content', array( 'MG_UPC_Woocommerce', 'item_cart_all_button' ), 25 );
add_action( 'mg_upc_single_list_content', 'mg_upc_template_single_items', 30 );
add_action( 'mg_upc_after_single_list_content', 'mg_upc_template_items_pagination', 10 );

/**
 * Item list content
 *
 * @see mg_upc_single_list_item_numbered_position()
 * @see mg_upc_single_list_item_vote_data()
 * @see MG_UPC_Woocommerce::show_price()
 * @see mg_upc_single_item_vote_button()
 * @see MG_UPC_Woocommerce::item_cart_button()
 * @see mg_upc_single_product_button()
 */
add_action( 'mg_upc_single_list_item_before_first_child', 'mg_upc_single_list_item_numbered_position', 10 );
add_action( 'mg_upc_single_list_item_after_description', 'mg_upc_single_list_item_vote_data', 10 );
add_action( 'mg_upc_single_list_item_after_title', array( 'MG_UPC_Woocommerce', 'show_price' ), 10 );
add_action( 'mg_upc_single_list_item_after_title', array( 'MG_UPC_Woocommerce', 'show_stock' ), 15 );
add_action( 'mg_upc_single_list_item_after_data', 'mg_upc_show_item_quantity', 10 );
add_action( 'mg_upc_single_list_item_action', 'mg_upc_single_item_vote_button', 5 );
add_action( 'mg_upc_single_list_item_action', array( 'MG_UPC_Woocommerce', 'item_cart_button' ), 10 );
add_action( 'mg_upc_single_product_buttons', 'mg_upc_single_product_button', 10 );

//WC Loop
add_action( 'mg_upc_loop_product_buttons', 'mg_upc_loop_product_button', 10 );

/**
 * Empty Collection
 *
 * @see mg_upc_template_no_items_found()
 */
add_action( 'mg_upc_no_items_found', 'mg_upc_template_no_items_found', 10 );

/**
 * Content Wrappers.
 *
 * @see mg_upc_output_content_wrapper()
 * @see mg_upc_output_content_wrapper_end()
 */
add_action( 'mg_upc_before_main_content', 'mg_upc_output_content_wrapper', 10 );
add_action( 'mg_upc_after_main_content', 'mg_upc_output_content_wrapper_end', 10 );


/**
 * List content.
 *
 * @see mg_upc_template_loop_single_thumbs()
 * @see mg_upc_template_loop_single_info_start()
 * @see mg_upc_template_loop_single_title()
 * @see mg_upc_template_loop_single_author()
 * @see mg_upc_template_loop_single_meta()
 * @see mg_upc_template_loop_single_description()
 * @see mg_upc_template_loop_single_info_end()
 */
add_action( 'mg_upc_loop_single_list_content', 'mg_upc_template_loop_single_thumbs', 10 );
add_action( 'mg_upc_loop_single_list_content', 'mg_upc_template_loop_single_info_start', 15 );
add_action( 'mg_upc_loop_single_list_content', 'mg_upc_template_loop_single_title', 20 );
add_action( 'mg_upc_loop_single_list_content', 'mg_upc_template_loop_single_author', 30 );
add_action( 'mg_upc_loop_single_list_content', 'mg_upc_template_loop_single_meta', 40 );
add_action( 'mg_upc_loop_single_list_content', 'mg_upc_template_loop_single_description', 50 );
add_action( 'mg_upc_loop_single_list_content', 'mg_upc_template_loop_single_info_end', 60 );
add_action( 'mg_upc_after_archive_content', 'mg_upc_template_archive_pagination', 10 );

/**
 * Empty List Collections
 *
 * @see mg_upc_template_loop_empty()
 */
add_action( 'mg_upc_loop_empty', 'mg_upc_template_loop_empty', 10 );
