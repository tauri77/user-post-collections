<?php
/** @noinspection SqlNoDataSourceInspection */
/** @noinspection SqlResolve */

class MG_List_Items_Model {

	private static $instance;

	/**
	 * @var MG_UPC_Cache for not repeat sql queries
	 */
	protected $cache;

	/**
	 * @var MG_UPC_Helper
	 */
	private $helper;

	private function __construct() {
		$this->helper = MG_UPC_Helper::get_instance();
		$this->cache  = new MG_UPC_Cache();
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get list items table name
	 *
	 * @return string
	 */
	public function get_table_list_items() {
		global $wpdb;

		return $wpdb->prefix . 'upc_items';
	}

	/**
	 * Vote to an item
	 *
	 * @param int $list_id
	 * @param int $post_id
	 *
	 * @return bool True on success
	 *
	 * @throws MG_UPC_Item_Not_Found_Exception
	 * @throws Exception
	 */
	public function vote( $list_id, $post_id ) {
		global $wpdb;
		$post_id = (int) $post_id;
		$list_id = (int) $list_id;

		if ( ! $this->item_exists( $list_id, $post_id ) ) {
			throw new MG_UPC_Item_Not_Found_Exception( 'Not item found on list' );
		}

		$aff = $wpdb->query(
			$wpdb->prepare(
			// phpcs:ignore
				"UPDATE `{$this->get_table_list_items()}` SET `votes` = votes + 1 WHERE ".
				'`list_id` = %d AND `post_id` = %d',
				array( $list_id, $post_id )
			)
		);

		if ( $aff > 0 ) {
			$this->cache->remove();
			do_action( 'mg_upc_vote', $list_id, $post_id );
			return true;
		}

		return false;
	}

	/**
	 * Delete all post from the list, this will not update the items counters!
	 *
	 * @param $list_id
	 *
	 * @return bool|false|int
	 */
	public function delete_all_posts_from_list( $list_id ) {
		global $wpdb;

		$this->cache->remove();

		return $wpdb->delete(
			$this->get_table_list_items(),
			array( 'list_id' => $list_id ),
			array( '%d' )
		);
	}

	/**
	 * List list items
	 *
	 * @param array $args Array with filters and configuration
	 *
	 * @return array|mixed|null
	 */
	public function items( $args ) {
		global $wpdb;

		$defaults = array(
			'page'           => 1,
			'items_per_page' => 50,
			'orderby'        => 'added',
			'order'          => 'asc',
		);

		$args = array_merge( $defaults, $args );

		if ( ! in_array( $args['orderby'], array( 'votes', 'position', 'post_id', 'added' ), true ) ) {
			$args['orderby'] = false;
		}

		$args['items_per_page'] = absint( $args['items_per_page'] );
		$args['page']           = absint( $args['page'] );

		$cache_key = md5( wp_json_encode( $args ) );

		$items = $this->cache->get( 'items', $cache_key );
		if ( null === $items ) {

			$select_count = "SELECT COUNT(*) FROM `{$this->get_table_list_items()}` ";
			$select       = 'SELECT `list_id`, `post_id`, `votes`, `position`, `description`, `quantity`, `addon_json`' .
							"FROM `{$this->get_table_list_items()}` ";
			$sql          = '';

			$where   = array();
			$prepare = array();

			$int_filters = array( 'list_id', 'post_id' );
			foreach ( $int_filters as $prop ) {
				if ( ! empty( $args[ $prop ] ) ) {
					if ( is_array( $args[ $prop ] ) ) {
						$where_status = array();
						foreach ( $args[ $prop ] as $value ) {
							$where_status[] .= '%d';
							$prepare[]       = (int) $value;
						}
						$where[] = '( `' . $prop . '` IN (' . implode( ',', $where_status ) . '))';
					} else {
						$where[]   = '`' . $prop . '` = %d';
						$prepare[] = (int) $args[ $prop ];
					}
				}
			}

			$sql .= ' WHERE ' . implode( ' AND ', $where );

			if ( $args['items_per_page'] > 1 ) { //for find one not run count query
				$total = (int) $wpdb->get_var(
					$wpdb->prepare(
					// phpcs:ignore
						$select_count . $sql,
						$prepare
					)
				);
			}

			if ( ! empty( $args['orderby'] ) ) {
				$sql .= ' ORDER BY ' . $args['orderby'];
				if ( isset( $args['order'] ) && 'desc' === $args['order'] ) {
					$sql .= ' DESC';
				} else {
					$sql .= ' ASC';
				}
			}

			if ( $args['items_per_page'] > 0 ) {
				if ( $args['page'] > 1 ) {
					$offset = $args['items_per_page'] * ( $args['page'] - 1 );
					$offset = max( 0, $offset );

					$sql      .= ' LIMIT %d, %d';
					$prepare[] = (int) $offset;
					$prepare[] = (int) $args['items_per_page'];
				} else {
					$sql      .= ' LIMIT %d';
					$prepare[] = (int) $args['items_per_page'];
				}
			}

			$results = $wpdb->get_results(
			// phpcs:ignore
				$wpdb->prepare( $select . $sql, $prepare )
			);

			if ( ! isset( $total ) ) {
				$total = count( $results );
			}

			$items = array(
				'items'       => $results,
				'total'       => $total,
				'total_pages' => $args['items_per_page'] > 0 ? ceil( $total / $args['items_per_page'] ) : 1,
				'current'     => $args['page'],
			);

			$this->cache->add( 'items', $cache_key, $items );

		}
		return $items;
	}

	/**
	 * Remove an item from list
	 *
	 * @param int $list_id
	 * @param int $post_id
	 *
	 * @return false|int Removed items count
	 *
	 * @throws Exception
	 */
	public function remove_item( $list_id, $post_id ) {
		/** @global User_Post_Collections $mg_upc Global plugin object. */
		global $mg_upc;
		global $wpdb;

		$post_id = (int) $post_id;
		$list_id = (int) $list_id;

		$deleted_items = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore
				"SELECT * FROM `{$this->get_table_list_items()}` WHERE `list_id` = %d AND `post_id` = %d",
				array(
					$list_id,
					$post_id,
				)
			)
		);

