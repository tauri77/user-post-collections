<?php


class MG_UPC_List_Types_Register extends MG_UPC_Module {

	public function __construct() { }

	public function activate( $network_wide ) { }

	public function deactivate() { }

	public function register_hook_callbacks() { }

	public function init() {

		mg_upc_register_list_status(
			'publish',
			array(
				'label'       => _x( 'Published', 'post status', 'user-post-collections' ),
				'public'      => true,
				/* translators: %s: Number of published posts. */
				'label_count' => _n_noop(
					'Published <span class="count">(%s)</span>',
					'Published <span class="count">(%s)</span>'
				),
			)
		);

		mg_upc_register_list_status(
			'private',
			array(
				'label'       => _x( 'Private', 'post status', 'user-post-collections' ),
				'private'     => true,
				/* translators: %s: Number of private posts. */
				'label_count' => _n_noop(
					'Private <span class="count">(%s)</span>',
					'Private <span class="count">(%s)</span>'
				),
			)
		);

		$list_types = array(
			'simple'   => array(
				'label'              => __( 'Simple List', 'user-post-collections' ),
				'plural_label'       => __( 'Simple Lists', 'user-post-collections' ),
				'description'        => __( 'Simple list sorted according to their items added', 'user-post-collections' ),
				'supported_features' => array(
					'editable_title',
					'editable_content',
					'editable_item_description',
					'show_in_my_lists',
					'show_in_settings',
				),
			),
			'numbered' => array(
				'label'              => __( 'Numbered List', 'user-post-collections' ),
				'plural_label'       => __( 'Numbered Lists', 'user-post-collections' ),
				'description'        => __( 'List with your numbered items. You can edit the order in which the items will be displayed.', 'user-post-collections' ),
				'default_orderby'    => 'position',
				'default_order'      => 'asc',
				'supported_features' => array(
					'editable_title',
					'editable_content',
					'editable_item_description',
					'show_in_my_lists',
					'sortable',
					'show_in_settings',
				),
			),
			'vote'     => array(
				'label'              => __( 'Polling list', 'user-post-collections' ),
				'plural_label'       => __( 'Polling Lists', 'user-post-collections' ),
				'description'        => __( 'You can ask others for their opinion', 'user-post-collections' ),
				'default_orderby'    => 'votes',
				'default_order'      => 'desc',
				'supported_features' => array(
					'editable_title',
					'editable_content',
					'editable_item_description',
					'show_in_my_lists',
					'vote',
					'show_in_settings',
				),
			),
		);

		/*
		 * always_exists
		 * this create an end point with favorites instead the ID
		 * dont create this types, add first item create this
		 */
		$list_types['favorites'] = array(
			'label'              => __( 'Favorites', 'user-post-collections' ),
			'plural_label'       => __( 'Favorites Lists', 'user-post-collections' ),
			'description'        => __( 'List to add your favorites', 'user-post-collections' ),
			'default_title'      => __( 'Favorites', 'user-post-collections' ),
			'default_status'     => 'private',
			'sticky'             => 1,
			'supported_features' => array(
				'editable_title',
				'editable_content',
				'editable_item_description',
				'show_in_my_lists',
				'always_exists', //this create an end point with bookmarks instead the ID
				'show_in_settings',
			),
			'default_supports'   => array(
				'editable_item_description',
				'show_in_my_lists',
				'always_exists', //this create an end point with bookmarks instead the ID
				'show_in_settings',
			),
		);

		$list_types['bookmarks'] = array(
			'label'              => __( 'Bookmarks', 'user-post-collections' ),
			'plural_label'       => __( 'Bookmarks Lists', 'user-post-collections' ),
			'description'        => __( 'List to add items that you will use in the future', 'user-post-collections' ),
			'default_title'      => __( 'Bookmarks', 'user-post-collections' ),
			'default_status'     => 'private',
			'sticky'             => 2,
			'supported_features' => array(
				'editable_title',
				'editable_item_description',
				'show_in_my_lists',
				'always_exists', //this create an end point with bookmarks instead the ID
				'show_in_settings',
			),
		);

		foreach ( $list_types as $list_type => $args ) {
			mg_upc_register_list_type( $list_type, $args );
		}
	}

	/**
	 * Update the database on upgrade plugin
	 *
	 * @param int $db_version
	 */
	public function upgrade( $db_version = 0 ) {
		if ( version_compare( $db_version, '0.6.22', '<' ) ) {
			self::set_initial_roles_caps( MG_UPC_Helper::get_instance()->get_list_types( true ) );
		}
	}

	/**
	 * Initial roles capsMG_UPC_Helper::get_instance()->get_list_type
	 *
	 * @param MG_UPC_List_Type[] $list_types
	 */
	public static function set_initial_roles_caps( $list_types ) {
		$all_roles = wp_roles()->roles;
		foreach ( $all_roles as $role => $details ) {
			$role_object = get_role( $role );
			if ( $role_object ) {
				self::set_initial_role_caps( $role_object, $list_types );
			}
		}
	}

	/**
	 * @param WP_Role $role
	 *
	 * @param MG_UPC_List_Type[] $list_types
	 */
	private static function set_initial_role_caps( $role, $list_types ) {
		foreach ( $list_types as $list_type ) {
			$caps = $list_type->get_cap();
			//Capabilities for create/publish/delete list
			$grant_listing = array(
				'edit_posts',
				'create_posts',
				'delete_posts',
				'publish_posts',
			);
			$grant_listing = apply_filters( 'mg_upc_grant_initial_to_all', $grant_listing, $role, $list_type );

			//Copy the rest capabilities from post
			$post_capabilities = array(
				'edit_posts',
				'create_posts',
				'delete_posts',
				'publish_posts',
				'edit_others_posts',
				'delete_others_posts',
				'read_private_posts',
			);
			$post_capabilities = array_diff( $post_capabilities, $grant_listing );

			foreach ( $post_capabilities as $post_cap_name ) {
				if ( $role->has_cap( $post_cap_name ) ) {
					$role->add_cap( $caps->$post_cap_name );
				} else {
					$role->remove_cap( $caps->$post_cap_name );
				}
			}
			foreach ( $grant_listing as $post_cap_name ) {
				$role->add_cap( $caps->$post_cap_name );
			}
		}
	}
}
