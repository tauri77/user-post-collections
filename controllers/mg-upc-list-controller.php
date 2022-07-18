<?php

/**
 * Class MG_List_Controller
 *
 * This class is used for the rest controllers and page controller, for centralized operations.
 */

class MG_UPC_List_Controller extends MG_UPC_Module {

	/**
	 * @var MG_List_Model main collections model
	 */
	private $model;

	public function __construct() {
		add_action( 'mg_upc_loaded', array( $this, 'mg_upc_loaded' ) );
	}

	/**
	 * On main controller loaded
	 */
	public function mg_upc_loaded() {
		global $mg_upc;

		$this->model = $mg_upc->model;
	}

	/**
	 * List of user list for "My Lists" or for add an item
	 *
	 * @param $base_args
	 * @param $config_or_request
	 *
	 * @return array|WP_Error|null
	 */
	public function get_user_lists( $base_args, $config_or_request ) {
		$helper = MG_UPC_Helper::get_instance();
		$args   = $base_args;

		if ( $config_or_request instanceof WP_REST_Request ) {
			$config = $config_or_request->get_params();
		} else {
			$config = $config_or_request;
		}

		// For adding item
		if ( isset( $config['adding'] ) ) {
			$post = $this->get_post_for_add( $config['adding'], $config );
			if ( is_wp_error( $post ) ) {
				return $post;
			}

			$enabled_types = $helper->get_available_list_types( $post->post_type );
			if ( empty( $enabled_types ) ) {
				return array();
			}
			$always_exist_types = $helper->get_always_exist_list_types( $post->post_type );
		} else {
			//show to the user disabled types?
			$valid_types        = $helper->get_list_types( true );
			$enabled_types      = array_keys( $valid_types );
			$always_exist_types = $helper->get_always_exist_list_types( false );
		}

		$sticky_types    = $helper->get_stick_list_types();
		$sticky_types    = array_intersect( $sticky_types, $enabled_types );
		$no_sticky_types = array_diff( $enabled_types, $sticky_types );

		//only use the list types that has my_list enabled
		$my_list_types = $helper->get_my_list_types();
		if ( isset( $config['adding'] ) ) {
			//check user can edit list types
			$unable_to_edit = array();
			foreach ( $my_list_types as $k => $name_type ) {
				$_type = $helper->get_list_type( $name_type );
				if ( ! current_user_can( $_type->get_cap()->edit_posts ) ) {
					$unable_to_edit[] = $name_type;
				}
			}
			$my_list_types = array_diff( $my_list_types, $unable_to_edit );
		}
		$no_sticky_types    = array_intersect( $no_sticky_types, $my_list_types );
		$sticky_types       = array_intersect( $sticky_types, $my_list_types );
		$always_exist_types = array_intersect( $always_exist_types, $my_list_types );

		$empty_result = array(
			'results'     => array(),
			'total'       => 0,
			'total_pages' => 1,
			'current'     => 1,
		);

		//Get the no sticky lists
		$args['type'] = $no_sticky_types;
		try {
			if ( ! empty( $args['type'] ) ) {
				$lists = $this->model->find( $args );
			} else {
				$lists = $empty_result;
			}
		} catch ( MG_UPC_Invalid_Field_Exception $e ) {
			return new WP_Error( 'upc_invalid_field', esc_html( $e->getMessage() ), array( 'status' => 500 ) );
		}

		if ( isset( $config['adding'], $post ) ) {
			$lists['addingPost'] = $post;
			//Add no sticky with always exist enabled
			$found_lists = array_map(
				function ( $list ) {
					return $list->type;
				},
				$lists['results']
			);
			foreach ( $always_exist_types as $always_exist_type ) {
				if (
					! in_array( $always_exist_type, $sticky_types, true ) &&
					! in_array( $always_exist_type, $found_lists, true )
				) {
					$lists['results'][] = $helper->get_initial_always_exist_list( $always_exist_type );
					$lists['total'] ++;
				}
			}
		}

		if ( 1 === $config['page'] ) {
			//get the sticky list
			$args['type'] = $sticky_types;
			try {
				if ( ! empty( $args['type'] ) ) {
					$stick_lists = $this->model->find( $args );
				} else {
					$stick_lists = $empty_result;
				}
			} catch ( MG_UPC_Invalid_Field_Exception $e ) {
				return new WP_Error(
					'rest_db_error',
					esc_html( $e->getMessage() ),
					array( 'status' => 500 )
				);
			}
			if ( isset( $config['adding'] ) ) {
				//Add sticky with always exist enabled
				$found_lists = array_map(
					function ( $list ) {
						return $list->type;
					},
					$stick_lists['results']
				);
				foreach ( $always_exist_types as $always_exist_type ) {
					if (
						in_array( $always_exist_type, $sticky_types, true ) &&
						! in_array( $always_exist_type, $found_lists, true )
					) {
						$stick_lists['results'][] = $helper->get_initial_always_exist_list( $always_exist_type );
					}
				}
			}

			$lists['results'] = array_merge( $stick_lists['results'], $lists['results'] );
			$lists['total']  += count( $stick_lists['results'] );
		}

		return $lists;
	}

