<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

if ( ! current_user_can( 'activate_plugins' ) ) {
	return;
}


function clear_blog() {
	delete_option( 'mg_upc_single_page' );
	delete_option( 'mg_upc_single_page_mode' );
	delete_option( 'mg_upc_flush_rewrite' );
	delete_option( 'mg_upc_db_version' );

	$options = array(
		'mg_upc_texts',
		'mg_upc_button_position',
		'mg_upc_my_orderby',
		'mg_upc_my_order',
		'mg_upc_purge_on_uninstall',
		'mg_upc_single_title',
		'mg_upc_store_vote_ip',
		'mg_upc_store_vote_anonymize_ip',
		'mg_upc_type_simple',
		'mg_upc_type_numbered',
		'mg_upc_type_vote',
		'mg_upc_type_favorites',
		'mg_upc_type_bookmarks',
		'mg_upc_button_position_product',
		'mg_upc_loop_button_position_product',
		'mg_upc_page_show_price',
		'mg_upc_page_show_stock',
		'mg_upc_page_add_to_cart',
		'mg_upc_modal_show_price',
		'mg_upc_modal_show_stock',
	);
	foreach ( $options as $option ) {
		delete_option( $option );
	}

	// delete database table
	global $wpdb;
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}upc_votes" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}upc_items" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}upc_lists" );

}

if ( get_option( 'mg_upc_purge_on_uninstall' ) === 'on' ) {

	if ( is_multisite() ) {

		// get registered site IDs
		$site_ids = array();
		$sites    = get_sites();
		foreach ( $sites as $site ) {
			$site_ids[] = $site->id;
		}

		foreach ( $site_ids as $site_id ) {
			// switch to next blog
			switch_to_blog( $site_id );
			clear_blog();
		}
		// restore the current blog, after calling switch_to_blog()
		restore_current_blog();
	} else {
		clear_blog();
	}
}

wp_cache_flush();
