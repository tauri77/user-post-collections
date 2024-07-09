<?php

/*
 * An object for MG_UPC_Query loop
 */
class MG_UPC_List implements ArrayAccess {
	/**
	 * Post ID.
	 *
	 * @var int
	 */
	public $ID;

	/**
	 * ID of list author.
	 *
	 * @var int
	 */
	public $author = 0;

	/**
	 * The list's content.
	 *
	 * @var string
	 */
	public $content = '';

	/**
	 * The list's publication time.
	 *
	 * @var string
	 */
	public $created = '0000-00-00 00:00:00';

	/**
	 * The list's modified time.
	 *
	 * @var string
	 */
	public $modified = '0000-00-00 00:00:00';

	/**
	 * The list's title.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $title = '';

	/**
	 * The post's slug.
	 *
	 * @since 3.5.0
	 * @var string
	 */
	public $slug = '';

	/**
	 * The list's type, like post or page.
	 *
	 * @var string
	 */
	public $type = 'simple';

	/**
	 * The list's status.
	 *
	 * @var string
	 */
	public $status = 'publish';

	/**
	 * Number of items
	 *
	 * @var int
	 */
	public $count = 0;

	/**
	 * Number of votes
	 *
	 * @var int
	 */
	public $vote_counter = 0;

	/**
	 * Number of views
	 *
	 * @var int
	 */
	public $views = 0;

	/**
	 * Author user
	 *
	 * @var WP_User
	 */
	private $user = null;

	/**
	 * Items
	 *
	 * @var array
	 */
	private $items = null;

	/**
	 * Signifies whether the current query is for a date archive.
	 *
	 * @var bool
	 */
	public $is_date = false;

	/**
	 * Signifies whether the current query is for a year archive.
	 *
	 * @var bool
	 */
	public $is_year = false;

	/**
	 * Signifies whether the current query is for a month archive.
	 *
	 * @var bool
	 */
	public $is_month = false;

	/**
	 * Signifies whether the current query is for a day archive.
	 *
	 * @var bool
	 */
	public $is_day = false;

	/**
	 * Signifies whether the current query is for a specific time.
	 *
	 * @var bool
	 */
	public $is_time = false;

	/**
	 * Lists items per page limit
	 *
	 * @var bool
	 */
	public $items_limit = null;

	/**
	 * Items paged props
	 *
	 * @var array
	 */
	private $items_page = array(
		'X-WP-Total'      => 0,
		'X-WP-TotalPages' => 1,
		'X-WP-Page'       => 1,
	);

	/**
	 * Items
	 *
	 * @var WP_Error[]
	 */
	public $errors = array();

	/**
	 * Retrieve MG_UPC_List instance.
	 *
	 * @param int|array|object $list_id List ID. If empty, will use the global $mg_upc_list
	 *
	 * @return false|MG_UPC_List Collection object, false otherwise.
	 */
	public static function get_instance( $list_id = 0, $force_new_instance = false ) {

		if ( $list_id instanceof MG_UPC_List ) {
			if ( ! $force_new_instance ) {
				return $list_id;
			}
			return new MG_UPC_List( $list_id );
		}

		if ( empty( $list_id ) ) {
			global $mg_upc_list;
			if ( isset( $mg_upc_list['ID'] ) ) {
				$list_id = $mg_upc_list['ID'];
			}
		}

		if ( is_array( $list_id ) && isset( $list_id['ID'] ) ) {
			$list_id = $list_id['ID'];
		} elseif ( is_object( $list_id ) && property_exists( $list_id, 'ID' ) ) {
			$list_id = $list_id->ID;
		}

		$list_id = (int) $list_id;
		if ( ! $list_id ) {
			return false;
		}

		$_list = $GLOBALS['mg_upc']->model->find_one( (int) $list_id );
		if ( ! $_list ) {
			return false;
		}

		return new MG_UPC_List( $_list );
	}

	/**
	 * Constructor.
	 *
	 *
	 * @param WP_Post|object|array $collection List object or array.
	 */
	public function __construct( $collection ) {
		if ( is_array( $collection ) ) {
			foreach ( $collection as $key => $value ) {
				$this->$key = $value;
			}
		} else {
			foreach ( get_object_vars( $collection ) as $key => $value ) {
				$this->$key = $value;
			}
		}
		if ( ! empty( $this->created ) ) {
			$this->created = gmdate( DateTime::ATOM, strtotime( $this->created ) );
		}
		if ( ! empty( $this->modified ) ) {
			$this->modified = gmdate( DateTime::ATOM, strtotime( $this->modified ) );
		}
	}