	/**
	 * Get a list
	 *
	 * @param int|WP_REST_Request|array $config_or_request If is int type, then use as list_id
	 *                                                     If array, used keys: 'id'(required), 'exclude_not_found_error'
	 *                                                     If is WP_REST_Request, then params equivalents to as array
	 *
	 * @return object|WP_Error
	 */
	public function get_list( $config_or_request ) {

		if ( is_int( $config_or_request ) ) {
			$config_or_request = array( 'id' => $config_or_request );
		}
		if ( $config_or_request instanceof WP_REST_Request ) {
			$config = $config_or_request->get_params();
		} else {
			$config = $config_or_request;
		}
		try {
			$list = $this->model->find_one( (int) $config['id'] );
		} catch ( MG_UPC_Invalid_Field_Exception $e ) {
			return new WP_Error(
				'rest_db_error',
				esc_html( $e->getMessage() ),
				array( 'status' => 500 )
			);
		}

		if ( empty( $config['exclude_not_found_error'] ) && empty( $list ) ) {
			return new WP_Error(
				'rest_list_not_found',
				esc_html__( 'List not found.', 'user-post-collections' ),
				array( 'status' => 404 )
			);
		}

		return $list;
	}

	/**
	 * Get a list for response
	 *
	 * @param int|WP_REST_Request|array $config_or_request If is int type, then use as list_id
	 *                                                     If array, used keys: 'id'(required), 'context', 'items_page', 'items_per_page'
	 *                                                     If is WP_REST_Request, then params equivalents to as array
	 *
	 * @return array|WP_Error
	 */
	public function get_list_for_response( $config_or_request ) {

		$list = $this->get_list( $config_or_request );

		if ( is_wp_error( $list ) ) {
			return $list;
		}

		if ( ! isset( $config_or_request['context'] ) ) {
			$config_or_request['context'] = 'view';
		}

		$list = $this->prepare_list_for_response( $list, $config_or_request );

		return (array) $list;
	}

	/**
	 * Get list items
	 *
	 * @param array|int|WP_REST_Request $config_or_request If is int type, then use as list_id
	 *                                                     If array, used keys: 'id'(required), 'page', 'per_page', 'orderby', 'order'
	 *                                                     If is WP_REST_Request, then params equivalents to as array
	 *
	 * @return array|WP_Error
	 */
	public function get_items( $config_or_request ) {
		if ( is_int( $config_or_request ) ) {
			$config_or_request = array( 'id' => $config_or_request );
		}
		if ( $config_or_request instanceof WP_REST_Request ) {
			$config = $config_or_request->get_params();
		} else {
			$config = $config_or_request;
		}

		$rest_params = MG_UPC_REST_List_Items_Controller::get_collection_params();

		$defaults = array();
		foreach ( $rest_params as $param => $config_param ) {
			if ( isset( $config_param['default'] ) ) {
				$defaults[ $param ] = $config_param['default'];
			}
		}
		$config = array_merge( $defaults, $config );

		$list = $this->get_list( (int) $config['id'] );

		if ( is_wp_error( $list ) ) {
			return $list;
		}

		$list_type = MG_UPC_Helper::get_instance()->get_list_type( $list->type, true );

		if ( false === $list_type ) {
			return new WP_Error(
				'rest_invalid_type',
				esc_html__( 'Invalid list type.', 'user-post-collections' ),
				array( 'status' => 500 )
			);
		}

		if ( is_string( $list_type['default_orderby'] ) ) {
			$config['orderby'] = $list_type['default_orderby'];
		}
		if ( is_string( $list_type['default_order'] ) ) {
			$config['order'] = $list_type['default_order'];
		}
		$items = $this->model->items->items(
			array(
				'list_id'        => (int) $config['id'],
				'page'           => (int) $config['page'],
				'items_per_page' => min( 100, (int) $config['per_page'] ),
				'orderby'        => $config['orderby'],
				'order'          => $config['order'],
			)
		);

		$data = array();

		if ( empty( $items ) ) {
			return $data;
		}

		foreach ( $items['items'] as $item ) {
			//TODO check_is_post_type_allowed? (maybe change) and ignore this? -.- total count fail..
			$response = $this->prepare_item_for_response( $item, $config );
			if ( null !== $response ) {
				$data[] = $response;
			}
		}

		return array(
			'items'       => $data,
			'total'       => $items['total'],
			'total_pages' => $items['total_pages'],
			'current'     => $items['current'],
		);
	}


