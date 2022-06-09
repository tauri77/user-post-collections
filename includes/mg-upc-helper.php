<?php


class MG_UPC_Helper {

	private static $instance;

	private function __construct() {

		add_action( 'init', array( $this, 'init' ) );
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

	public function init() {
		$base_types = array(
			'simple'   => array(
				'label' => 'Simple',
			),
			'numbered' => array(
				'label'           => 'Numbered',
				'default_orderby' => 'position',
				'default_order'   => 'asc',
				'supports'        => array(
					'editable_title',
					'editable_content',
					'editable_item_description',
					'show_in_my_lists',
					'sortable',
				),
			),
			'vote'     => array(
				'label'           => 'Poll',
				'default_orderby' => 'votes',
				'default_order'   => 'desc',
				'enabled'         => false,
				'supports'        => array(
					'editable_title',
					'editable_content',
					'editable_item_description',
					'show_in_my_lists',
					'vote',
				),
			),
		);

		foreach ( $base_types as $base_type => $args ) {
			mg_upc_register_list_type( $base_type, $args );
		}

		/*
		 * always_exists
		 * this create an end point with favorites instead the ID
		 * dont create this types, add first item create this
		 */
		$list_types = array(
			'favorites' => array(
				'label'              => 'Favorites',
				'default_title'      => 'Favorites',
				'default_status'     => 'private',
				'sticky'             => 1,
				'available_statuses' => array( 'private' ),
				'supports'           => array(
					'editable_item_description',
					'show_in_my_lists',
					'always_exists', //this create an end point with bookmarks instead the ID
				),
			),
			'bookmarks' => array(
				'label'          => 'Bookmarks',
				'default_title'  => 'Bookmarks',
				'default_status' => 'private',
				'sticky'         => 2,
				'supports'       => array(
					'editable_title',
					'editable_item_description',
					'show_in_my_lists',
					'always_exists', //this create an end point with bookmarks instead the ID
				),
			),
		);

		foreach ( $list_types as $base_type => $args ) {
			mg_upc_register_list_type( $base_type, $args );
		}
	}

	/**
	 * Get list type object from name
	 *
	 * @param string $type_name
	 * @param bool   $include_disabled
	 *
	 * @return false|MG_UPC_List_Type The list type object or false
	 */
	public function get_list_type( $type_name, $include_disabled = false ) {
		$types = $this->get_list_types( $include_disabled );
		return array_key_exists( $type_name, $types ) ? $types[ $type_name ] : false;
	}

	/**
	 * Get list of list types
	 *
	 * @param bool $include_disabled
	 *
	 * @return MG_UPC_List_Type[]
	 */
	public function get_list_types( $include_disabled = false ) {
		global $mg_upc_list_types;

		if ( ! $include_disabled ) {
			return array_filter(
				$mg_upc_list_types,
				function ( $type ) {
					return $type['enabled'];
				}
			);
		}

		return $mg_upc_list_types;
	}

	/**
	 * Check if a list_type support a feature
	 *
	 * @param string $list_type
	 * @param string $feature
	 * @param bool $include_disabled
	 *
	 * @return bool
	 */
	public function list_type_support( $list_type, $feature, $include_disabled = false ) {
		$list_type = $this->get_list_type( $list_type, $include_disabled );
		if ( $list_type && $list_type->support( $feature ) ) {
			return true;
		}

		return false;
	}

	public function get_max_list_items( $type ) {
		$type = $this->get_list_type( $type );
		if ( empty( $type ) ) {
			return 0;
		}
		return $type['max_items'];
	}

	public function get_initial_always_exist_list( $always_exist_type ) {

		$list = array(
			'ID'      => $always_exist_type,
			'title'   => '',
			'content' => '',
			'type'    => $always_exist_type,
			'count'   => '',
			'status'  => array(),
			'author'  => get_current_user_id(),
		);

		$list_type = $this->get_list_type( $always_exist_type, true );
		if ( false !== $list_type ) {
			$list = array_merge(
				$list,
				array(
					'title'   => $list_type['default_title'] ? $list_type['default_title'] : $list_type['label'],
					'content' => $list_type['default_content'] ? $list_type['default_content'] : '',
					'status'  => $list_type['default_status'],
				)
			);
		}

		return apply_filters( 'initial_always_exist_list', $list, $always_exist_type );
	}

	public function get_stick_list_types( $include_disabled = false ) {
		$types = $this->get_list_types( $include_disabled );

		$filtered = array_filter(
			$types,
			function ( $type ) {
				return $type['sticky'] > 0;
			}
		);

		return array_keys( $filtered );
	}

	public function get_my_list_types( $include_disabled = false ) {
		$types = $this->get_list_types( $include_disabled );

		$filtered = array_filter(
			$types,
			function ( $type ) {
				return $type->support( 'show_in_my_lists' );
			}
		);

		return array_keys( $filtered );
	}

	/**
	 * Get the list types that has always exist enable
	 *
	 * @param bool|string $post_type            (Optional) Only include lists that can contain this post type.
	 * @param bool        $include_disabled
	 *
	 * @return array
	 */
	public function get_always_exist_list_types( $post_type = false, $include_disabled = false ) {
		$types = $this->get_list_types( $include_disabled );

		$filtered = array_filter(
			$types,
			function ( $type ) use ( $post_type ) {
				return $type->support( 'always_exists' ) &&
						( false === $post_type || in_array( $post_type, $type['available_post_types'], true ) );
			}
		);

		return array_keys( $filtered );
	}

	public function get_available_list_types( $post_type, $include_disabled = false ) {
		$list_types = $this->get_list_types( $include_disabled );
		if ( empty( $list_types ) ) {
			return array();
		}

		$compat = array_filter(
			$list_types,
			function( $type ) use ( $post_type ) {
				return in_array( $post_type, $type['available_post_types'], true );
			}
		);

		return array_keys( $compat );
	}

	public function get_available_post_types( $list_type ) {
		$type = $this->get_list_type( $list_type );
		if ( empty( $type ) ) {
			return array();
		}
		return $type['available_post_types'];
	}

	public function is_available_post_type_for_list_type( $post_type, $list_type ) {

		$valid_post_types = $this->get_available_post_types( $list_type );

		if ( in_array( $post_type, $valid_post_types, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * @param $user_id
	 * @param string $pre
	 * @param string $suf
	 *
	 * @return string   $pre+$user_login+$suf or empty string on fail
	 */
	public function get_user_login( $user_id, $pre = '', $suf = '' ) {
		$user_info = get_userdata( $user_id );
		if ( $user_info instanceof WP_User ) {
			return $pre . $user_info->user_login . $suf;
		}

		return '';
	}

	public function post_exist( $post_id ) {
		global $wpdb;

		$post_id = (int) $post_id;
		if ( ! $post_id ) {
			return false;
		}
		$_post = wp_cache_get( $post_id, 'posts' );
		if ( ! $_post ) {
			$_post = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT DISTINCT(1) FROM `{$wpdb->posts}` WHERE `ID` = %d",
					$post_id
				)
			);
			if ( ! $_post ) {
				return false;
			}
		}
		return true;
	}

	public function user_id_exists( $user_id ) {
		global $wpdb;

		// Check cache:
		if ( wp_cache_get( $user_id, 'users' ) ) {
			return true;
		}

		// Check database:
		if (
			$wpdb->get_var(
				$wpdb->prepare(
					"SELECT EXISTS (SELECT 1 FROM `{$wpdb->users}` WHERE `ID` = %d)",
					$user_id
				)
			)
		) {
			return true;
		}

		return false;
	}

}
