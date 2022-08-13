<?php


class MG_UPC_Database extends MG_UPC_Module {

	public function __construct() {
		add_action( 'delete_user', array( $this, 'on_delete_user' ), 10, 2 );
		add_action( 'wpmu_delete_user', array( $this, 'on_delete_user_mu' ), 10, 1 );
	}

	public function activate( $network_wide ) { }

	public function deactivate() { }

	public function register_hook_callbacks() { }

	public function init() { }

	/**
	 * Remove list of user deleted on mu
	 *
	 * @param $id
	 */
	public function on_delete_user_mu( $id ) {
		if ( is_multisite() ) {
			$blogs = get_blogs_of_user( $id );
			if ( ! empty( $blogs ) ) {
				foreach ( $blogs as $blog ) {
					switch_to_blog( $blog->userblog_id );
					$this->on_delete_user( $id, null );
					restore_current_blog();
				}
			}
		} else {
			$this->on_delete_user( $id, null );
		}
	}

	/**
	 * Remove list of user deleted
	 *
	 * @param $id
	 * @param $reassign
	 */
	public function on_delete_user( $id, $reassign ) {
		/** @global User_Post_Collections $mg_upc Global plugin object. */
		global $mg_upc;
		/** @global MG_UPC_List_Type[] $mg_upc_list_types Global array with list types. */
		global $mg_upc_list_types;
		if ( null === $reassign ) {
			$list_types_to_delete = array();
			foreach ( $mg_upc_list_types as $list_type ) {
				if ( $list_type->delete_with_user() ) {
					$list_types_to_delete[] = $list_type->name;
				} else {
					$list_types_to_delete[] = $list_type->name;
				}
			}
			$mg_upc->model->deleted_all_from_user( $id, $list_types_to_delete );
		} else {
			//search for reassign or delete list ( always_exists list types that already has the reassign user)
			$list_types_to_delete   = array();
			$list_types_to_reassign = array();
			foreach ( $mg_upc_list_types as $list_type ) {
				if ( $list_type->support( 'always_exists' ) ) {
					try {
						if ( null === $mg_upc->model->find_always_exist( $list_type->name, $reassign ) ) {
							$list_types_to_reassign[] = $list_type->name;
						} else {
							$list_types_to_delete[] = $list_type->name;
						}
					} catch ( MG_UPC_Invalid_Field_Exception $e ) {
						error_log( 'MG_UPC: Error on delete list of removed user.' );
					}
				} else {
					$list_types_to_reassign[] = $list_type->name;
				}
			}
			$mg_upc->model->reassign_all_from_user( $id, $reassign, $list_types_to_reassign );
			$mg_upc->model->deleted_all_from_user( $id, $list_types_to_delete );
		}
	}

	/**
	 * Update the database on upgrade plugin
	 *
	 * @param int $db_version
	 */
	public function upgrade( $db_version = 0 ) {
		if ( version_compare( $db_version, '0.1.0', '<' ) ) {
			self::create_db_tables();
		}
		if ( version_compare( $db_version, '0.1.2', '<' ) ) {
			self::update_db_tables_2();
		}
		if ( version_compare( $db_version, '0.8.21', '<' ) ) {
			self::add_cart_quantity();
		}
		if ( version_compare( $db_version, '0.8.23', '<' ) ) {
			self::add_addon_json();
		}
	}

	/**
	 * Initial database
	 */
	private static function create_db_tables() {
		global $wpdb;
		/** @global User_Post_Collections $mg_upc Global plugin object. */
		global $mg_upc;

		$charset_collate = '';

		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset} ";
		}

		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= "COLLATE {$wpdb->collate}";
		}

		$table_lists = $mg_upc->model->get_table_list();
		$table_items = $mg_upc->model->items->get_table_list_items();

		$sql = "
        CREATE TABLE {$table_lists} (
				ID bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				author bigint(20) DEFAULT NULL,
				title mediumtext NOT NULL,
				slug varchar(200) NOT NULL DEFAULT '',
				content longtext NOT NULL,
				status varchar(20) NOT NULL DEFAULT '',
				type varchar(20) NOT NULL DEFAULT '',
				count tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
				views bigint(20) UNSIGNED NOT NULL DEFAULT 0,
				vote_counter bigint(20) UNSIGNED NOT NULL DEFAULT 0,
				created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				modified datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (ID),
            KEY slug (slug),
			KEY type_status_created (type,status,created,ID),
			KEY author_type (author,type)
        ) {$charset_collate} ENGINE=InnoDB;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		$sql = "CREATE TABLE {$table_items} (
			list_id bigint(20) UNSIGNED NOT NULL,
			post_id bigint(20) UNSIGNED NOT NULL,
			position bigint(20) UNSIGNED NOT NULL DEFAULT 0,
			votes bigint(20) UNSIGNED NOT NULL DEFAULT 0,
		 	added datetime NOT NULL DEFAULT current_timestamp(),
			description varchar(400) NOT NULL DEFAULT '',
  			KEY list_post (list_id, post_id),
  			KEY post_id (post_id),
  			KEY list_position (list_id,position),
  			KEY list_votes (list_id,votes)
        ) {$charset_collate} ENGINE=InnoDB;";

		dbDelta( $sql );
	}

	/**
	 * Add votes table
	 *
	 * @since 0.1.2
	 */
	private static function update_db_tables_2() {
		global $wpdb;
		/** @global User_Post_Collections $mg_upc Global plugin object. */
		global $mg_upc;

		$charset_collate = '';

		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset} ";
		}

		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= "COLLATE {$wpdb->collate}";
		}

		$table_votes = $mg_upc->model->votes->get_table_list_votes();

		$sql = "
        CREATE TABLE {$table_votes} (
			list_id bigint(20) UNSIGNED NOT NULL,
			post_id bigint(20) UNSIGNED NOT NULL,
			user_id bigint(20) UNSIGNED NOT NULL,
			ip varchar(100) NOT NULL,
		 	added datetime NOT NULL DEFAULT current_timestamp(),
  			KEY list_user_post (list_id, user_id, post_id),
  			KEY user_id (user_id),
  			KEY post_id (post_id),
  			KEY list_post (list_id, post_id)
        ) {$charset_collate} ENGINE=InnoDB;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	private static function add_cart_quantity() {
		/** @global User_Post_Collections $mg_upc Global plugin object. */
		global $mg_upc;
		global $wpdb;

		$table_items = $mg_upc->model->items->get_table_list_items();
		//phpcs:ignore
		$wpdb->query( "ALTER TABLE {$table_items} ADD `quantity` int(11) UNSIGNED NOT NULL DEFAULT 0 AFTER `votes`;" );
	}

	private static function add_addon_json() {
		/** @global User_Post_Collections $mg_upc Global plugin object. */
		global $mg_upc;
		global $wpdb;

		$table_items = $mg_upc->model->items->get_table_list_items();
		//phpcs:ignore
		$wpdb->query( "ALTER TABLE {$table_items} ADD `addon_json` longtext DEFAULT NULL AFTER `description`;" );
	}
}