	/**
	 * Matches the list data to the schema we want.
	 *
	 * @param object $list   The object whose response is being prepared.
	 * @param array  $config (Optional) Key used: context, items_per_page, items_page
	 *
	 * @return array|WP_Error List data as array, WP_Error on failure
	 */
	public function prepare_list_for_response( $list, $config = array() ) {

		$list_data = (array) $list;

		$user                   = get_user_by( 'id', $list_data['author'] );
		$list_data['user_link'] = '';
		if (
			! empty( $user ) &&
			property_exists( $user, 'data' ) &&
			property_exists( $user->data, 'display_name' )
		) {
			$list_data['user_login'] = $user->data->display_name;
			$list_data['user_img']   = get_avatar_url( $user->ID );
		} else {
			$list_data['user_img'] = get_avatar_url( -1 );
		}
		$list_data['user_link'] = apply_filters( 'mg_upc_list_author_url', $list_data['user_link'], $user, $list_data );

		if ( isset( $config['context'] ) && 'view' === $config['context'] ) {
			if ( is_int( $list_data['ID'] ) || ctype_digit( $list_data['ID'] ) ) {
				//Add items
				$params = array( 'id' => $list_data['ID'] );
				if ( ! empty( $config['items_per_page'] ) ) {
					$params['per_page'] = $config['items_per_page'];
				}
				if ( ! empty( $config['items_page'] ) ) {
					$params['page'] = $config['items_page'];
				}
				$items              = $this->get_items( $params );
				$list_data['items'] = array();
				if ( ! empty( $items ) && ! is_wp_error( $items ) ) {
					$list_data['items']      = $items['items'];
					$list_data['items_page'] = array(
						'X-WP-Total'      => $items['total'],
						'X-WP-TotalPages' => $items['total_pages'],
						'X-WP-Page'       => $items['current'],
					);
				}
				if ( is_wp_error( $items ) ) {
					return new WP_Error(
						'mg_upc_items_error',
						esc_html__( 'Error on get items', 'user-post-collections' ),
						array( 'status' => 500 )
					);
				}
			}
		}

		if ( ! empty( $list_data['created'] ) ) {
			$list_data['created'] = gmdate( DATE_ISO8601, strtotime( $list_data['created'] ) );
		}

		if ( ! empty( $list_data['modified'] ) ) {
			$list_data['modified'] = gmdate( DATE_ISO8601, strtotime( $list_data['modified'] ) );
		}

		$list_data = apply_filters( 'prepare_list_data_for_response', $list_data, $list, $config );

		return $list_data;
	}

	/**
	 * Matches the item list data to the schema we want.
	 *
	 * @param object|array $item   The item data
	 * @param array        $config Only used to pass to the filter
	 *
	 * @return array|null
	 */
	public function prepare_item_for_response( $item, $config ) {

		$data = (array) $item;

		$post = get_post( $item->post_id );

		if ( null !== $post ) {
			$data = $this->add_post_info_to_item( $data, $post );
		} else {
			//Or fake item?
			return null;
		}

		$item_response = apply_filters( 'mg_prepare_item_for_response', $data, $config );

		if ( key_exists( 'addon_json', $item_response ) ) {
			unset( $item_response['addon_json'] );
		}

		return $item_response;
	}

	/**
	 * Add post info to item
	 *
	 * @param array   $data    The item data. ( can be empty on get a virtual item to see )
	 * @param WP_Post $post    The post associated to the item
	 * @param null    $config  (Only used for filter)
	 *
	 * @return array
	 */
	public function add_post_info_to_item( $data, $post, $config = null ) {

		if ( $post instanceof WP_Post ) {
			$data['link']      = get_permalink( $post->ID );
			$data['post_type'] = $post->post_type;

			add_filter( 'protected_title_format', array( $this, 'protected_title_format' ) );
			$data['title'] = html_entity_decode( get_the_title( $post->ID ) );
			remove_filter( 'protected_title_format', array( $this, 'protected_title_format' ) );

			$excerpt = apply_filters( 'get_the_excerpt', $post->post_excerpt, $post );
			/** This filter is documented in wp-includes/post-template.php */
			$excerpt         = apply_filters( 'the_excerpt', $excerpt );
			$data['excerpt'] = post_password_required( $post ) ? '' : $excerpt;

			$data['featured_media'] = '' . get_post_thumbnail_id( $post->ID ); //to string for max int on other plataforms
			$data['image']          = get_the_post_thumbnail_url( $post->ID ); // or add size , 'medium'

			$data = apply_filters( "mg_post_item_{$post->post_type}_for_response", $data, $config );
		}

		return apply_filters( 'mg_post_item_for_response', $data, $config );
	}