	public function get_user_login() {
		$this->get_user(); //Load user
		$user_login = '';
		if (
			! empty( $this->user ) &&
			property_exists( $this->user, 'data' ) &&
			property_exists( $this->user->data, 'display_name' )
		) {
			$user_login = $this->user->data->display_name;
		}

		return apply_filters( 'mg_upc_list_author_user_login', $user_login, $this->user, (array) $this );
	}

	public function get_user_img() {
		$this->get_user(); //Load user
		if (
			! empty( $this->user ) &&
			property_exists( $this->user, 'ID' )
		) {
			$img = get_avatar_url( $this->user->ID );
		} else {
			$img = get_avatar_url( -1 );
		}

		return apply_filters( 'mg_upc_list_author_img', $img, $this->user, (array) $this );
	}

	public function get_user_link() {
		return apply_filters( 'mg_upc_list_author_url', '', $this->get_user(), (array) $this );
	}

	private function get_user() {
		if ( null === $this->user ) {
			$this->user = get_user_by( 'id', $this->author );
		}

		return $this->user;
	}

	public function get_link() {
		return apply_filters( 'mg_upc_list_url', null, $this );
	}

	public function set_items_limit( $limit ) {
		$this->items_limit = $limit;
	}

	public function get_items() {
		if ( null === $this->items ) {
			if ( ! empty( $this->errors ) ) {
				return array();
			}

			$this->items      = array();
			$this->items_page = array(
				'X-WP-Total'      => $this->count,
				'X-WP-TotalPages' => 0,
				'X-WP-Page'       => 1,
			);

			$post_per_page = null !== $this->items_limit ? (int) $this->items_limit : (int) get_option( 'mg_upc_item_per_page', 16 );

			if ( $post_per_page > 0 ) {

				$items = MG_UPC_List_Controller::get_instance()->get_items(
					array(
						'id'       => $this->ID,
						'per_page' => $post_per_page,
						'page'     => (int) get_query_var( 'list-page', 1 ),
					)
				);

				if ( empty( $items ) ) {
					return $this->items;
				}

				if ( is_wp_error( $items ) ) {
					$this->errors[] = $items;
					return $this->items;
				}

				$this->items = $items['items'];

				$this->items_page = array(
					'X-WP-Total'      => $items['total'],
					'X-WP-TotalPages' => $items['total_pages'],
					'X-WP-Page'       => $items['current'],
				);
			}
		}

		return $this->items;
	}

	#[ReturnTypeWillChange]
	public function offsetSet( $offset, $value ) {
		if ( $this->allowedProp( $offset, true ) ) {
			$this->$offset = $value;
		}
	}

	#[ReturnTypeWillChange]
	public function offsetExists( $offset ) {
		if ( $this->allowedProp( $offset ) ) {
			return true;
		}
		return false;
	}

	#[ReturnTypeWillChange]
	public function offsetUnset( $offset ) {
		if ( $this->allowedProp( $offset, true ) ) {
			unset( $this->$offset );
		}
	}

	#[ReturnTypeWillChange]
	public function offsetGet( $offset ) {

		if ( 'link' === $offset ) {
			return $this->get_link();
		}
		if ( 'user_img' === $offset ) {
			return $this->get_user_img();
		}
		if ( 'user_link' === $offset ) {
			return $this->get_user_link();
		}
		if ( 'user_login' === $offset ) {
			return $this->get_user_login();
		}
		if ( 'items_page' === $offset || 'items' === $offset ) {
			$this->get_items(); //Load items and items_page
		}

		if ( $this->allowedProp( $offset ) ) {
			return $this->$offset ?? null;
		}
		return null;
	}

	/**
	 * Check if a property can be used as array
	 *
	 * @param string $offset
	 * @param bool   $write
	 *
	 * @return bool
	 */
	public function allowedProp( $offset, $write = false ) {
		$allowed_set = array(
			'ID',
			'author',
			'title',
			'slug',
			'content',
			'status',
			'type',
			'count',
			'views',
			'vote_counter',
			'created',
			'modified',
			'link',
		);

		$allowed_get = $allowed_set;

		$allowed_get = array_merge(
			$allowed_get,
			array(
				'user_img',
				'user_link',
				'user_login',
				'items',
				'items_page',
			)
		);

		return $write ? in_array( $offset, $allowed_set, true ) : in_array( $offset, $allowed_get, true );
	}

	/**
	 * Convert object to array.
	 *
	 * @return array Object as array.
	 */
	public function to_array( $context = '' ) {
		$list = get_object_vars( $this );

		foreach ( array( 'items', 'items_page', 'user_img', 'user_link', 'user_login' ) as $key ) {
			if ( $this->offsetExists( $key ) ) {
				$list[ $key ] = $this->offsetGet( $key );
			}
		}

		return $list;
	}
}