		do_action( 'mg_upc_pre_remove_item', $list_id, $post_id, $deleted_items );

		$deleted_count = $wpdb->delete(
			$this->get_table_list_items(),
			array(
				'list_id' => $list_id,
				'post_id' => $post_id,
			),
			array( '%d', '%d' )
		);

		$this->cache->remove();

		do_action( 'mg_upc_remove_item', $list_id, $post_id, $deleted_count );

		if ( $mg_upc->model->support( $list_id, 'sortable' ) ) {
			$this->renumber( $list_id );
		}

		return $deleted_count;
	}

	/**
	 * Move the position of an item
	 *
	 * @param int $list_id List id
	 * @param int $post_id Item post id
	 * @param int $to Destination position
	 *
	 * @throws MG_UPC_Item_Not_Found_Exception
	 * @throws Exception
	 */
	public function item_move( $list_id, $post_id, $to ) {
		global $wpdb;

		$post_id = (int) $post_id;
		$list_id = (int) $list_id;
		$to      = (int) $to;

		$item = $this->get_item( $list_id, $post_id );
		if ( empty( $item ) ) {
			throw new MG_UPC_Item_Not_Found_Exception( 'Item not found' );
		}

		$from = (int) $item->position;
		if ( $to > $from ) {
			$wpdb->query(
				$wpdb->prepare(
				// phpcs:ignore
					"UPDATE `{$this->get_table_list_items()}` SET `position` = position - 1 WHERE".
					' list_id = %d AND `position` > %d AND `position` <= %d',
					array( $list_id, $from, $to )
				)
			);
		} elseif ( $to < $from ) {
			$wpdb->query(
				$wpdb->prepare(
				// phpcs:ignore
					"UPDATE `{$this->get_table_list_items()}` SET `position` = position + 1 WHERE".
					' list_id = %d AND `position` >= %d AND `position` < %d',
					array( $list_id, $to, $from )
				)
			);
		}
		$this->set_item_position( $list_id, $post_id, $to );

		do_action( 'mg_upc_item_move', $list_id, $post_id, $to );

		$this->cache->remove();
	}

	/**
	 * Renumber post from the list, for example on remove item on numbered list
	 *
	 * @param int $list_id
	 *
	 * @throws Exception
	 */
	public function renumber( $list_id ) {
		global $wpdb;
		/** @global User_Post_Collections $mg_upc Global plugin object. */
		global $mg_upc;

		$list_id = (int) $list_id;

		if ( ! $list_id ) {
			throw new Exception( 'Invalid list ID.' );
		}

		if ( ! $mg_upc->model->support( $list_id, 'sortable' ) ) {
			throw new Exception( 'Unable to sort not numbered list.' );
		}

		$items = $wpdb->get_results(
			$wpdb->prepare(
			// phpcs:ignore
				"SELECT `post_id`, `position` FROM `{$this->get_table_list_items()}` " .
				'WHERE `list_id` = %d ORDER BY position',
				$list_id
			)
		);
		if ( $items ) {
			foreach ( $items as $idx => $item ) {
				if ( $idx + 1 !== (int) $item->position ) {
					$wpdb->update(
						$this->get_table_list_items(),
						array( 'position' => $idx + 1 ),
						array(
							'list_id' => $list_id,
							'post_id' => $item->post_id,
						),
						array( '%d' ),
						array( '%d', '%d' )
					);
					$this->cache->remove();
				}
			}
		}
	}

	/**
	 * Set item position
	 *
	 * @param int $list_id
	 * @param int $post_id
	 * @param int $number
	 */
	private function set_item_position( $list_id, $post_id, $number ) {
		global $wpdb;

		$values = array( 'position' => (int) $number );
		$format = array( '%d' );

		$wpdb->update(
			$this->get_table_list_items(),
			$values,
			array(
				'list_id' => (int) $list_id,
				'post_id' => (int) $post_id,
			),
			$format,
			array( '%d', '%d' )
		);

		$this->cache->remove();
	}

	/**
	 * Get an list item
	 *
	 * @param int $list_id
	 * @param int $post_id
	 *
	 * @return object|null
	 *
	 * @throws Exception
	 */
	public function get_item( $list_id, $post_id ) {
		global $wpdb;
		$list_id = (int) $list_id;
		$post_id = (int) $post_id;
		if ( ! $post_id || ! $list_id ) {
			throw new Exception( 'item_exists: Invalid parameters' );
		}

		return $wpdb->get_row(
			$wpdb->prepare(
			// phpcs:ignore
				"SELECT * FROM `{$this->get_table_list_items()}` WHERE `list_id` = %d AND `post_id` = %d",
				array(
					$list_id,
					$post_id,
				)
			)
		);
	}

	/**
	 * Check if post is already in the list
	 *
	 * @param $list_id
	 * @param $post_id
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function item_exists( $list_id, $post_id ) {
		$item = $this->get_item( (int) $list_id, (int) $post_id );

		return ! empty( $item );
	}

	/**
	 * Add an item to a list
	 *
	 * @param int $list_id
	 * @param int $post_id
	 * @param string $description (Optional)
	 * @param int $quantity (Optional)
	 * @param null|string $addon_json
	 *
	 * @throws MG_UPC_Invalid_Field_Exception
	 * @throws MG_UPC_Item_Exist_Exception
	 * @throws MG_UPC_Item_Not_Found_Exception
	 * @throws Exception
	 */
	public function add_item( $list_id, $post_id, $description = '', $quantity = 0, $addon_json = null ) {
		global $wpdb;
		/** @global User_Post_Collections $mg_upc Global plugin object. */
		global $mg_upc;

		$post_id  = (int) $post_id;
		$list_id  = (int) $list_id;
		$quantity = (int) $quantity;
		if ( ! $post_id || ! $list_id || ! is_string( $description ) ) {
			throw new Exception( 'Invalid parameters' );
		}

		if ( $this->item_exists( $list_id, $post_id ) ) {
			throw new MG_UPC_Item_Exist_Exception( 'The content is already in the list.' );
		}

		$description = wp_strip_all_tags( $description );
		if ( mg_upc_strlen( $description ) > 400 ) {
			throw new MG_UPC_Invalid_Field_Exception( 'The description exceeds the maximum number of characters.' );
		}

		if ( ! is_string( $addon_json ) && null !== $addon_json ) {
			throw new Exception( 'Invalid parameters' );
		}
		if ( is_string( $addon_json ) ) {
			json_decode( $addon_json );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				throw new MG_UPC_Invalid_Field_Exception( 'Invalid addon_json field.' );
			}
		}

		if ( ! $this->helper->post_exist( $post_id ) ) {
			throw new MG_UPC_Item_Not_Found_Exception( 'Item not found.' );
		}

		$list = $mg_upc->model->find_one( (int) $list_id );
		if ( empty( $list ) ) {
			throw new MG_UPC_Item_Not_Found_Exception( 'List not found.' );
		}

		$position = 0;
		if ( $mg_upc->model->support( $list, 'sortable' ) ) {
			$position = $this->get_count( $list_id ) + 1;
		}

		$limit = $this->helper->get_max_list_items( $list->type );
		if ( $this->get_count( $list_id ) >= $limit ) {

			if ( ! $mg_upc->model->support( $list, 'max_items_rotate' ) ) {
				throw new Exception(
					sprintf(
					// translators: digit is the max number of items
						__( 'Sorry, you can not have more than %d items.', 'user-post-collections' ),
						$limit
					)
				);
			}

			// remove last listed item
			$type_object = $this->helper->get_list_type( $list->type, false );
			if ( ! $type_object ) {
				throw new MG_UPC_Item_Not_Found_Exception( 'List type not found.' );
			}

			$config = array(
				'list_id'        => $list_id,
				'orderby'        => $type_object->default_orderby ? $type_object->default_orderby : 'added',
				'order'          => $type_object->default_order ? $type_object->default_order : 'asc',
				'items_per_page' => 1,
			);
			//invert order for get last one
			$config['order'] = 'desc' === strtolower( $config['order'] ) ? 'asc' : 'desc';
			$last            = $this->items( $config );
			if (
				empty( $last ) ||
				empty( $last['items'] ) ||
				1 !== $this->remove_item( $list_id, $last['items'][0]->post_id )
			) {
				throw new Exception( 'DB error on rotate items' );
			}
		}

		$data = array(
			'post_id'     => $post_id,
			'list_id'     => $list_id,
			'position'    => $position,
			'description' => $description,
			'addon_json'  => $addon_json,
			'quantity'    => $quantity,
			'added'       => gmdate( 'Y-m-d H:i:s' ),
		);

		$format = array(
			'%d',
			'%d',
			'%d',
			'%s',
			null === $addon_json ? null : '%s',
			'%d',
			'%s',
		);

		$ok = $wpdb->insert( $this->get_table_list_items(), $data, $format );

		//update general counter if enabled
		if ( get_option( 'mg_upc_post_stats', 'on' ) === 'on' ) {
			$count = get_post_meta( $post_id, 'mg_upc_listed', true );
			if ( ! $count ) {
				$count = 0;
			}
			$count++;
			update_post_meta( $post_id, 'mg_upc_listed', $count );
		}
		//update general counter if enabled
		if ( $this->helper->get_list_type_option( $list->type, 'mg_upc_post_stats', 'off' ) === 'on' ) {
			$count = get_post_meta( $post_id, 'mg_upc_listed_' . $list->type, true );
			if ( ! $count ) {
				$count = 0;
			}
			$count++;
			update_post_meta( $post_id, 'mg_upc_listed_' . $list->type, $count );
		}

		$this->cache->remove();

		do_action( 'mg_upc_add_item', $data, $ok );
	}

	/**
	 * Update description for a item
	 *
	 * @param int    $list_id
	 * @param int    $post_id
	 * @param string $description (Optional)
	 *
	 * @throws MG_UPC_Invalid_Field_Exception
	 * @throws MG_UPC_Item_Not_Found_Exception
	 * @throws Exception
	 */
	public function update_item_description( $list_id, $post_id, $description = '' ) {
		if ( ! is_string( $description ) ) {
			throw new Exception( 'Invalid parameters' );
		}

		$description = wp_strip_all_tags( $description );
		if ( mg_upc_strlen( $description ) > 400 ) {
			throw new MG_UPC_Invalid_Field_Exception( 'The description exceeds the maximum number of characters.' );
		}

		$this->update_field( $list_id, $post_id, 'description', $description );
	}

	/**
	 * Update quantity for a item
	 *
	 * @param int $list_id
	 * @param int $post_id
	 * @param int $quantity
	 *
	 * @throws MG_UPC_Item_Not_Found_Exception
	 * @throws Exception
	 */
	public function update_item_quantity( $list_id, $post_id, $quantity ) {
		$this->update_field( $list_id, $post_id, 'quantity', (int) $quantity );
	}

	/**
	 * Update description for a item
	 *
	 * @param int    $list_id
	 * @param int    $post_id
	 * @param string $addon_json
	 *
	 * @throws MG_UPC_Invalid_Field_Exception
	 * @throws MG_UPC_Item_Not_Found_Exception
	 * @throws Exception
	 */
	public function update_item_addon_json( $list_id, $post_id, $addon_json ) {
		if ( ! is_string( $addon_json ) && null !== $addon_json ) {
			throw new Exception( 'Invalid parameters' );
		}

		if ( is_string( $addon_json ) ) {
			json_decode( $addon_json );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				throw new MG_UPC_Invalid_Field_Exception( 'Invalid addon_json field.' );
			}
		}

		$this->update_field( $list_id, $post_id, 'addon_json', $addon_json );
	}

	/**
	 * @param $list_id
	 * @param $post_id
	 * @param $field
	 * @param $value
	 *
	 * @throws MG_UPC_Item_Not_Found_Exception
	 * @throws Exception
	 */
	private function update_field( $list_id, $post_id, $field, $value ) {
		global $wpdb;
		$post_id = (int) $post_id;
		$list_id = (int) $list_id;
		if ( ! $post_id || ! $list_id ) {
			throw new Exception( 'Invalid parameters' );
		}

		if ( ! $this->item_exists( $list_id, $post_id ) ) {
			throw new MG_UPC_Item_Not_Found_Exception( 'Item not found.' );
		}

		if ( ! $this->helper->post_exist( $post_id ) ) {
			throw new MG_UPC_Item_Not_Found_Exception( 'Item not found.' );
		}

		$data = array(
			$field => $value,
		);
		if ( is_int( $value ) ) {
			$format = array( '%d' );
		} elseif ( null === $value ) {
			$format = array( null );
		} else {
			$format = array( '%s' );
		}

		$where        = array(
			'post_id' => $post_id,
			'list_id' => $list_id,
		);
		$where_format = array( '%d', '%d' );

		$ok = $wpdb->update( $this->get_table_list_items(), $data, $where, $format, $where_format );

		if ( $ok ) {
			$this->cache->remove();

			do_action( 'mg_upc_update_item_' . $field, $list_id, $post_id, $value );
		}
	}

	/**
	 * Get the item count of a list
	 *
	 * @param int|object $list
	 *
	 * @return bool|int
	 *
	 * @throws MG_UPC_Invalid_Field_Exception
	 */
	public function get_count( $list ) {
		/** @global User_Post_Collections $mg_upc Global plugin object. */
		global $mg_upc;

		if ( is_string( $list ) || is_int( $list ) ) {
			$list = $mg_upc->model->find_one( (int) $list );
		}

		if ( $list && is_object( $list ) ) {
			return (int) $list->count;
		}
		return false;
	}

}