	/**
	 * Overwrites the default protected title format.
	 *
	 * By default, WordPress will show password protected posts with a title of
	 * "Protected: %s", as the REST API communicates the protected status of a post
	 * in a machine readable format, we remove the "Protected: " prefix.
	 *
	 *
	 * @return string Protected title format.
	 */
	public function protected_title_format() {
		return '%s';
	}

	/**
	 * Get the post, if the ID is valid.
	 *
	 * @param int                   $id       Supplied ID.
	 * @param array|WP_REST_Request $request Only used for password
	 *
	 * @return WP_Post|WP_Error Post object if ID is valid, WP_Error otherwise.
	 */
	public function get_post_for_add( $id, $request ) {
		$error = new WP_Error(
			'rest_post_not_found',
			esc_html__( 'Post not found.', 'user-post-collections' ),
			array( 'status' => 404 )
		);

		if ( (int) $id <= 0 ) {
			return $error;
		}

		$post = get_post( (int) $id );
		if ( empty( $post ) || empty( $post->ID ) ) {
			return $error;
		}

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		if ( ! empty( $post->post_password ) ) {
			if ( ! empty( $request['password'] ) ) {
				// Check post password, and return error if invalid.
				if ( ! hash_equals( $post->post_password, $request['password'] ) ) {
					return new WP_Error(
						'rest_post_incorrect_password',
						esc_html__( 'Incorrect post password.', 'user-post-collections' ),
						array( 'status' => 403 )
					);
				}
			} else {
				return new WP_Error(
					'rest_post_incorrect_password',
					esc_html__( 'Unset post password.', 'user-post-collections' ),
					array( 'status' => 403 )
				);
			}
		}

		return $post;
	}

	/**
	 * Check if user can vote in the list
	 *
	 * @param int $list_id
	 *
	 * @return bool|WP_Error
	 */
	public function can_vote( $list_id ) {
		$ret = null;

		//short circuit for vote permission
		$ret = apply_filters( 'mg_upc_pre_can_vote', $ret, $list_id );

		if ( null !== $ret ) {
			return $ret;
		}

		$list_type_obj = $this->get_type_obj_from_list( $list_id );

		if ( false === $list_type_obj ) {
			$ret = new WP_Error(
				'rest_db_error',
				'The list type is not valid',
				array( 'status' => 500 )
			);
		}

		// check for return a message ( current_user_can do this too )
		if (
			null === $ret &&
			$list_type_obj->vote_require_login() &&
			! is_user_logged_in()
		) {
			$ret = new WP_Error(
				'mg_upc_login_required',
				'Sorry, login required.',
				array( 'status' => 403 )
			);
		}

		// check for return a message ( current_user_can do this too )
		try {
			$max_votes = $list_type_obj->get_max_votes_per_user();
			if (
				null === $ret &&
				is_user_logged_in() &&
				( 0 !== $max_votes && $this->model->user_count_votes( $list_id, get_current_user_id() ) >= $max_votes )
			) {
				$ret = new WP_Error(
					'mg_upc_vote_limit',
					'You already voted in this poll.',
					array( 'status' => 409 )
				);
			}
			$ip_max_votes = $list_type_obj->get_max_votes_per_ip();
			if (
				null === $ret &&
				( 0 !== $ip_max_votes && $this->model->ip_count_votes( $list_id ) >= $ip_max_votes )
			) {
				$ret = new WP_Error(
					'mg_upc_vote_limit',
					'Your IP has already reached the limit for this poll.',
					array( 'status' => 409 )
				);
			}
		} catch ( Exception $e ) {
			$ret = new WP_Error(
				'rest_db_error',
				esc_html( $e->getMessage() ),
				array( 'status' => 500 )
			);
		}

		if ( null === $ret ) {
			$ret = current_user_can( $list_type_obj->get_cap()->vote, $list_id );
		}

		return apply_filters( 'mg_upc_can_vote', $ret, $list_id );
	}

