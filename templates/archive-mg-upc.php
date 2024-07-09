<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

get_header( 'mg_upc' );

/**
 * mg_upc_before_main_content hook.
 *
 * @hooked mg_upc_output_content_wrapper - 10 (outputs opening divs for the content)
 */
do_action( 'mg_upc_before_main_content' );

mg_upc_get_template_part( 'content', 'archive-mg-upc' );

/**
 * mg_upc_after_main_content hook.
 *
 * @hooked mg_upc_output_content_wrapper_end - 10 (outputs closing divs for the content)
 */
do_action( 'mg_upc_after_main_content' );

get_footer( 'mg_upc' );

