<?php
/** @noinspection SqlNoDataSourceInspection */
/** @noinspection SqlResolve */

class MG_List_Votes_Model {

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

		add_action(
			'mg_upc_vote',
			function( $list_id, $post_id ) {
				$user_id = get_current_user_id();
				$this->add_vote( $list_id, $post_id, $user_id );
			},
			10,
			2
		);
	}

	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new MG_List_Votes_Model();
		}

		return self::$instance;
	}

	/**
	 * Get list votes table name
	 *
	 * @return string
	 */
	public function get_table_list_votes() {
		global $wpdb;

		return $wpdb->prefix . 'upc_votes';
	}

	/**
	 * Delete all votes from the list, this will not update the vote counters!
	 *
	 * @param $list_id
	 *
	 * @return int|false The number of rows updated, or false on error.
	 */
	public function delete_all_votes_from_list( $list_id ) {
		global $wpdb;

		$this->cache->remove();

		return $wpdb->delete(
			$this->get_table_list_votes(),
			array( 'list_id' => $list_id ),
			array( '%d' )
		);
	}

	/**
	 * Delete all votes from user, this will not update the vote counters!
	 *
	 * @param $user_id
	 *
	 * @return int|false The number of rows updated, or false on error.
	 */
	public function delete_all_votes_from_user( $user_id ) {
		global $wpdb;

		$this->cache->remove();

		return $wpdb->delete(
			$this->get_table_list_votes(),
			array( 'user_id' => $user_id ),
			array( '%d' )
		);
	}

	/**
	 * Reassign votes to other user
	 *
	 * @param $user_id
	 * @param $reassign_id
	 *
	 * @return int|false The number of rows updated, or false on error.
	 */
	public function reassign_all_votes_from_user( $user_id, $reassign_id ) {
		global $wpdb;

		$this->cache->remove();

		return $wpdb->update(
			$this->get_table_list_votes(),
			array( 'user_id' => (int) $reassign_id ),
			array( 'user_id' => (int) $user_id ),
			array( '%d' ),
			array( '%d' )
		);
	}

	/**
	 * List User Votes
	 *
	 * @param bool   $filters
	 * @param int    $page            Set to 0 for only run count query
	 * @param int    $votes_per_page
	 * @param string $orderby
	 * @param string $order
	 *
	 * @return array
	 *
	 * @throws MG_UPC_Invalid_Field_Exception
	 */
	public function votes( $filters = false, $page = 1, $votes_per_page = 50, $orderby = 'added', $order = 'asc' ) {
		global $wpdb;

		if ( ! in_array( $orderby, array( 'list_id', 'user_id', 'post_id', 'added' ), true ) ) {
			$orderby = false;
		}
		// standardize inputs

		$votes_per_page = absint( $votes_per_page );

		$page = absint( $page );

		$order = strtolower( $order );

		$int_filters = array( 'list_id', 'post_id', 'user_id' );
		foreach ( $int_filters as $prop ) {
			if ( ! empty( $filters[ $prop ] ) ) {
				$filters[ $prop ] = (int) $filters[ $prop ];
				if ( ! $filters[ $prop ] ) {
					throw new MG_UPC_Invalid_Field_Exception( 'Invalid filter value.' );
				}
			}
		}

		// check cache
		$cache_key = wp_json_encode( $filters ) . '-' . $page . '-' . $votes_per_page . '-' . $orderby . '-' . $order;
		$cache_key = md5( $cache_key );

		$return = $this->cache->get( 'items', $cache_key );
		if ( null === $return ) {

			$select_count = "SELECT COUNT(*) FROM `{$this->get_table_list_votes()}` ";
			$select       = 'SELECT `list_id`, `post_id`, `user_id`, `ip`, `added` ' .
							"FROM `{$this->get_table_list_votes()}` ";
			$sql          = '';

			$where   = array();
			$prepare = array();

			foreach ( $int_filters as $prop ) {
				if ( ! empty( $filters[ $prop ] ) ) {
					$where[]   = '`' . $prop . '` = %d';
					$prepare[] = $filters[ $prop ];
				}
			}

			if ( ! empty( $filters['ip'] ) ) {
				$where[]   = '`ip` = %s';
				$prepare[] = $filters['ip'];
			}

			if ( ! empty( $where ) ) {
				$sql .= ' WHERE ' . implode( ' AND ', $where );
			}

			if ( $votes_per_page > 1 ) { //for find one not run count query
				$total = (int) $wpdb->get_var(
					$wpdb->prepare(
					// phpcs:ignore
						$select_count . $sql,
						$prepare
					)
				);
				if ( 0 === $page ) {
					return array(
						'votes'       => array(),
						'total'       => $total,
						'total_pages' => $votes_per_page > 0 ? ceil( $total / $votes_per_page ) : 1,
						'current'     => 0,
					);
				}
			}

			if ( ! empty( $orderby ) ) {
				$sql .= ' ORDER BY ' . $orderby;
				if ( isset( $order ) && 'desc' === $order ) {
					$sql .= ' DESC';
				} else {
					$sql .= ' ASC';
				}
			}

			if ( $votes_per_page > 0 ) {
				if ( $page > 1 ) {
					$offset = $votes_per_page * ( $page - 1 );
					$offset = max( 0, $offset );

					$sql      .= ' LIMIT %d, %d';
					$prepare[] = (int) $offset;
					$prepare[] = (int) $votes_per_page;
				} else {
					$sql      .= ' LIMIT %d';
					$prepare[] = (int) $votes_per_page;
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
				'votes'       => $results,
				'total'       => $total,
				'total_pages' => $votes_per_page > 0 ? ceil( $total / $votes_per_page ) : 1,
				'current'     => $page,
			);

			$this->cache->add( 'votes', $cache_key, $return );

		}
		return $return;
	}

	/**
	 * Remove votes using filters
	 *
	 * @param array $filters Valid keys: list_id, post_id, user_id, ip
	 *
	 * @return bool
	 *
	 * @throws Exception
	 */
	public function remove_votes( $filters ) {
		global $wpdb;

		$where   = array();
		$prepare = array();

		$int_filters = array( 'list_id', 'post_id', 'user_id' );
		foreach ( $int_filters as $prop ) {
			if ( ! empty( $filters[ $prop ] ) ) {
				$prop_id = (int) $filters[ $prop ];
				if ( ! $prop_id ) {
					return false;
				}
				$where[ $prop ] = $prop_id;
				$prepare        = '%d';
			}
		}

		if ( ! empty( $filters['ip'] ) ) {
			$where['ip'] = $filters['ip'];
			$prepare     = '%s';
		}

		if ( empty( $where ) ) {
			throw new Exception( 'Invalid filters' );
		}

		$deleted_count = $wpdb->delete(
			$this->get_table_list_votes(),
			$where,
			$prepare
		);

		$this->cache->remove();

		do_action( 'mg_upc_remove_votes', $filters, $deleted_count );

		return $deleted_count;
	}

	/**
	 * Get the vote of an user for a list
	 *
	 * @param int $list_id
	 * @param int $user_id
	 *
	 * @return object[]|null
	 *
	 * @throws Exception
	 */
	public function get_votes( $list_id, $user_id ) {
		global $wpdb;
		$list_id = (int) $list_id;
		$user_id = (int) $user_id;

		if ( ! $user_id || ! $list_id ) {
			throw new Exception( 'Votes: Invalid parameters' );
		}

		return $wpdb->get_results(
			$wpdb->prepare(
			// phpcs:ignore
				"SELECT * FROM `{$this->get_table_list_votes()}` WHERE `list_id` = %d AND `user_id` = %d",
				array(
					$list_id,
					$user_id,
				)
			)
		);
	}

	/**
	 * Count votes of an user for a list
	 *
	 * @param int          $list_id The list ID
	 * @param int          $user_id (Optional) The user ID
	 * @param null|string  $ip
	 *
	 * @return int
	 *
	 * @throws Exception
	 */
	public function count_votes( $list_id, $user_id = 0, $ip = null ) {
		global $wpdb;
		$list_id = (int) $list_id;

		if ( ! $list_id ) {
			throw new Exception( 'Votes: Invalid list ID parameter' );
		}

		if ( 0 === $user_id ) {
			if ( null !== $ip ) {
				return (int) $wpdb->get_var(
					$wpdb->prepare(
						// phpcs:ignore
						"SELECT COUNT(*) FROM `{$this->get_table_list_votes()}` WHERE `list_id` = %d AND `ip` = %s",
						array(
							$list_id,
							$ip,
						)
					)
				);
			}
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore
					"SELECT COUNT(*) FROM `{$this->get_table_list_votes()}` WHERE `list_id` = %d",
					array( $list_id )
				)
			);
		}

		$user_id = (int) $user_id;
		if ( ! $user_id ) {
			throw new Exception( 'Votes: Invalid user ID parameter' );
		}

		if ( null !== $ip ) {
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore
					"SELECT COUNT(*) FROM `{$this->get_table_list_votes()}` WHERE `list_id` = %d AND `user_id` = %d AND `ip` = %s",
					array(
						$list_id,
						$user_id,
						$ip,
					)
				)
			);
		}

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore
				"SELECT COUNT(*) FROM `{$this->get_table_list_votes()}` WHERE `list_id` = %d AND `user_id` = %d",
				array(
					$list_id,
					$user_id,
				)
			)
		);
	}

	/**
	 * Add a vote
	 *
	 * @param int $list_id
	 * @param int $post_id
	 * @param int $user_id
	 *
	 * @throws MG_UPC_Invalid_Field_Exception
	 * @throws MG_UPC_Item_Not_Found_Exception
	 * @throws Exception
	 */
	public function add_vote( $list_id, $post_id, $user_id ) {
		global $wpdb;
		/** @global User_Post_Collections $mg_upc Global plugin object. */
		global $mg_upc;

		$list_id = (int) $list_id;
		$post_id = (int) $post_id;
		$user_id = (int) $user_id;
		if ( ( 0 !== $user_id && ! $user_id ) || ! $list_id || ! $post_id ) {
			throw new Exception( 'Invalid parameters' );
		}

		if ( 0 !== $user_id && ! $this->helper->user_id_exists( $user_id ) ) {
			throw new MG_UPC_Item_Not_Found_Exception( 'User not found.' );
		}

		if ( ! $mg_upc->model->support( $list_id, 'vote' ) ) {
			throw new MG_UPC_Invalid_Field_Exception( 'This list is not for vote.' );
		}

		$data = array(
			'list_id' => $list_id,
			'user_id' => $user_id,
			'post_id' => $post_id,
			'ip'      => $this->get_ip_to_storage(),
			'added'   => gmdate( 'Y-m-d H:i:s' ),
		);

		$format = array( '%d', '%d', '%d', '%s', '%s' );

		$ok = $wpdb->insert( $this->get_table_list_votes(), $data, $format );

		$this->cache->remove();

		do_action( 'mg_upc_add_vote', $data, $ok );
	}

	public function get_ip_to_storage() {
		$ip = '';
		if (
			get_option( 'mg_upc_store_vote_ip', 'on' ) === 'on' &&
			! empty( $_SERVER['REMOTE_ADDR'] ) &&
			is_string( $_SERVER['REMOTE_ADDR'] ) &&
			rest_is_ip_address( $_SERVER['REMOTE_ADDR'] ) !== false
		) {
			$ip = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
		}
		$ip = apply_filters( 'mg_upc_vote_ip', $ip );

		if ( ! empty( $ip ) ) {
			if ( get_option( 'mg_upc_store_vote_anonymize_ip', 'on' ) === 'on' ) {
				$ip = wp_privacy_anonymize_ip( $ip );
			}
		}
		return $ip;
	}

	/**
	 * Remove old votes for a list type
	 *
	 * @param $list_type_name
	 * @param $ttl_vote
	 *
	 * @return bool|int
	 */
	public function clear_votes( $list_type_name, $ttl_vote ) {
		global $wpdb;
		global $mg_upc;

		return $wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore
				"DELETE `{$this->get_table_list_votes()}` FROM `{$this->get_table_list_votes()}` INNER JOIN `{$mg_upc->model->get_table_list()}` " .
				'WHERE `ID` = `list_id` AND `type` = %s AND `added` < %s',
				$list_type_name,
				gmdate( 'Y-m-d H:i:s', time() - ( 3600 * 24 * $ttl_vote ) )
			)
		);
	}

}