	/**
	 * Get post type from list
	 *
	 * @param object|int $list
	 *
	 * @return false|MG_UPC_List_Type
	 */
	public function get_type_obj_from_list( $list ) {

		if ( is_int( $list ) || is_string( $list ) ) {
			try {
				$list = $this->model->find_one( (int) $list );
			} catch ( Exception $e ) {
				return false;
			}
		}

		if ( null !== $list ) {
			return MG_UPC_Helper::get_instance()->get_list_type( $list->type );
		}

		return false;
	}

	/**
	 * Check if the user can read an specified list
	 *
	 * @param int $list_id
	 *
	 * @return bool
	 */
	public function can_read( $list_id ) {

		$list_type_obj = $this->get_type_obj_from_list( $list_id );

		if ( false !== $list_type_obj ) {
			return current_user_can( $list_type_obj->get_cap()->read_post, $list_id );
		}

		return false;
	}

	/**
	 * Check if the user can read private an specified list type
	 *
	 * @param string $list_type
	 *
	 * @return bool
	 */
	public function can_read_private_type( $list_type ) {

		$list_type_obj = MG_UPC_Helper::get_instance()->get_list_type( $list_type );

		if ( false !== $list_type_obj ) {
			return current_user_can( $list_type_obj->get_cap()->read_private_posts );
		}

		return false;
	}

	/**
	 * Check if the user can edit an specified list
	 *
	 * @param int $list_id
	 *
	 * @return bool
	 */
	public function can_edit( $list_id ) {

		$list_type_obj = $this->get_type_obj_from_list( $list_id );
		if ( false !== $list_type_obj ) {
			return current_user_can( $list_type_obj->get_cap()->edit_post, $list_id );
		}

		return false;
	}

	/**
	 * Check if the user can delete an specified list
	 *
	 * @param int $list_id
	 *
	 * @return bool
	 */
	public function can_delete( $list_id ) {

		$list_type_obj = $this->get_type_obj_from_list( $list_id );
		if ( false !== $list_type_obj ) {
			return current_user_can( $list_type_obj->get_cap()->delete_post, $list_id );
		}

		return false;
	}

	/**
	 * Check if the user can edit lists of other users
	 *
	 * @param string $list_type The list type
	 *
	 * @return bool
	 */
	public function can_edit_others( $list_type ) {
		$list_type_obj = MG_UPC_Helper::get_instance()->get_list_type( $list_type );
		if ( false !== $list_type_obj ) {
			return current_user_can( $list_type_obj->get_cap()->edit_others_posts );
		}

		return false;
	}

	/**
	 * Check if the user can create a list
	 *
	 * @param string $list_type The list type
	 *
	 * @return bool
	 */
	public function can_create( $list_type ) {
		$list_type_obj = MG_UPC_Helper::get_instance()->get_list_type( $list_type );
		if ( false !== $list_type_obj ) {
			return current_user_can( $list_type_obj->get_cap()->create_posts );
		}

		return false;
	}

	/**
	 * Sanitize list title
	 *
	 * @param $title
	 *
	 * @return string
	 */
	public function sanitize_title( $title ) {
		return sanitize_text_field( $title );
	}

	/**
	 * Sanitize list content
	 *
	 * @param $content
	 *
	 * @return string
	 *
	 * @noinspection PhpUnused
	 */
	public function sanitize_content( $content ) {
		return wp_kses( $content, $this->list_allowed_tags() );
	}

	/**
	 * Content allowed html tags
	 *
	 * @return array
	 */
	public function list_allowed_tags() {
		$allowed_tags = array(
			'b'          => array(),
			'i'          => array(),
			's'          => array(),
			'strong'     => array(),
			'blockquote' => array(
				'cite' => true,
			),
			'cite'       => array(),
			'br'         => array(),
		);

		/**
		 * Add allowed html tags allowed for list content
		 *
		 * You can add html tags, ex:
		 * $allowed_tags['a'] = array( 'href'  => true, 'title' => true )
		 *
		 * @param array $allowed_tags The allowed tags.
		 */
		return apply_filters( 'mg_upc_content_allowed_tags', $allowed_tags );
	}

	// Sets up the proper HTTP status code for authorization.
	public static function authorization_status_code() {

		$status = 401;

		if ( is_user_logged_in() ) {
			$status = 403;
		}

		return $status;
	}

	public function init() { }

	public function activate( $network_wide ) { }

	public function deactivate() { }

	public function register_hook_callbacks() { }

	public function upgrade( $db_version = 0 ) { }

}
