<?php
/** @noinspection PhpUnusedParameterInspection */
/** @noinspection PhpUnused */
/** @noinspection DuplicatedCode */


class MG_UPC_REST_List_Items_Controller {

	/**
	 * @var string
	 */
	private $namespace;

	/**
	 * @var string
	 */
	private $resource_name;

	/**
	 * @var MG_List_Model
	 */
	private $model;

	/**
	 * MG_UPC_REST_List_Items_Controller constructor.
	 *
	 * @param string $namespace     (Optional) Namespace
	 * @param string $resource_name (Optional) Resource name
	 */
	public function __construct( $namespace = 'mg-upc/v1', $resource_name = 'lists' ) {
		global $mg_upc;

		$this->namespace     = $namespace;
		$this->resource_name = $resource_name;

		$this->model = $mg_upc->model;
	}

	/**
	 * Get base url
	 *
	 * @return string
	 */
	public function get_base_route() {
		return $this->namespace . '/' . $this->resource_name;
	}

	/**
	 * Register our routes.
	 */
	public function register_routes() {

		$always_exist_types = MG_UPC_Helper::get_instance()->get_always_exist_list_types();
		foreach ( $always_exist_types as $sticky_type ) {
			register_rest_route(
				$this->namespace,
				'/' . $this->resource_name . '/(?P<upctype>' . $sticky_type . ')/items',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_items_always_exist' ),
						'permission_callback' => array( $this, 'get_items_permissions_check_always_exist' ),
						'args'                => self::get_collection_params(),
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'create_item_always_exist' ),
						'permission_callback' => array( $this, 'write_item_permissions_check_always_exist' ),
						'args'                => $this->get_create_params(),
					),
				)
			);
		}

		register_rest_route(
			$this->namespace,
			'/' . $this->resource_name . '/(?P<id>[\d]+)/items',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => self::get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'write_item_permissions_check' ),
					'args'                => array(
						'post_id'     => array(
							'type'        => 'integer',
							'required'    => true,
							'description' => __( 'The post id for add to the list.', 'user-post-collections' ),
						),
						'description' => array(
							'type'              => 'string',
							'maxlength'         => 400,
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => array( 'MG_UPC_REST_Lists_Controller', 'string_validate_callback' ),
							'description'       => __( 'The item comment.', 'user-post-collections' ),
						),
						'context'     => array(
							'description'       => __( 'Scope under which the request is made; determines fields present in response.', 'user-post-collections' ),
							'type'              => 'string',
							'default'           => 'view',
							'sanitize_callback' => 'sanitize_key',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->resource_name . '/(?P<id>[\d]+)/items/(?P<postid>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'write_item_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'write_item_permissions_check' ),
					'args'                => array(
						'position'    => array(
							'type'        => 'integer',
							'description' => __( 'The position for the post on the list.', 'user-post-collections' ),
						),
						'description' => array(
							'type'              => 'string',
							'description'       => __( 'The description/comment for the post on the list.', 'user-post-collections' ),
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->resource_name . '/(?P<id>[\d]+)/items/(?P<postid>[\d]+)/vote',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'vote_item' ),
					'permission_callback' => array( $this, 'vote_item_permissions_check' ),
					'args'                => array(
						'context' => array(
							'description'       => __( 'Scope under which the request is made; determines fields present in response.', 'user-post-collections' ),
							'type'              => 'string',
							'default'           => 'view',
							'sanitize_callback' => 'sanitize_key',
							'validate_callback' => 'rest_validate_request_arg',
						),
						'posts'   => array(
							'description'       => __( 'Posts present in response, comma separated.', 'user-post-collections' ),
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
			)
		);
	}

	/**
	 * Check permissions for get always exist list items.
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return bool|WP_Error
	 */
	public function get_items_permissions_check_always_exist( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'You cannot view the list.', 'user-post-collections' ),
				array( 'status' => $this->authorization_status_code() )
			);
		}
		return true;
	}

	/**
	 * Check permissions for add an item to always exist list.
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return bool|WP_Error
	 */
	public function write_item_permissions_check_always_exist( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'You cannot write this list.', 'user-post-collections' ),
				array(
					'status' => $this->authorization_status_code(),
				)
			);
		}
		return true;
	}

	/**
	 * Check permissions for get items
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return bool|WP_Error
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! MG_UPC_List_Controller::get_instance()->can_read( $request['id'] ) ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'You cannot view the list.', 'user-post-collections' ),
				array( 'status' => $this->authorization_status_code() )
			);
		}
		return true;
	}

	/**
	 * Check permissions for edit items
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return bool|WP_Error
	 */
	public function write_item_permissions_check( $request ) {
		if ( ! MG_UPC_List_Controller::get_instance()->can_edit( $request['id'] ) ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'You cannot write this list.', 'user-post-collections' ),
				array(
					'status' => $this->authorization_status_code(),
				)
			);
		}
		return true;
	}

	/**
	 * Check permissions for get items
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return bool|WP_Error
	 */
	public function vote_item_permissions_check( $request ) {

		if ( '0' !== $request['postid'] ) {
			return MG_UPC_List_Controller::get_instance()->can_vote( $request['id'] );
		}

		return true;
	}

	/**
	 * Delete item
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function delete_item( $request ) {

		$existing_list = MG_UPC_List_Controller::get_instance()->get_list( (int) $request['id'] );

		if ( is_wp_error( $existing_list ) ) {
			return $existing_list;
		}

		try {
			if ( ! $this->model->items->item_exists( (int) $request['id'], (int) $request['postid'] ) ) {
				return new WP_Error(
					'rest_item_not_found',
					__( 'Item not found.', 'user-post-collections' ),
					array( 'status' => 404 )
				);
			}
			$this->model->items->remove_item( (int) $request['id'], (int) $request['postid'] );
		} catch ( Exception $e ) {
			return new WP_Error(
				'rest_item_error',
				__( 'Unknown error.', 'user-post-collections' ),
				array( 'status' => 500 )
			);
		}

		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted' => true,
			)
		);

		return $response;
	}

	/**
	 * Add an item to an 'always exist' list
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item_always_exist( $request ) {

		$data = array();

		try {
			$list = $this->model->find_always_exist( $request['upctype'], get_current_user_id() );

			if ( null === $list ) {
				$list_id       = $this->model->create( array( 'type' => $request['upctype'] ) );
				$request['id'] = $list_id;
			} else {
				$request['id'] = $list->ID;
			}
			return $this->create_item( $request );
		} catch ( MG_UPC_Invalid_Field_Exception $e ) {
			$data['code']    = 'rest_invalid_field';
			$data['message'] = $e->getMessage();
			$data['status']  = 409;
		} catch ( MG_UPC_Required_Field_Exception $e ) {
			$data['code']    = 'rest_required_field';
			$data['message'] = $e->getMessage();
			$data['status']  = 409;
		} catch ( Exception $e ) {
			$data['code']    = 'rest_db_error';
			$data['message'] = $e->getMessage();
			$data['status']  = 500;
		}

		$response = new WP_REST_Response();
		$response->set_data( $data );
		$response->set_status( $data['status'] );

		return $response;
	}

	/**
	 * Create an item
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {

		$list_before = MG_UPC_List_Controller::get_instance()->get_list( (int) $request['id'] );

		if ( is_wp_error( $list_before ) ) {
			return $list_before;
		}

		$list_type_obj = MG_UPC_Helper::get_instance()->get_list_type( $list_before->type );

		if ( ! $list_type_obj->support( 'max_items_rotate' ) ) {
			if ( $list_type_obj->max_items <= $list_before->count ) {
				return new WP_Error(
					'rest_unable_post_add_max',
					__( 'Unable to add more items to this list.', 'user-post-collections' ),
					array( 'status' => 403 )
				);
			}
		}

		$post = MG_UPC_List_Controller::get_instance()->get_post_for_add( $request['post_id'], $request );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		if ( ! self::check_add_permission( $post, $list_before ) ) {
			return new WP_Error(
				'rest_unable_post_add',
				__( 'Unable to add this post to list.', 'user-post-collections' ),
				array( 'status' => 403 )
			);
		}

		$response = new WP_REST_Response();

		$data = array();

		try {
			if (
				is_string( $request['description'] ) &&
				! empty( $request['description'] )
			) {
				if ( ! $list_type_obj->support( 'editable_item_description' ) ) {

					$this->model->items->add_item( $request['id'], $request['post_id'] );

					$data['code']    = 'rest_item_desc_error';
					$data['message'] = __( 'This list dont support item description', 'user-post-collections' );
					$data['status']  = 409;
					$data['added']   = true;
				} else {
					$this->model->items->add_item( $request['id'], $request['post_id'], $request['description'] );
					$data['status'] = 201;
					$data['added']  = true;
				}
			} else {
				$this->model->items->add_item( $request['id'], $request['post_id'] );
				$data['status'] = 201;
				$data['added']  = true;
			}
		} catch ( MG_UPC_Item_Exist_Exception $e ) {
			$data['code']    = 'rest_item_exist_error';
			$data['message'] = $e->getMessage();
			$data['status']  = 409;
		} catch ( Exception $e ) {
			return new WP_Error(
				'rest_db_error',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		}

		if ( 'view' === $request['context'] ) {
			$data['post'] = array(
				'title' => $post->post_title,
				'link'  => get_the_permalink( $post->ID ),
			);
			$data['list'] = MG_UPC_List_Controller::get_instance()->prepare_list_for_response(
				$list_before,
				array( 'context' => 'view' )
			);
		}

		$response->set_data( $data );
		$response->set_status( $data['status'] );

		return $response;

	}

	/**
	 * Update an item
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {

		$list = MG_UPC_List_Controller::get_instance()->get_list( (int) $request['id'] );

		if ( is_wp_error( $list ) ) {
			return $list;
		}

		$response = new WP_REST_Response();
		try {
			if ( ! $this->model->items->item_exists( (int) $request['id'], (int) $request['postid'] ) ) {
				return new WP_Error(
					'rest_item_not_found',
					__( 'Item not found.', 'user-post-collections' ),
					array( 'status' => 404 )
				);
			}

			$list_type_obj = MG_UPC_Helper::get_instance()->get_list_type( $list->type );

			if (
				$list_type_obj->support( 'sortable' ) &&
				! empty( $request['position'] ) &&
				( is_int( $request['position'] ) || ctype_digit( $request['position'] ) ) &&
				$list->count >= (int) $request['position']
			) {
				$this->model->items->item_move( $request['id'], $request['postid'], $request['position'] );
				$response->set_data(
					array(
						'moved' => true,
					)
				);
			}

			if (
				isset( $request['description'] ) &&
				is_string( $request['description'] )
			) {

				if ( ! $list_type_obj->support( 'editable_item_description' ) ) {
					return new WP_Error(
						'rest_item_desc_error',
						__( 'This list dont support item description', 'user-post-collections' ),
						array( 'status' => 409 )
					);
				}

				$this->model->items->update_item_description( $request['id'], $request['postid'], $request['description'] );
				$response->set_data(
					array(
						'update' => true,
					)
				);
			}
		} catch ( Exception $e ) {
			return new WP_Error(
				'rest_db_error',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		}

		return $response;
	}

	/**
	 * Vote to an item
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function vote_item( $request ) {
		$request['id']     = (int) $request['id'];
		$request['postid'] = (int) $request['postid'];

		$list = MG_UPC_List_Controller::get_instance()->get_list( $request['id'] );

		if ( is_wp_error( $list ) ) {
			return $list;
		}

		$response = new WP_REST_Response();
		try {
			if ( 0 !== $request['postid'] ) {
				if ( ! $this->model->items->item_exists( $request['id'], $request['postid'] ) ) {
					return new WP_Error(
						'rest_item_not_found',
						__( 'Item not found.', 'user-post-collections' ),
						array( 'status' => 404 )
					);
				}
			}

			if ( ! $this->model->support( $request['id'], 'vote' ) ) {
				return new WP_Error(
					'rest_invalid_list_operation',
					__( 'This list dont support this operation.', 'user-post-collections' ),
					array( 'status' => 500 )
				);
			}

			$data     = array();
			$can_vote = MG_UPC_List_Controller::get_instance()->can_vote( $request['id'] );

			if ( 0 !== $request['postid'] ) {
				if ( is_wp_error( $can_vote ) ) {
					return $can_vote;
				}
				if ( true === $can_vote ) {
					$this->model->items->vote( $request['id'], $request['postid'] );
					$data['vote'] = true;
				}
			} else {
				if ( ! is_user_logged_in() ) {
					$data['can_vote'] = true; //to show button and show required login on vote button
				} else {
					$data['can_vote'] = true === $can_vote;
				}
			}

			if ( ! empty( $request['posts'] ) ) {
				$posts = explode( ',', $request['posts'] );
				$posts = array_map( 'trim', $posts );
				$posts = array_map( 'absint', $posts );

				$actual = $this->model->items->items(
					array(
						'list_id' => $request['id'],
						'post_id' => $posts,
					)
				);

				$data['posts'] = $actual['items'];
			}

			$list = MG_UPC_List_Controller::get_instance()->get_list( (int) $request['id'] );

			if ( is_wp_error( $list ) ) {
				return $list;
			}

			$data['vote_counter'] = $list->vote_counter;

			$response->set_data( $data );
		} catch ( Exception $e ) {
			return new WP_Error(
				'rest_db_error',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		}

		return $response;
	}

	/**
	 * Check if the user can add an item to a list
	 *
	 * @param WP_Post $post
	 * @param object  $list
	 *
	 * @return bool
	 */
	public static function check_add_permission( $post, $list ) {

		if ( ! MG_UPC_Helper::get_instance()->is_available_post_type_for_list_type( $post->post_type, $list->type ) ) {
			return false;
		}

		// Is the post readable?
		if ( 'publish' === $post->post_status || current_user_can( 'read_post', $post->ID ) ) {
			return true;
		}

		$post_status_obj = get_post_status_object( $post->post_status );
		if ( $post_status_obj && $post_status_obj->public ) {
			return true;
		}

		// Can we read the parent if we're inheriting?
		if ( 'inherit' === $post->post_status && $post->post_parent > 0 ) {
			$parent = get_post( $post->post_parent );
			if ( $parent ) {
				return self::check_add_permission( $parent, $list );
			}
		}

		/*
		 * If there isn't a parent, but the status is set to inherit, assume
		 * it's published (as per get_post_status()).
		 */
		if ( 'inherit' === $post->post_status ) {
			return true;
		}

		return false;
	}

	/**
	 * Get items for an 'always exits' list type
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items_always_exist( $request ) {
		try {

			$list = $this->model->find_always_exist( $request['upctype'], get_current_user_id() );

			if ( null === $list ) {
				$list_id       = $this->model->create( array( 'type' => $request['type'] ) );
				$request['id'] = $list_id;
			} else {
				$request['id'] = $list->ID;
			}
		} catch ( Exception $e ) {
			return new WP_Error(
				'rest_db_error',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		}
		return $this->get_items( $request );
	}

	/**
	 * Get list items
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$data = MG_UPC_List_Controller::get_instance()->get_items( $request );

		if ( empty( $data ) ) {
			return rest_ensure_response( array() );
		}
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$response = array();
		foreach ( $data['items'] as $item ) {
			$response[] = $this->prepare_response_for_collection( $item );
		}

		// Return all of our comment response data.
		$rest_response = new WP_REST_Response( $response, 200 );
		$rest_response->header( 'X-WP-Total', $data['total'] );
		$rest_response->header( 'X-WP-TotalPages', $data['total_pages'] );
		$rest_response->header( 'X-WP-Page', $data['current'] );

		return $rest_response;
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
	 * Prepare a response for inserting into a collection of responses.
	 *
	 * This is copied from WP_REST_Controller class in the WP REST API v2 plugin.
	 *
	 * @param WP_REST_Response $response Response object.
	 *
	 * @return array|WP_REST_Response
	 */
	public function prepare_response_for_collection( $response ) {
		if ( ! ( $response instanceof WP_REST_Response ) ) {
			return $response;
		}

		$data   = (array) $response->get_data();
		$server = rest_get_server();

		if ( method_exists( $server, 'get_compact_response_links' ) ) {
			$links = call_user_func( array( $server, 'get_compact_response_links' ), $response );
		} else {
			$links = call_user_func( array( $server, 'get_response_links' ), $response );
		}

		if ( ! empty( $links ) ) {
			$data['_links'] = $links;
		}

		return $data;
	}

	// Sets up the proper HTTP status code for authorization.
	public function authorization_status_code() {

		$status = 401;

		if ( is_user_logged_in() ) {
			$status = 403;
		}

		return $status;
	}

	/**
	 * Get params configuration for create an item
	 *
	 * @return array[]
	 */
	public function get_create_params() {
		return array(
			'post_id'     => array(
				'type'        => 'integer',
				'required'    => true,
				'description' => __( 'The post id for add to the list.', 'user-post-collections' ),
			),
			'description' => array(
				'type'              => 'string',
				'maxlength'         => 400,
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => array( 'MG_UPC_REST_Lists_Controller', 'string_validate_callback' ),
				'description'       => __( 'The item comment.', 'user-post-collections' ),
			),
		);
	}

	/**
	 * Get params configuration for collection (of items)
	 *
	 * @return array[]
	 */
	public static function get_collection_params() {
		$query_params = array(
			'page'     => array(
				'description'       => __( 'Current page of the collection.', 'user-post-collections' ),
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
				'minimum'           => 1,
			),
			'per_page' => array(
				'description'       => __( 'Maximum number of items to be returned in result set.', 'user-post-collections' ),
				'type'              => 'integer',
				'default'           => 12,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);

		$query_params['order'] = array(
			'description' => __( 'Order sort attribute ascending or descending.', 'user-post-collections' ),
			'type'        => 'string',
			'default'     => 'desc',
			'enum'        => array( 'asc', 'desc' ),
		);

		$query_params['orderby'] = array(
			'description' => __( 'Sort collection by attribute.', 'user-post-collections' ),
			'type'        => 'string',
			'default'     => '',
			'enum'        => array(
				'',
				'votes',
				'position',
				'post_id',
				'added',
			),
		);

		return apply_filters( 'rest_mg_upc_list_items_collection_params', $query_params );
	}

	public function activate( $network_wide ) { }

	public function deactivate() { }

	public function register_hook_callbacks() { }

	public function init() { }

	public function upgrade( $db_version = 0 ) { }

}
