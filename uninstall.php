<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

if ( ! current_user_can( 'activate_plugins' ) ) {
	return;
}


function mg_upc_uninstall_remove_from_role( $role ) {
	$list_types = MG_UPC_Helper::get_instance()->get_list_types( true );
	foreach ( $list_types as $list_type ) {
		$caps = $list_type->get_cap();
		//Capabilities for create/publish/delete list
		$post_capabilities = array(
			'edit_posts',
			'create_posts',
			'delete_posts',
			'publish_posts',
			'edit_posts',
			'create_posts',
			'delete_posts',
			'publish_posts',
			'edit_others_posts',
			'read_private_posts',
		);
		foreach ( $post_capabilities as $post_cap_name ) {
			$role->remove_cap( $caps->$post_cap_name );
		}
	}
}

function mg_upc_clear_blog() {
	//register blog list types
	$GLOBALS['mg_upc_list_types']    = array();
	$GLOBALS['mg_upc_list_statuses'] = array();

	$register = new MG_UPC_List_Types_Register();
	$register->init();

	$all_roles = wp_roles()->roles;
	foreach ( $all_roles as $role => $details ) {
		$role_object = get_role( $role );
		if ( $role_object ) {
			mg_upc_uninstall_remove_from_role( $role_object );
		}
	}

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
		'mg_upc_anh_notices',
		'mg_upc_api_item_per_page',
		'mg_upc_item_per_page',
		'mg_upc_post_stats',
		'mg_upc_share_buttons',
		'mg_upc_share_buttons_client',
		'mg_upc_ajax_load',
	);
	foreach ( $options as $option ) {
		delete_option( $option );
	}

	// delete database table
	global $wpdb;
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}upc_votes" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}upc_items" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}upc_lists" );

	delete_metadata( 'post', 0, 'mg_upc_listed', '', true );
	$list_types = MG_UPC_Helper::get_instance()->get_list_types( true );
	foreach ( $list_types as $list_type ) {
		delete_metadata( 'post', 0, 'mg_upc_listed_' . $list_type->name, '', true );
	}
}

if ( get_option( 'mg_upc_purge_on_uninstall' ) === 'on' ) {

	require_once __DIR__ . '/includes/utils.php';
	require_once __DIR__ . '/classes/mg-upc-module.php';
	require_once __DIR__ . '/includes/mg-upc-helper.php';
	require_once __DIR__ . '/includes/mg-upc-list-type.php';
	require_once __DIR__ . '/includes/list-types.php';
	require_once __DIR__ . '/classes/mg-upc-list-types-register.php';

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
			mg_upc_clear_blog();
		}
		// restore the current blog, after calling switch_to_blog()
		restore_current_blog();
	} else {
		mg_upc_clear_blog();
	}
}

wp_cache_flush();
