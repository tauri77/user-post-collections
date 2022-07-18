<?php
/** @noinspection SqlNoDataSourceInspection */
/** @noinspection SqlResolve */

class MG_List_Model {

	private static $instance;

	/**
	 * @var MG_UPC_Cache for not repeat sql queries
	 */
	protected $cache;

	/**
	 * @var MG_UPC_Helper
	 */
	private $helper;

	/**
	 * @var MG_List_Votes_Model
	 */
	public $votes;

	/**
	 * @var MG_List_Items_Model
	 */
	public $items;

	private function __construct() {

		$this->helper = MG_UPC_Helper::get_instance();
		$this->votes  = MG_List_Votes_Model::get_instance();
		$this->items  = MG_List_Items_Model::get_instance();
		$this->cache  = new MG_UPC_Cache();

		add_action( 'mg_upc_vote', array( $this, 'hook_vote' ), 10, 1 );
		add_action( 'mg_upc_remove_item', array( $this, 'hook_remove_item' ), 10, 3 );
		add_action( 'mg_upc_add_item', array( $this, 'hook_add_item' ), 10, 2 );
		add_action( 'mg_upc_item_move', array( $this, 'set_modified' ), 10, 1 );
		add_action( 'mg_upc_update_item_description', array( $this, 'set_modified' ), 10, 1 );

	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function get_table_list() {
		global $wpdb;

		return $wpdb->prefix . 'upc_lists';
	}

	/**
	 * Get one user post collections
	 *
	 * @param array|int|string $args If int use as ID, if string use as slug, else array filters to find method
	 *
	 * @return object|null
	 *
	 * @throws MG_UPC_Invalid_Field_Exception
	 */
	public function find_one( $args ) {
		if ( is_int( $args ) ) {
			$args = array( 'ID' => $args );
		} elseif ( is_string( $args ) ) {
			$args = array( 'slug' => $args );
		}
		$args['limit'] = 1;

		$ret = $this->find( $args );
		if ( ! empty( $ret['results'] ) ) {
			return $ret['results'][0];
		}

		return null;
	}

	/**
	 * Find from type and author
	 *
	 * @param string|array $type
	 * @param int          $user_id
	 *
	 * @return object|null
	 * @throws MG_UPC_Invalid_Field_Exception
	 */
	public function find_always_exist( $type, $user_id ) {
		$args = array(
			'type'   => $type,
			'author' => $user_id,
			'limit'  => 1,
		);
		$ret  = $this->find( $args );
		if ( ! empty( $ret['results'] ) ) {
			return $ret['results'][0];
		}

		return null;
	}

	/**
	 * Return query ORDER for sort sticked list
	 *
	 * @return bool|string
	 */
	private function get_pins_query() {
		$types = $this->helper->get_stick_list_types();

		if ( empty( $types ) ) {
			return false;
		}

		$number    = 1;
		$sql_order = '( CASE';
		foreach ( $types as $type ) {
			$sql_order .= " WHEN type = '" . sanitize_title( $type ) . "' THEN '" . $number . "'";
			$number ++;
		}

		return $sql_order . " else '" . $number . "' END) ASC";
	}

	/**
	 * List collections
	 *
	 * @param array $args Filters and configutations
	 *
	 * @return array
	 *
	 * @throws MG_UPC_Invalid_Field_Exception
	 */
	public function find( $args ) {
		global $wpdb;

		$defaults = array(
			'limit' => 20,
			'page'  => 1,
		);
		$args     = array_merge( $defaults, $args );

		$cache_key = md5( wp_json_encode( $args ) );
		$return    = $this->cache->get( 'find', $cache_key );
		if ( null !== $return ) {
			return $return;
		}

		$where   = array();
		$prepare = array();

		//compare only support with array=false
		$filters = array(
			'ID'              => array(
				'type'  => 'int',
				'array' => true,
			),
			'slug'            => array(
				'type'  => 'string',
				'array' => true,
			),
			'author'          => array(
				'type'  => 'int',
				'array' => true,
			),
			'type'            => array(
				'type'  => 'string',
				'array' => true,
				'valid' => $this->valid_types( true ),
				'any'   => 'any',
			),
			'status'          => array(
				'type'  => 'string',
				'array' => true,
				'valid' => $this->valid_status(),
				'any'   => 'any',
			),
			'after'           => array(
				'type'      => 'datetime',
				'array'     => false,
				'compare'   => '>',
				'db_column' => 'created',
			),
			'before'          => array(
				'type'      => 'datetime',
				'array'     => false,
				'compare'   => '<',
				'db_column' => 'created',
			),
			'modified_after'  => array(
				'type'      => 'datetime',
				'array'     => false,
				'compare'   => '>',
				'db_column' => 'modified',
			),
			'modified_before' => array(
				'type'      => 'datetime',
				'array'     => false,
				'compare'   => '<',
				'db_column' => 'modified',
			),
		);

		//int filters
		foreach ( $filters as $prop => $filter ) {

			if ( ! empty( $args[ $prop ] ) ) {
				$db_column    = isset( $filter['db_column'] ) ? $filter['db_column'] : $prop;
				$compare      = isset( $filter['compare'] ) ? $filter['compare'] : '=';
				$single_value = null;

				if ( $filter['array'] ) {

					if ( is_string( $args[ $prop ] ) ) {
						$args[ $prop ] = explode( ',', $args[ $prop ] );
					}

					if ( is_scalar( $args[ $prop ] ) ) {
						$args[ $prop ] = array( $args[ $prop ] );
					}

					if ( ! is_array( $args[ $prop ] ) ) {
						throw new MG_UPC_Invalid_Field_Exception(
							'Invalid field ' . $prop . '.',
							0,
							null,
							$prop
						);
					}

					if ( ! empty( $filter['any'] ) && in_array( $filter['any'], $args[ $prop ], true ) ) {
						continue;
					}

					if ( count( $args[ $prop ] ) > 1 ) {

						$where_values = array();
						foreach ( $args[ $prop ] as $value ) {
							if ( 'int' === $filter['type'] ) {
								if ( ! empty( $filter['valid'] ) && ! in_array( (int) $value, $filter['valid'], true ) ) {
									throw new MG_UPC_Invalid_Field_Exception(
										'Invalid field ' . $prop . '.',
										0,
										null,
										$prop
									);
								}
								$where_values[] = '%d';
								$prepare[]      = (int) $value;
							} elseif ( 'string' === $filter['type'] ) {
								if ( ! empty( $filter['valid'] ) && ! in_array( $value, $filter['valid'], true ) ) {
									throw new MG_UPC_Invalid_Field_Exception(
										'Invalid field ' . $prop . '.',
										0,
										null,
										$prop
									);
								}
								$where_values[] = '%s';
								$prepare[]      = $value;
							} elseif ( 'datetime' === $filter['type'] ) {
								$datetime = strtotime( $value );
								if ( false === $datetime ) {
									throw new MG_UPC_Invalid_Field_Exception(
										'Invalid field ' . $prop . '.',
										0,
										null,
										$prop
									);
								}
								$where_values[] = '%s';
								$prepare[]      = gmdate( 'Y-m-d H:i:s', $datetime );
							}
						}

						$where[] = '( `' . $db_column . '` IN (' . implode( ',', $where_values ) . '))';

						continue; //end count( $args[ $prop ] ) > 1
					} elseif ( 1 === count( $args[ $prop ] ) ) {
						$args[ $prop ] = array_values( $args[ $prop ] );
						$single_value  = $args[ $prop ][0];
					}
					//end array=true
				} else {
					$single_value = $args[ $prop ];
				}

				//single value
				if ( isset( $single_value ) ) {
					if ( 'int' === $filter['type'] ) {
						$where[]   = '`' . $db_column . '` ' . $compare . ' %d';
						$prepare[] = (int) $single_value;
					} elseif ( 'string' === $filter['type'] ) {
						$where[]   = '`' . $db_column . '` ' . $compare . ' %s';
						$prepare[] = $single_value;
					} elseif ( 'datetime' === $filter['type'] ) {
						$datetime = strtotime( $single_value );
						if ( false === $datetime ) {
							throw new MG_UPC_Invalid_Field_Exception(
								'Invalid field ' . $prop . '.',
								0,
								null,
								$prop
							);
						}
						$where[]   = '`' . $db_column . '` ' . $compare . ' %s';
						$prepare[] = gmdate( 'Y-m-d H:i:s', $datetime );
					}
				}
			}
		}

		//search
		if ( isset( $args['search'] ) ) {
			$where[]   = '(`title` LIKE %s OR `slug` LIKE %s)';
			$prepare[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$prepare[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
		}

		$select_count = 'SELECT COUNT(*) FROM `' . $this->get_table_list() . '` ';
		$select       = 'SELECT * FROM `' . $this->get_table_list() . '` ';
		$sql          = '';

		if ( ! empty( $where ) ) {
			$sql .= ' WHERE ' . implode( ' AND ', $where );
		}

		$args['page'] = max( intval( $args['page'] ), 1 );

		if ( $args['limit'] > 1 ) { //for find one not run count query and not set order
			$sql_pin_query = '';
			if ( false !== $args['pined'] ) {
				$sql_pin_query = $this->get_pins_query();
			}
			if (
				isset( $args['orderby'] ) &&
				in_array(
					$args['orderby'],
					array(
						'ID',
						'views',
						'vote_counter',
						'count',
						'created',
						'modified',
					),
					true
				)
			) {
				$sql_pin_query = $sql_pin_query ? $sql_pin_query . ', ' : '';
				$sql          .= ' ORDER BY ' . $sql_pin_query . $args['orderby'];
				if ( isset( $args['order'] ) && 'desc' === $args['order'] ) {
					$sql .= ' DESC';
				} else {
					$sql .= ' ASC';
				}
			} elseif ( false !== $sql_pin_query ) {
				$sql .= ' ORDER BY ' . $sql_pin_query;
			}

			$total = (int) $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore
					$select_count . $sql,
					$prepare
				)
			);
		}

		if ( $args['limit'] > 0 ) {
			if ( isset( $args['offset'] ) ) {
				$sql      .= ' LIMIT %d, %d';
				$prepare[] = max( 0, (int) $args['offset'] );
				$prepare[] = $args['limit'];
			} elseif ( $args['page'] > 1 ) {
				$offset = $args['limit'] * ( $args['page'] - 1 );

				$sql      .= ' LIMIT %d, %d';
				$prepare[] = $offset;
				$prepare[] = $args['limit'];
			} else {
				$sql      .= ' LIMIT %d';
				$prepare[] = $args['limit'];
			}
		}
		$results = $wpdb->get_results(
			// phpcs:ignore
			$wpdb->prepare( $select . $sql, $prepare )
		);

		if ( ! isset( $total ) ) {
			$total = count( $results );
		}

		$return = array(
			'results'     => $results,
			'total'       => $total,
			'total_pages' => $args['limit'] > 0 ? ceil( $total / $args['limit'] ) : 1,
			'current'     => $args['page'],
		);

		$this->cache->add( 'find', $cache_key, $return );

		return $return;
	}

	/**
	 * Create a post collection
	 *
	 * @param array $args
	 *
	 * @return bool|int The id of created list, false on failure.
	 *
	 * @throws MG_UPC_Invalid_Field_Exception
	 * @throws MG_UPC_Required_Field_Exception
	 * @throws Exception
	 */
	public function create( $args ) {
		global $wpdb;

		if ( ! isset( $args['type'] ) ) {
			throw new MG_UPC_Required_Field_Exception( 'Required type.', 0, null, 'type' );
		}

		$data        = array();
		$data_format = array();

		$list_type = $this->helper->get_list_type( $args['type'], false );

		if ( false === $list_type ) {
			throw new MG_UPC_Invalid_Field_Exception( 'Invalid type.', 0, null, 'type' );
		}
		$data['type']  = $args['type'];
		$data_format[] = '%s';

		//Clear fields for list type
		if ( empty( $args['title'] ) || true !== $list_type['editable_title'] ) {
			$args['title'] = false !== $list_type['default_title'] ? $list_type['default_title'] : $list_type['label'];
		}
		if ( ! isset( $args['status'] ) ) {
			$args['status'] = $list_type['default_status'];
		}
		if ( isset( $args['content'] ) && true !== $list_type['editable_content'] ) {
			$args['content'] = false !== $list_type['default_content'] ? $list_type['default_content'] : '';
		}

		if ( ! in_array( $args['status'], $list_type['available_statuses'], true ) ) {
			throw new MG_UPC_Invalid_Field_Exception( 'Invalid status.', 0, null, 'status' );
		}
		$data['status'] = $args['status'];
		$data_format[]  = '%s';

		if ( ! isset( $args['author'] ) ) {
			$current_user = wp_get_current_user();
			if ( null !== $current_user ) {
				$args['author'] = $current_user->ID;
			} else {
				throw new MG_UPC_Required_Field_Exception( 'No author set.', 0, null, 'author' );
			}
		}
		$user = get_user_by( 'id', $args['author'] );
		if ( false === $user->ID ) {
			throw new MG_UPC_Invalid_Field_Exception( 'Invalid author.', 0, null, 'author' );
		}
		$data['author'] = (int) $args['author'];
		$data_format[]  = '%d';

		if ( empty( $args['title'] ) ) {
			throw new MG_UPC_Required_Field_Exception( 'The title is required.', 0, null, 'title' );
		}
		$data['title'] = $args['title'];
		$data_format[] = '%s';

		$data['slug']  = $this->find_slug( $args['title'], $user->user_login );
		$data_format[] = '%s';

		$data['content'] = ! empty( $args['content'] ) && is_string( $args['content'] ) ? $args['content'] : '';
		$data_format[]   = '%s';

		$data['created'] = gmdate( 'Y-m-d H:i:s' );
		$data_format[]   = '%s';

		$data['modified'] = $data['created'];
		$data_format[]    = '%s';

		$data['count'] = 0;
		$data_format[] = '%d';

		$data['vote_counter'] = 0;
		$data_format[]        = '%d';

		$wpdb->insert( $this->get_table_list(), $data, $data_format );

		$this->cache->remove();

		return 0 !== $wpdb->insert_id ? $wpdb->insert_id : false;
	}

	/**
	 * Slug creator
	 *
	 * @param string $title       Title list
	 * @param string $user_login  User login
	 *
	 * @return string
	 * @throws Exception
	 */
	private function find_slug( $title, $user_login ) {
		$slug = sanitize_title( $title . ' by ' . $user_login );

		$slug_exist = $this->slug_exist( $slug );
		$count      = 2;
		while ( $slug_exist && $count < 100 ) {
			$slug       = sanitize_title( $title . ' by ' . $user_login . ' ' . $count );
			$slug_exist = $this->slug_exist( $slug );
			$count++;
		}
		if ( $slug_exist ) {
			throw new Exception( 'The title exists too many times' );
		}

		return $slug;
	}

	/**
	 * Update exists collection
	 *
	 * @param array $args Require ID key. And add the extra fields to update.
	 *
	 * @return int        The number of rows updated
	 *
	 * @throws MG_UPC_Invalid_Field_Exception
	 * @throws Exception
	 */
	public function update( $args ) {
		global $wpdb;

		$actual = $this->find_one( (int) $args['ID'] );
		if ( empty( $actual ) ) {
			throw new Exception( 'Invalid id.' );
		}

		$where        = array( 'ID' => $args['ID'] );
		$where_format = array( '%d' );

		$data        = array();
		$data_format = array();

		$list_type = $this->helper->get_list_type( $actual->type, true );

		if ( false === $list_type ) {
			//this can trow when an list type was removed(not disabled). Maybe use "simple" config?
			throw new MG_UPC_Invalid_Field_Exception( 'Invalid type.', 0, null, 'type' );
		}

		//Clear fields for list type
		if ( ! empty( $args['title'] ) && true !== $list_type['editable_title'] ) {
			unset( $args['title'] ); // or set to default_title?
		}
		if ( isset( $args['content'] ) && true !== $list_type['editable_content'] ) {
			unset( $args['content'] ); // or set to default_content
		}

		if ( isset( $args['status'] ) ) {
			if ( ! in_array( $args['status'], $list_type['available_statuses'], true ) ) {
				throw new MG_UPC_Invalid_Field_Exception( 'Invalid status.', 0, null, 'status' );
			}
			$data_format[]  = '%s';
			$data['status'] = $args['status'];
		}

		if ( ! empty( $args['author'] ) ) {
			$user = get_user_by( 'id', $args['author'] );
			if ( false === $user->ID ) {
				throw new MG_UPC_Invalid_Field_Exception( 'Invalid author.', 0, null, 'author' );
			}
			$data_format[]  = '%d';
			$data['author'] = $args['author'];
		}

		if ( ! empty( $args['title'] ) ) {
			$data_format[] = '%s';
			$data['title'] = $args['title'];
		}

		if ( isset( $args['content'] ) && is_string( $args['content'] ) ) {
			$data_format[]   = '%s';
			$data['content'] = $args['content'];
		}

		if (
			! empty( $args['slug'] ) &&
			$args['slug'] !== $actual['slug'] &&
			! $this->slug_exist( $args['slug'] )
		) {
			$data_format[] = '%s';
			$data['slug']  = $args['slug'];
		}

		$data_format[]    = '%s';
		$data['modified'] = gmdate( 'Y-m-d H:i:s' );

		if ( ! empty( $args['views'] ) ) {
			$data_format[] = '%d';
			$data['views'] = $args['views'];
		}

		$updated = $wpdb->update( $this->get_table_list(), $data, $where, $data_format, $where_format );

		$this->cache->remove();

		if ( false === $updated ) {
			throw new Exception( 'Unable to update database' );
		}

		return $updated;
	}

	/**
	 * Check if slug exists
	 *
	 * @param $slug
	 * @param bool $exclude_id
	 *
	 * @return bool
	 */
	public function slug_exist( $slug, $exclude_id = false ) {
		global $wpdb;

		if ( false === $exclude_id ) {
			$slug_exist = $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore
					"SELECT EXISTS (SELECT 1 FROM `{$this->get_table_list()}` WHERE `slug` = %s)",
					$slug
				)
			);
		} else {
			$slug_exist = $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore
					"SELECT EXISTS (SELECT 1 FROM `{$this->get_table_list()}` WHERE `slug` = %s AND `id` IS NOT %d )",
					$slug,
					(int) $exclude_id
				)
			);
		}

		return ! ! $slug_exist;
	}

	/**
	 * Get valid list types
	 *
	 * @param bool $include_disabled If true include disabled list types
	 *
	 * @return string[]
	 */
	public function valid_types( $include_disabled = false ) {
		$valid_types = $this->helper->get_list_types( $include_disabled );

		return array_keys( $valid_types );
	}

	/**
	 * Change the author of all lists and votes of $user_id to $reassign_id
	 *
	 * @param int   $user_id      From user
	 * @param int   $reassign_id  To user
	 * @param array $list_types   Only this list types
	 */
	public function reassign_all_from_user( $user_id, $reassign_id, $list_types ) {
		global $wpdb;

		foreach ( $list_types as $list_type ) {
			$wpdb->update(
				$this->get_table_list(),
				array( 'author' => (int) $reassign_id ),
				array(
					'author' => (int) $user_id,
					'type'   => $list_type,
				),
				array( '%d' ),
				array( '%d', '%s' )
			);
		}

		$this->votes->reassign_all_votes_from_user( $user_id, $reassign_id );
	}

	/**
	 * Delete all lists and votes of $user_id
	 *
	 * @param int   $user_id      From user
	 * @param array $list_types   Only this list types
	 */
	public function deleted_all_from_user( $user_id, $list_types ) {
		if ( ! empty( $list_types ) ) {
			//Delete lists
			$this->deleted_all_list_from_user( $user_id, $list_types );
		}
		//Delete votes, this dont reverse the vote counters
		$this->votes->delete_all_votes_from_user( $user_id );
	}

	/**
	 * Delete all lists of $user_id
	 *
	 * @param int   $user_id    From user
	 * @param array $list_types Only this list types
	 *
	 * @return array The array with deleted list (ID as key, db delete result as value)
	 */
	public function deleted_all_list_from_user( $user_id, $list_types ) {
		global $wpdb;

		$ret = array();
		foreach ( $list_types as $list_type ) {
			$lists = $wpdb->get_results(
				$wpdb->prepare(
				// phpcs:ignore
					"SELECT * FROM `{$this->get_table_list()}` WHERE `author` = %d AND `type` = %s",
					(int) $user_id,
					$list_type
				)
			);
			foreach ( $lists as $result ) {
				$ret[ $result->ID ] = $this->delete( $result->ID );
			}
		}

		return $ret;
	}


	/**
	 * Delete list from database
	 *
	 * @param int $list_id
	 *
	 * @return bool|int The number of rows updated, or false on error.
	 */
	public function delete( $list_id ) {
		global $wpdb;

		$this->votes->delete_all_votes_from_list( $list_id );
		MG_List_Items_Model::get_instance()->delete_all_posts_from_list( $list_id );

		$this->cache->remove();

		return $wpdb->delete(
			$this->get_table_list(),
			array( 'ID' => $list_id ),
			array( '%d' )
		);
	}

	/**
	 * Set modified datetime to NOW
	 *
	 * @param int $list_id
	 *
	 * @return bool|int The number of rows updated, or false on error.
	 */
	public function set_modified( $list_id ) {
		global $wpdb;

		$this->cache->remove();

		return $wpdb->update(
			$this->get_table_list(),
			array( 'modified' => gmdate( 'Y-m-d H:i:s' ) ),
			array( 'ID' => (int) $list_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Count the votes of a user in a specific list
	 *
	 * @param int $list_id
	 * @param int $user_id
	 *
	 * @return int
	 *
	 * @throws Exception
	 */
	public function user_count_votes( $list_id, $user_id ) {
		return $this->votes->count_votes( $list_id, $user_id );
	}

	/**
	 * Count the votes of an IP in a specific list
	 *
	 * @param int $list_id
	 *
	 * @return int
	 *
	 * @throws Exception
	 */
	public function ip_count_votes( $list_id ) {
		return $this->votes->count_votes( $list_id, 0, $this->votes->get_ip_to_storage() );
	}

	/**
	 * Get valid status strings
	 *
	 * @param string|bool $type If false or not set return all valid status, else only statuses for the specified list type
	 *
	 * @return string[]
	 */
	public function valid_status( $type = false ) {
		if ( false === $type ) {
			$all_status = array();
			$list_types = $this->helper->get_list_types( false );
			foreach ( $list_types as $list_type ) {
				$all_status = array_merge( $all_status, $list_type['available_statuses'] );
			}
			return array_unique( $all_status );
		}

		$list_type = $this->helper->get_list_type( $type, true );

		return $list_type ? $list_type['available_statuses'] : array();
	}

	/**
	 * Check if a list support an specific feature
	 *
	 * @param int|object $list     List ID or list object
	 * @param string     $feature  Feature to check
	 *
	 * @return mixed|void
	 *
	 * @throws MG_UPC_Invalid_Field_Exception
	 */
	public function support( $list, $feature ) {

		$has_feature = false;

		if ( is_string( $list ) || is_int( $list ) ) {
			$list = $this->find_one( (int) $list );
		}

		if ( $list && is_object( $list ) ) {
			$has_feature = $this->helper->list_type_support( $list->type, $feature, true );
		}

		return apply_filters( 'mg_upc_list_support', $has_feature, $list, $feature );
	}

	/**
	 * Update items counter on item removed
	 *
	 * @param array $data
	 * @param bool  $ok
	 */
	public function hook_add_item( $data, $ok ) {
		global $wpdb;
		if ( $ok ) {
			$wpdb->query(
				$wpdb->prepare(
				// phpcs:ignore
					"UPDATE `{$this->get_table_list()}` SET `count` = count + 1 WHERE `ID` = %d",
					$data['list_id']
				)
			);
			$this->set_modified( $data['list_id'] );
		}
	}

	/**
	 * Update items counter on item added
	 *
	 * @param int $list_id
	 * @param int $post_id
	 * @param int $deleted_count
	 *
	 * @throws MG_UPC_Invalid_Field_Exception
	 */
	public function hook_remove_item( $list_id, $post_id, $deleted_count ) {
		global $wpdb;
		if ( is_int( $deleted_count ) && $deleted_count > 0 ) {
			$wpdb->query(
				$wpdb->prepare(
					// phpcs:ignore
					"UPDATE `{$this->get_table_list()}` SET `count` = count - %d WHERE `ID` = %d",
					$deleted_count,
					$list_id
				)
			);
		}
		if ( $this->support( $list_id, 'vote' ) ) {
			$votes_count = $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore
					"SELECT SUM(votes) FROM `{$this->items->get_table_list_items()}` WHERE `list_id` = %d",
					$list_id
				)
			);
			if ( is_int( $votes_count ) || is_numeric( $votes_count ) ) {
				$wpdb->query(
					$wpdb->prepare(
						// phpcs:ignore
						"UPDATE `{$this->get_table_list()}` SET `vote_counter` = %d WHERE `ID` = %d",
						absint( $votes_count ),
						$list_id
					)
				);
			}
		}

		$this->set_modified( $list_id );
	}

	/**
	 * Update vote counter on vote added
	 *
	 * @param int $list_id
	 */
	public function hook_vote( $list_id ) {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore
				"UPDATE `{$this->get_table_list()}` SET `vote_counter` = vote_counter + 1 WHERE `ID` = %d",
				$list_id
			)
		);

		$this->cache->remove();
	}

	/**
	 * Maintenance DB
	 *
	 * @return array
	 */
	public function maintenance() {
		$summary = array( 'votes' => 0 );

		$valid_types = $this->helper->get_list_types( true );
		foreach ( $valid_types as $list_type ) {
			if ( $list_type->support( 'vote' ) ) {
				$ttl_vote = (int) apply_filters(
					'mg_upc_ttl_votes_' . $list_type->name,
					$this->helper->get_list_type_option( $list_type->name, 'ttl_votes', 365 )
				);

				$summary['votes'] += (int) $this->votes->clear_votes( $list_type->name, $ttl_vote );
			}
		}

		return $summary;
	}

}
