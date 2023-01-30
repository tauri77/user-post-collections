<?php

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
	 * @var array|mixed
	 */
	private $schema;

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
					'schema' => array( $this, 'get_item_schema' ),
				)
			);

			register_rest_route(
				$this->namespace,
				'/' . $this->resource_name . '/(?P<upctype>' . $sticky_type . ')/items/(?P<postid>[\d]+)',
				array(
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => array( $this, 'delete_item_always_exist' ),
						'permission_callback' => array( $this, 'write_item_permissions_check_always_exist' ),
					),
					'schema' => array( $this, 'get_item_schema' ),
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
							'description' => esc_html__(
								'The post id for add to the list.',
								'user-post-collections'
							),
						),
						'description' => array(
							'type'              => 'string',
							'maxLength'         => 400,
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
							'validate_callback' => 'rest_validate_request_arg',
							'description'       => esc_html__( 'The item comment.', 'user-post-collections' ),
						),
						'quantity'    => array(
							'type'              => 'integer',
							'description'       => esc_html__(
								'The quantity for the post on the list.',
								'user-post-collections'
							),
							'sanitize_callback' => 'absint',
							'validate_callback' => 'rest_validate_request_arg',
							'minimum'           => 1,
						),
						'context'     => array(
							'description'       => esc_html__(
								'Scope under which the request is made; determines fields present in response.',
								'user-post-collections'
							),
							'type'              => 'string',
							'default'           => 'view',
							'sanitize_callback' => 'sanitize_key',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
				'schema' => array( $this, 'get_item_schema' ),
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
							'description' => esc_html__(
								'The position for the post on the list.',
								'user-post-collections'
							),
						),
						'description' => array(
							'type'              => 'string',
							'description'       => esc_html__(
								'The description/comment for the post on the list.',
								'user-post-collections'
							),
							'sanitize_callback' => 'sanitize_text_field',
						),
						'quantity'    => array(
							'type'              => 'integer',
							'description'       => esc_html__(
								'The quantity for the post on the list.',
								'user-post-collections'
							),
							'sanitize_callback' => 'absint',
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
							'description'       => esc_html__(
								'Scope under which the request is made; determines fields present in response.',
								'user-post-collections'
							),
							'type'              => 'string',
							'default'           => 'view',
							'sanitize_callback' => 'sanitize_key',
							'validate_callback' => 'rest_validate_request_arg',
						),
						'posts'   => array(
							'description'       => esc_html__(
								'Posts present in response, comma separated.',
								'user-post-collections'
							),
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
	 *
	 * @noinspection PhpUnused (Rest API callback)
	 */
	public function get_items_permissions_check_always_exist( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'You cannot view the list.', 'user-post-collections' ),
				array( 'status' => MG_UPC_List_Controller::authorization_status_code() )
			);
		}

		$list_type_obj = MG_UPC_Helper::get_instance()->get_list_type( $request['upctype'] );
		if ( false !== $list_type_obj ) {
			return current_user_can( $list_type_obj->get_cap()->create_posts );
		}

		return true;
	}

	/**
	 * Check permissions for add an item to always exist list.
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return bool|WP_Error
	 *
	 * @noinspection PhpUnused (Rest API callback)
	 */
	public function write_item_permissions_check_always_exist( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'You cannot write this list.', 'user-post-collections' ),
				array(
					'status' => MG_UPC_List_Controller::authorization_status_code(),
				)
			);
		}

		$list_type_obj = MG_UPC_Helper::get_instance()->get_list_type( $request['upctype'] );
		if ( false !== $list_type_obj ) {
			return current_user_can( $list_type_obj->get_cap()->create_posts );
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
				array( 'status' => MG_UPC_List_Controller::authorization_status_code() )
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
	 *
	 * @noinspection PhpUnused (Rest API callback)
	 */
	public function write_item_permissions_check( $request ) {
		if ( ! MG_UPC_List_Controller::get_instance()->can_edit( $request['id'] ) ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'You cannot write this list.', 'user-post-collections' ),
				array(
					'status' => MG_UPC_List_Controller::authorization_status_code(),
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
	 *
	 * @noinspection PhpUnused (Rest API callback)
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

		return $this->delete_item_from_id( (int) $request['id'], (int) $request['postid'] );
	}

	/**
	 * Remove item from a list ID
	 *
	 * @param int $list_id
	 * @param int $post_id
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	private function delete_item_from_id( $list_id, $post_id ) {
		try {
			if ( ! $this->model->items->item_exists( (int) $list_id, (int) $post_id ) ) {
				return new WP_Error(
					'rest_item_not_found',
					esc_html__( 'Item not found.', 'user-post-collections' ),
					array(
						'status'  => 404,
						'list_id' => (int) $list_id,
						'post_id' => (int) $post_id,
					)
				);
			}
			$this->model->items->remove_item( (int) $list_id, (int) $post_id );
		} catch ( Exception $e ) {
			return new WP_Error(
				'rest_item_error',
				esc_html__( 'Unknown error.', 'user-post-collections' ),
				array(
					'status'  => 500,
					'list_id' => (int) $list_id,
					'post_id' => (int) $post_id,
				)
			);
		}

		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted' => true,
				'list_id' => (int) $list_id,
				'post_id' => (int) $post_id,
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
	 *
	 * @noinspection PhpUnused (Rest API callback)
	 */
	public function create_item_always_exist( $request ) {

		$response = array( 'data' => array() );

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
			$response['code']           = 'rest_invalid_field';
			$response['message']        = $e->getMessage();
			$response['data']['status'] = 409;
		} catch ( MG_UPC_Required_Field_Exception $e ) {
			$response['code']           = 'rest_required_field';
			$response['message']        = $e->getMessage();
			$response['data']['status'] = 409;
		} catch ( Exception $e ) {
			$response['code']           = 'rest_db_error';
			$response['message']        = $e->getMessage();
			$response['data']['status'] = 500;
		}

		$response_api = new WP_REST_Response();
		$response_api->set_data( $response );
		$response_api->set_status( $response['data']['status'] );

		return $response_api;
	}

	/**
	 * Delete an item from an 'always exist' list
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return WP_Error|WP_REST_Response
	 *
	 * @noinspection PhpUnused (Rest API callback)
	 */
	public function delete_item_always_exist( $request ) {

		$response = array( 'data' => array() );

		try {
			$list = $this->model->find_always_exist( $request['upctype'], get_current_user_id() );

			if ( null === $list ) {
				return new WP_Error(
					'rest_item_not_found',
					esc_html__( 'Item not found.', 'user-post-collections' ),
					array( 'status' => 404 )
				);
			} else {
				return $this->delete_item_from_id( (int) $list->ID, (int) $request['postid'] );
			}
		} catch ( MG_UPC_Invalid_Field_Exception $e ) {
			$response['code']           = 'rest_invalid_field';
			$response['message']        = $e->getMessage();
			$response['data']['status'] = 409;
		}

		$response_api = new WP_REST_Response();
		$response_api->set_data( $response );
		$response_api->set_status( $response['data']['status'] );

		return $response_api;
	}

	/**
	 * Create an item
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {

		$response = array( 'data' => array() );

		try {
			$response = self::add_tem_to_list( (int) $request['id'], (int) $request['post_id'], $request );
			if ( is_wp_error( $response ) ) {
				return $response;
			}
		} catch ( MG_UPC_Item_Exist_Exception $e ) {
			$response['code']           = 'rest_item_exist_error';
			$response['message']        = esc_html( $e->getMessage() );
			$response['data']['status'] = 409;

			if ( 'check' === $request['context'] ) {
				$response['check'] = 'OK';
			}
		} catch ( Exception $e ) {
			return new WP_Error(
				'rest_db_error',
				esc_html( $e->getMessage() ),
				array( 'status' => 500 )
			);
		}

		if ( 'view' === $request['context'] ) {
			$list_before = MG_UPC_List_Controller::get_instance()->get_list( (int) $request['id'] );

			$post = MG_UPC_List_Controller::get_instance()->get_post_for_add( (int) $request['post_id'], $request );

			$response['post'] = array(
				'title' => $post->post_title,
				'link'  => get_the_permalink( $post->ID ),
			);

			$response['list'] = MG_UPC_List_Controller::get_instance()->prepare_list_for_response(
				$list_before,
				array( 'context' => 'view' )
			);
		}

		$api_response = new WP_REST_Response();
		$api_response->set_data( $response );
		$api_response->set_status( $response['data'] );

		return $api_response;

	}

	/**
	 * Add an item to a list
	 *
	 * @param int                   $list_id
	 * @param int                   $post_id
	 * @param array|WP_REST_Request $request
	 *
	 * @return array|WP_Error
	 *
	 * @throws MG_UPC_Invalid_Field_Exception
	 * @throws MG_UPC_Item_Exist_Exception
	 * @throws MG_UPC_Item_Not_Found_Exception
	 */
	public static function add_tem_to_list( $list_id, $post_id, $request ) {
		/** @global $mg_upc User_Post_Collections */
		global $mg_upc;
		$model = $mg_upc->model;

		$list_before = MG_UPC_List_Controller::get_instance()->get_list( (int) $list_id );

		if ( is_wp_error( $list_before ) ) {
			return $list_before;
		}

		$list_type_obj = MG_UPC_Helper::get_instance()->get_list_type( $list_before->type );
		if ( false === $list_type_obj ) {
			return new WP_Error(
				'rest_unable_post_add',
				esc_html__( 'Unable to add items to this list.', 'user-post-collections' ),
				array( 'status' => MG_UPC_List_Controller::authorization_status_code() )
			);
		}

		if ( ! $list_type_obj->support( 'max_items_rotate' ) ) {
			if ( $list_type_obj->max_items <= $list_before->count ) {
				return new WP_Error(
					'rest_unable_post_add_max',
					esc_html__( 'Unable to add more items to this list.', 'user-post-collections' ),
					array( 'status' => MG_UPC_List_Controller::authorization_status_code() )
				);
			}
		}

		$post = MG_UPC_List_Controller::get_instance()->get_post_for_add( $post_id, $request );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		if ( ! self::check_add_permission( $post, $list_before ) ) {
			return new WP_Error(
				'rest_unable_post_add',
				esc_html__( 'Unable to add this post to list.', 'user-post-collections' ),
				array( 'status' => MG_UPC_List_Controller::authorization_status_code() )
			);
		}

		$to_save  = array(
			'list_id' => $list_id,
			'post_id' => $post_id,
		);
		$response = array( 'data' => array() );
		if (
			is_string( $request['description'] ) &&
			! empty( $request['description'] )
		) {
			if ( ! $list_type_obj->support( 'editable_item_description' ) ) {
				$response['code']           = 'rest_item_desc_error';
				$response['message']        = esc_html__(
					'This list dont support item description',
					'user-post-collections'
				);
				$response['data']['status'] = 409;
				$response['added']          = true;
			} else {
				$to_save['description']     = $request['description'];
				$response['data']['status'] = 201;
				$response['added']          = true;
			}
		} else {
			$response['data']['status'] = 201;
			$response['added']          = true;
		}
		if (
			isset( $request['quantity'] ) &&
			is_numeric( $request['quantity'] )
		) {
			if ( $list_type_obj->support( 'quantity' ) && 0 <= (int) $request['quantity'] ) {
				$to_save['quantity'] = $request['quantity'];
				if ( ! isset( $response['code'] ) ) {
					$response['data']['status'] = 201;
					$response['added']          = true;
				}
			}
		}

		if ( isset( $request['context'] ) && 'check' === $request['context'] ) {
			if ( ! empty( $response['added'] ) ) {
				$response['check'] = 'OK';
			} else {
				$response['check'] = 'ERR';
			}
		}

		/**
		 * Filter for item to save. If return WP_Error, the operation is canceled.
		 * @param array                 $to_save      The item to insert
		 * @param object                $list_before The list object
		 * @param array|WP_REST_Request $request     The list object
		 */
		$to_save = apply_filters( 'mg_upc_pre_add_item', $to_save, $list_before, $request );
		if ( is_wp_error( $to_save ) ) {
			return $to_save;
		}

		$model->items->add_item(
			$to_save['list_id'],
			$to_save['post_id'],
			isset( $to_save['description'] ) ? $to_save['description'] : '',
			isset( $to_save['quantity'] ) ? $to_save['quantity'] : 0
		);

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
					esc_html__( 'Item not found.', 'user-post-collections' ),
					array( 'status' => 404 )
				);
			}

			$list_type_obj = MG_UPC_Helper::get_instance()->get_list_type( $list->type );
			if ( false === $list_type_obj ) {
				return new WP_Error(
					'rest_unable_post_add_max',
					esc_html__( 'Unable to edit items in this list.', 'user-post-collections' ),
					array( 'status' => MG_UPC_List_Controller::authorization_status_code() )
				);
			}

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
						esc_html__( 'This list dont support item description', 'user-post-collections' ),
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

			if (
				isset( $request['quantity'] ) &&
				is_numeric( $request['quantity'] )
			) {

				if ( ! $list_type_obj->support( 'quantity' ) ) {
					return new WP_Error(
						'rest_item_desc_error',
						esc_html__( 'This list dont support item quantity', 'user-post-collections' ),
						array( 'status' => 409 )
					);
				}

				$this->model->items->update_item_quantity( $request['id'], $request['postid'], $request['quantity'] );
				$response->set_data(
					array(
						'update' => true,
					)
				);

				$item = $this->model->items->get_item( $request['id'], $request['postid'] );
				if ( ! empty( $item ) ) {
					$it_response = MG_UPC_List_Controller::get_instance()->prepare_item_for_response( $item, array() );
					$response->set_data(
						array(
							'update' => true,
							'item'   => $it_response,
						)
					);
				}
			}
		} catch ( Exception $e ) {
			return new WP_Error(
				'rest_db_error',
				esc_html( $e->getMessage() ),
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
	 *
	 * @noinspection PhpUnused (Rest API callback)
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
						esc_html__( 'Item not found.', 'user-post-collections' ),
						array( 'status' => 404 )
					);
				}
			}

			if ( ! $this->model->support( $request['id'], 'vote' ) ) {
				return new WP_Error(
					'rest_invalid_list_operation',
					esc_html__( 'This list dont support this operation.', 'user-post-collections' ),
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
				$list_type = MG_UPC_Helper::get_instance()->get_list_type( $list->type );
				if ( ! is_user_logged_in() && $list_type->vote_require_login() ) {
					$data['can_vote'] = true; // to show button and show required login on vote button
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

				$show_on_vote = MG_UPC_Helper::get_instance()->get_list_type_option(
					$list->type,
					'show_on_vote',
					'off'
				);
				if ( ! $show_on_vote || 0 !== $request['postid'] ) {
					$data['posts'] = $actual['items'];
				}
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
				esc_html( $e->getMessage() ),
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
	 *
	 * @noinspection PhpUnused (Rest API callback)
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
				esc_html( $e->getMessage() ),
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
		$items = MG_UPC_List_Controller::get_instance()->get_items( $request );

		if ( empty( $items ) ) {
			return rest_ensure_response( array() );
		}
		if ( is_wp_error( $items ) ) {
			return $items;
		}

		$response = array();
		foreach ( $items['items'] as $item ) {
			$response[] = $this->prepare_response_for_collection( $item );
		}

		// Return all of our comment response data.
		$rest_response = new WP_REST_Response( $response, 200 );
		$rest_response->header( 'X-WP-Total', $items['total'] );
		$rest_response->header( 'X-WP-TotalPages', $items['total_pages'] );
		$rest_response->header( 'X-WP-Page', $items['current'] );

		return $rest_response;
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
				'description' => esc_html__( 'The post id for add to the list.', 'user-post-collections' ),
			),
			'description' => array(
				'type'              => 'string',
				'maxLength'         => 400,
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
				'description'       => esc_html__( 'The item comment.', 'user-post-collections' ),
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
				'description' => esc_html__( 'Current page of the collection.', 'user-post-collections' ),
				'type'        => 'integer',
				'default'     => 1,
				'minimum'     => 1,
			),
			'per_page' => array(
				'description' => esc_html__(
					'Maximum number of items to be returned in result set.',
					'user-post-collections'
				),
				'type'        => 'integer',
				'default'     => (int) get_option( 'mg_upc_api_item_per_page', 12 ),
				'minimum'     => 1,
				'maximum'     => 100,
			),
		);

		$query_params['order'] = array(
			'description' => esc_html__(
				'Order sort attribute ascending or descending.',
				'user-post-collections'
			),
			'type'        => 'string',
			'default'     => 'desc',
			'enum'        => array( 'asc', 'desc' ),
		);

		$query_params['orderby'] = array(
			'description' => esc_html__( 'Sort collection by attribute.', 'user-post-collections' ),
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

	/**
	 * Get schema for a list.
	 *
	 * @return array The schema for a list
	 */
	public function get_item_schema() {
		if ( $this->schema ) {
			return $this->schema;
		}
		$this->schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			// The title property marks the identity of the resource.
			'title'      => 'listItem',
			'type'       => 'object',
			'properties' => self::get_item_properties_schema(),
		);

		return $this->schema;
	}

	public function activate( $network_wide ) { }

	public function deactivate() { }

	public function register_hook_callbacks() { }

	public function init() { }

	public function upgrade( $db_version = 0 ) { }

	public static function get_item_properties_schema() {
		$item_schema = array(
			'list_id'        => array(
				'description' => esc_html__( 'Unique identifier for the list.', 'user-post-collections' ),
				'type'        => 'integer',
				'readonly'    => true,
			),
			'post_id'        => array(
				'description' => esc_html__( 'Unique identifier for the post.', 'user-post-collections' ),
				'type'        => 'integer',
				'readonly'    => true,
			),
			'title'          => array(
				'description' => esc_html__( 'Item title.', 'user-post-collections' ),
				'type'        => 'string',
			),
			'description'    => array(
				'description' => esc_html__( 'Item description.', 'user-post-collections' ),
				'type'        => 'string',
			),
			'quantity'       => array(
				'description' => esc_html__(
					'Quantity of items (in cart type, the quantity for the product).',
					'user-post-collections'
				),
				'type'        => 'integer',
			),
			'position'       => array(
				'description' => esc_html__( 'Item position.', 'user-post-collections' ),
				'type'        => 'integer',
			),
			'votes'          => array(
				'description' => esc_html__( 'Item votes.', 'user-post-collections' ),
				'type'        => 'integer',
				'readonly'    => true,
			),
			'post_type'      => array(
				'description' => esc_html__( 'Post type for post_id.', 'user-post-collections' ),
				'type'        => 'string',
				'readonly'    => true,
			),
			'excerpt'        => array(
				'description' => esc_html__( 'Excerpt for post_id.', 'user-post-collections' ),
				'type'        => 'string',
				'readonly'    => true,
			),
			'featured_media' => array(
				'description' => esc_html__( 'Featured Media for post_id.', 'user-post-collections' ),
				'type'        => 'integer',
				'readonly'    => true,
			),
			'image'          => array(
				'description' => esc_html__( 'Image for post_id.', 'user-post-collections' ),
				'type'        => 'string',
				'readonly'    => true,
			),
			'link'           => array(
				'description' => esc_html__( 'Link for post_id.', 'user-post-collections' ),
				'type'        => 'string',
				'readonly'    => true,
			),
		);

		return apply_filters( 'mg_upc_api_schema_item', $item_schema );
	}

}
