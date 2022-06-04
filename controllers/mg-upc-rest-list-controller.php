<?php
/** @noinspection PhpUnused */

class MG_UPC_REST_Lists_Controller {
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
	 * MG_UPC_REST_Lists_Controller constructor.
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

	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->resource_name,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_lists' ),
					'permission_callback' => array( $this, 'get_lists_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_list' ),
					'permission_callback' => array( $this, 'create_list_permissions_check' ),
					'args'                => $this->get_write_params( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_list_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->resource_name . '/My',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_lists_my' ),
					'permission_callback' => array( $this, 'get_lists_my_permissions_check' ),
					'args'                => array(
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
							'default'           => 10,
							'minimum'           => 1,
							'maximum'           => 100,
							'sanitize_callback' => 'absint',
							'validate_callback' => 'rest_validate_request_arg',
						),
						'adding'   => array(
							'description'       => __( 'Show only list that can add this post.', 'user-post-collections' ),
							'type'              => 'integer',
							'minimum'           => 1,
							'sanitize_callback' => 'absint',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
			)
		);

		$exist_list_types = MG_UPC_Helper::get_instance()->get_always_exist_list_types();
		foreach ( $exist_list_types as $exist_list_type ) {
			register_rest_route(
				$this->namespace,
				'/' . $this->resource_name . '/(?P<type>' . $exist_list_type . ')',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_list_always_exist' ),
						'permission_callback' => array( $this, 'get_list_permissions_check_always_exist' ),
						'args'                => array(
							'context' => array(
								'description'       => __( 'Scope under which the request is made; determines fields present in response.', 'user-post-collections' ),
								'type'              => 'string',
								'default'           => 'view',
								'sanitize_callback' => 'sanitize_key',
								'validate_callback' => 'rest_validate_request_arg',
							),
						),
					),
					'schema' => array( $this, 'get_list_schema' ),
				)
			);
		}

		register_rest_route(
			$this->namespace,
			'/' . $this->resource_name . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_list' ),
					'permission_callback' => array( $this, 'get_list_permissions_check' ),
					'args'                => array(
						'context' => array(
							'description'       => __( 'Scope under which the request is made; determines fields present in response.', 'user-post-collections' ),
							'type'              => 'string',
							'default'           => 'view',
							'sanitize_callback' => 'sanitize_key',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_list' ),
					'permission_callback' => array( $this, 'update_list_permissions_check' ),
					'args'                => $this->get_write_params( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_list' ),
					'permission_callback' => array( $this, 'delete_list_permissions_check' ),
					'args'                => array(),
				),
				'schema' => array( $this, 'get_list_schema' ),
			)
		);
	}

	/**
	 * Check permissions for the lists.
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return bool|WP_Error
	 */
	public function get_list_permissions_check( $request ) {
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
	 * Check permissions for the always exist lists.
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return bool|WP_Error
	 */
	public function get_list_permissions_check_always_exist( $request ) {
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
	 * Check permissions for "my lists".
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return bool|WP_Error
	 */
	public function get_lists_my_permissions_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'required_logged_in',
				esc_html__( 'Required logged in.', 'user-post-collections' ),
				array( 'status' => $this->authorization_status_code() )
			);
		}

		return true;
	}

	/**
	 * Check permissions for get list.
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return bool
	 */
	public function get_lists_permissions_check( $request ) {
		return true;
	}

	/**
	 * Check permissions for create a list.
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return bool|WP_Error
	 */
	public function create_list_permissions_check( $request ) {

		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'required_logged_in',
				esc_html__( 'Required logged in.', 'user-post-collections' ),
				array( 'status' => $this->authorization_status_code() )
			);
		}

		if ( ! MG_UPC_List_Controller::get_instance()->can_create( $request['type'] ) ) {
			return new WP_Error(
				'rest_cannot_edit_others',
				__( 'Sorry, you are not allowed to create this list.', 'user-post-collections' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if (
			! empty( $request['author'] ) &&
			get_current_user_id() !== $request['author'] &&
			! MG_UPC_List_Controller::get_instance()->can_edit_others( $request['type'] )
		) {
			return new WP_Error(
				'rest_cannot_edit_others',
				__( 'Sorry, you are not allowed to create list as this user.', 'user-post-collections' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Checks if a given request has access to update a post.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has access to update the item, WP_Error object otherwise.
	 */
	public function update_list_permissions_check( $request ) {

		$list = MG_UPC_List_Controller::get_instance()->get_list( (int) $request['id'] );

		if ( is_wp_error( $list ) ) {
			return $list;
		}

		if ( ! MG_UPC_List_Controller::get_instance()->can_edit( (int) $request['id'] ) ) {
			return new WP_Error(
				'rest_cannot_edit',
				__( 'Sorry, you are not allowed to edit this post.', 'user-post-collections' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	public function delete_list_permissions_check( $request ) {

		$list = MG_UPC_List_Controller::get_instance()->get_list( (int) $request['id'] );

		if ( is_wp_error( $list ) ) {
			return $list;
		}

		if ( ! MG_UPC_List_Controller::get_instance()->can_delete( (int) $request['id'] ) ) {
			return new WP_Error(
				'rest_cannot_edit',
				__( 'Sorry, you are not allowed to delete this entry.', 'user-post-collections' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Create a list
	 *
	 * @param WP_REST_Request $request Current request
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function create_list( $request ) {

		if ( ! empty( $request['id'] ) ) {
			return new WP_Error(
				'rest_list_exists',
				__( 'Cannot create existing list.', 'user-post-collections' ),
				array( 'status' => 400 )
			);
		}

		$prepared_list = $this->prepare_collection_for_database( $request );
		if ( is_wp_error( $prepared_list ) ) {
			return $prepared_list;
		}

		try {
			$new_id = $this->model->create( (array) $prepared_list );
			$list   = $this->model->find_one( (int) $new_id );
		} catch ( Exception $e ) {
			return new WP_Error(
				'rest_db_error',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		}

		$request['ID'] = $new_id;

		if ( isset( $request['adding'] ) ) {
			$post = MG_UPC_List_Controller::get_instance()->get_post_for_add( $request['adding'], $request );
			if ( is_wp_error( $post ) ) {
				return $post;
			}

			if ( ! MG_UPC_REST_List_Items_Controller::check_add_permission( $post, $list ) ) {
				return new WP_Error(
					'rest_unable_post_add',
					__( 'Unable to add this post to list.', 'user-post-collections' ),
					array( 'status' => 403 )
				);
			}
			try {
				$this->model->items->add_item( $list->ID, $post->ID );
			} catch ( MG_UPC_Item_Exist_Exception $e ) {
				return new WP_Error(
					'rest_item_exist_error',
					$e->getMessage(),
					array( 'status' => 409 )
				);
			} catch ( Exception $e ) {
				return new WP_Error(
					'rest_db_error',
					$e->getMessage(),
					array( 'status' => 500 )
				);
			}
		}

		if ( empty( $list ) ) {
			return rest_ensure_response( array() );
		}

		$request['context'] = 'view';

		$response = $this->prepare_list_for_response( $list, $request );
		$response = rest_ensure_response( $response );

		$response->set_status( 201 );
		$response->header(
			'Location',
			rest_url(
				sprintf( '%s/%s/%d', $this->namespace, $this->resource_name, $new_id )
			)
		);

		return $response;
	}

	/**
	 * Updates a single list.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_list( $request ) {

		$list_before = MG_UPC_List_Controller::get_instance()->get_list( (int) $request['id'] );

		if ( is_wp_error( $list_before ) ) {
			return $list_before;
		}

		$prepared_list = $this->prepare_collection_for_database( $request );

		if ( is_wp_error( $prepared_list ) ) {
			return $prepared_list;
		}

		try {
			$this->model->update( (array) $prepared_list );
		} catch ( Exception $e ) {
			return new WP_Error(
				'rest_db_error',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		}

		$list = MG_UPC_List_Controller::get_instance()->get_list( (int) $request['id'] );

		if ( is_wp_error( $list ) ) {
			return $list;
		}

		$response = $this->prepare_list_for_response( $list, $request );
		$response = rest_ensure_response( $response );

		return $response;
	}

	/**
	 * Delete a single list.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_list( $request ) {

		$list_before = MG_UPC_List_Controller::get_instance()->get_list( (int) $request['id'] );

		if ( is_wp_error( $list_before ) ) {
			return $list_before;
		}

		$previous = $this->prepare_list_for_response( $list_before, $request );

		$result = $this->model->delete( $request['id'] );

		if ( ! $result ) {
			return new WP_Error(
				'rest_cannot_delete',
				__( 'The list cannot be deleted.', 'user-post-collections' ),
				array( 'status' => 500 )
			);
		}

		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted'  => true,
				'previous' => $previous->get_data(),
			)
		);

		return $response;
	}

	/**
	 * Prepare a list for database
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return stdClass|WP_Error
	 */
	protected function prepare_collection_for_database( $request ) {
		$prepared_list  = new stdClass();
		$current_status = '';

		// ID.
		if ( isset( $request['id'] ) ) {

			$existing_list = MG_UPC_List_Controller::get_instance()->get_list( (int) $request['id'] );

			if ( is_wp_error( $existing_list ) ) {
				return $existing_list;
			}

			$prepared_list->ID   = $existing_list->ID;
			$prepared_list->type = $existing_list->type;
			$current_status      = $existing_list->status;

			$list_type = MG_UPC_Helper::get_instance()->get_list_type( $existing_list->type, true );

			if ( ! $list_type['editable_content'] && isset( $request['content'] ) ) {
				return new WP_Error(
					'rest_cannot_edit_content',
					__( 'This list cant change content.', 'user-post-collections' ),
					array( 'status' => 400 )
				);
			}
			if ( ! $list_type['editable_title'] && isset( $request['title'] ) ) {
				return new WP_Error(
					'rest_cannot_edit_title',
					__( 'This list cant change title.', 'user-post-collections' ),
					array( 'status' => 400 )
				);
			}
		} else {
			$prepared_list->type = $request['type'];

			$list_type = MG_UPC_Helper::get_instance()->get_list_type( $request['type'], false );

			if ( false !== $list_type['default_title'] ) {
				$prepared_list->title = $list_type['default_title'];
			} elseif ( false === $list_type['editable_title'] ) {
				$prepared_list->title = $list_type['label'];
			}
			if ( false !== $list_type['default_content'] ) {
				$prepared_list->content = ! $list_type['default_content'] ? $list_type['default_content'] : '';
			}
		}

		//Title
		if ( $list_type['editable_title'] && isset( $request['title'] ) ) {
			if ( is_string( $request['title'] ) ) {
				$prepared_list->title = $request['title'];
			} elseif ( ! empty( $request['title']['raw'] ) ) {
				$prepared_list->title = $request['title']['raw'];
			}
		}

		//content
		if ( $list_type['editable_content'] && isset( $request['content'] ) ) {
			if ( is_string( $request['content'] ) ) {
				$prepared_list->content = $request['content'];
			} elseif ( isset( $request['content']['raw'] ) ) {
				$prepared_list->content = $request['content']['raw'];
			}
		}

		//author
		if ( ! empty( $request['author'] ) ) {
			$post_author = (int) $request['author'];

			if ( get_current_user_id() !== $post_author ) {
				$user_obj = get_userdata( $post_author );

				if ( ! $user_obj ) {
					return new WP_Error(
						'rest_invalid_author',
						__( 'Invalid author ID.', 'user-post-collections' ),
						array( 'status' => 400 )
					);
				}
			}

			$prepared_list->author = $post_author;
		} else {
			$prepared_list->author = get_current_user_id();
		}

		//status
		if (
			isset( $request['status'] ) &&
			( ! $current_status || $current_status !== $request['status'] )
		) {
			if ( ! in_array( $request['status'], $list_type['available_statuses'], true ) ) {
				return new WP_Error(
					'rest_invalid_status',
					__( 'Invalid status.', 'user-post-collections' ),
					array( 'status' => 400 )
				);
			}

			$prepared_list->status = $request['status'];
		}

		return $prepared_list;
	}

	/**
	 * Get a list of collections
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function get_lists( $request ) {

		$args = array(
			'limit'  => $request['per_page'],
			'page'   => $request['page'],
			'status' => 'publish',
		);

		if ( ! empty( $request['author'] ) ) {
			$args['author'] = $request['author'];
		}

		if ( ! empty( $request['author'] ) ) {
			$args['author'] = $request['author'];
		}

		return $this->process_lists( $args, $request );
	}

	/**
	 * Get user "my list"
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function get_lists_my( $request ) {

		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'Required logged in.', 'user-post-collections' ),
				array( 'status' => $this->authorization_status_code() )
			);
		}

		$args = array(
			'limit'  => $request['per_page'],
			'page'   => $request['page'],
			'status' => '*',
			'author' => get_current_user_id(),
		);

		$lists = MG_UPC_List_Controller::get_instance()->get_user_lists( $args, $request );

		if ( empty( $lists ) || is_wp_error( $lists ) ) {
			return rest_ensure_response( $lists );
		}

		$data = array();
		foreach ( $lists['results'] as $list ) {
			$response = $this->prepare_list_for_response( $list, $request );
			$data[]   = $this->prepare_response_for_collection( $response );
		}

		// Return all of our comment response data.
		$rest_response = new WP_REST_Response( $data, 200 );
		if ( isset( $lists['addingPost'] ) ) {
			if ( $lists['addingPost'] instanceof WP_Post ) {
				// or add size 'medium'
				$rest_response->header( 'X-WP-Post-Image', get_the_post_thumbnail_url( $lists['addingPost']->ID ) );
				$rest_response->header( 'X-WP-Post-Title', $lists['addingPost']->post_title );
				$rest_response->header( 'X-WP-Post-Type', $lists['addingPost']->post_type );
			}
		}
		$rest_response->header( 'X-WP-Total', $lists['total'] );
		$rest_response->header( 'X-WP-Page', $lists['current'] );
		$rest_response->header( 'X-WP-TotalPages', $lists['total_pages'] );
		return $rest_response;
	}

	/**
	 * Find lists
	 *
	 * @param $args
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	private function process_lists( $args, $request ) {
		try {
			$lists = $this->model->find( $args );
		} catch ( MG_UPC_Invalid_Field_Exception $e ) {
			return new WP_Error(
				'rest_db_error',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		}

		$data = array();

		if ( empty( $lists ) ) {
			return rest_ensure_response( $data );
		}

		foreach ( $lists['results'] as $post ) {
			$response = $this->prepare_list_for_response( $post, $request );
			$data[]   = $this->prepare_response_for_collection( $response );
		}

		// Return all of our comment response data.
		$rest_response = new WP_REST_Response( $data, 200 );
		$rest_response->header( 'X-WP-Total', $lists['total'] );
		$rest_response->header( 'X-WP-Page', $lists['current'] );
		$rest_response->header( 'X-WP-TotalPages', $lists['total_pages'] );

		return $rest_response;
	}

	/**
	 * Get a collection
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function get_list( $request ) {
		$id = (int) $request['id'];

		// Return all of our post response data.
		return $this->process_list( $id, $request );
	}

	/**
	 * Get a list from type
	 *
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function get_list_always_exist( $request ) {
		try {
			$list = $this->model->find_always_exist( $request['type'], get_current_user_id() );
		} catch ( MG_UPC_Invalid_Field_Exception $e ) {
			return new WP_Error(
				'rest_db_error',
				$e->getMessage(),
				array( 'status' => 500 )
			);
		}
		if ( null === $list ) {
			try {
				$list_id = $this->model->create( array( 'type' => $request['type'] ) );
			} catch ( Exception $e ) {
				return new WP_Error(
					'rest_db_error',
					$e->getMessage(),
					array( 'status' => 500 )
				);
			}
			$request['id'] = $list_id;
		} else {
			$request['id'] = $list->ID;
		}

		$id = (int) $request['id'];

		// Return all of our post response data.
		return $this->process_list( $id, $request );
	}

	/**
	 * Get an specified collection
	 *
	 * @param int             $id      List id
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
	 */
	public function process_list( $id, $request ) {

		$list = MG_UPC_List_Controller::get_instance()->get_list(
			array(
				'id'                      => (int) $id,
				'exclude_not_found_error' => true,
			)
		);

		if ( is_wp_error( $list ) ) {
			return $list;
		}

		if ( empty( $list ) ) {
			return rest_ensure_response( array() );
		}

		// Return all of our list response data.
		return $this->prepare_list_for_response( $list, $request );
	}

	/**
	 * Prepare a UPC for response
	 *
	 * @param object          $list    The list
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return WP_HTTP_Response|WP_REST_Response|WP_Error
	 */
	private function prepare_list_for_response( $list, $request ) {
		$response = MG_UPC_List_Controller::get_instance()->prepare_list_for_response( $list, $request );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response = rest_ensure_response( $response );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$links = $this->prepare_list_links( $list );
		$response->add_links( $links );

		return apply_filters( 'prepare_list_for_rest_response', $response, $list, $request );
	}

	/**
	 * Prepare collection links
	 *
	 * @param object $list
	 *
	 * @return array
	 */
	private function prepare_list_links( $list ) {
		$base = sprintf( '%s/%s', $this->namespace, $this->resource_name );

		$links = array(
			'self'       => array(
				'href' => rest_url( trailingslashit( $base ) . $list->ID ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
			//list items
			'items'      => array(
				'href'       => rest_url( trailingslashit( $base ) . $list->ID . '/items' ),
				'embeddable' => true,
			),
		);

		$links['author'] = array(
			'href'       => rest_url( 'wp/v2/users/' . $list->author ),
			'embeddable' => true,
		);

		return $links;
	}

	/**
	 * Prepare a response for inserting into a collection of responses. (list of lists)
	 *
	 * This is copied from WP_REST_Controller class in the WP REST API v2 plugin.
	 *
	 * @param array|WP_REST_Response $response Response object, is is array this not change.
	 *
	 * @return array Response data, ready for insertion into collection data.
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
	 * Get schema for a list.
	 *
	 * @return array The schema for a list
	 */
	public function get_list_schema() {
		if ( $this->schema ) {
			return $this->schema;
		}
		//TODO complete this
		$this->schema = array(
			// This tells the spec of JSON Schema we are using which is draft 4.
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			// The title property marks the identity of the resource.
			'title'      => 'list',
			'type'       => 'object',
			// In JSON Schema you can specify object properties in the properties attribute.
			'properties' => array(
				'id'      => array(
					'description' => esc_html__( 'Unique identifier for the object.', 'user-post-collections' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'content' => array(
					'description' => esc_html__( 'The content for the object.', 'user-post-collections' ),
					'type'        => 'string',
				),
			),
		);

		return $this->schema;
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
	 * Get params configuration for create and edit a list
	 *
	 * @param bool|string $type (Optional) Type of operation. EDITABLE or CREATABLE
	 *
	 * @return array
	 */
	public function get_write_params( $type = false ) {
		$query_params = array();

		if ( WP_REST_Server::EDITABLE === $type ) {
			$query_params['id'] = array(
				'description'       => __( 'The list id', 'user-post-collections' ),
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => function ( $val ) {
					return intval( $val ); },
			);
		}

		$query_params['author'] = array(
			'description' => __( 'The user_id author. (only admin can set)', 'user-post-collections' ),
			'type'        => 'integer',
		);

		$query_params['title'] = array(
			'description'       => __( 'List title.', 'user-post-collections' ),
			'type'              => 'string',
			'required'          => WP_REST_Server::CREATABLE === $type,
			'maxlength'         => 100,
			'minlength'         => 3,
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => array( 'MG_UPC_REST_Lists_Controller', 'string_validate_callback' ),
		);

		$query_params['content'] = array(
			'description'       => __( 'List text description.', 'user-post-collections' ),
			'type'              => 'string',
			'required'          => false,
			'maxlength'         => 500,
			'sanitize_callback' => array( MG_UPC_List_Controller::get_instance(), 'sanitize_content' ),
			'validate_callback' => array( 'MG_UPC_REST_Lists_Controller', 'string_validate_callback' ),
		);

		if ( WP_REST_Server::CREATABLE === $type ) {
			$query_params['type']   = array(
				'description'       => __( 'List type.', 'user-post-collections' ),
				'type'              => 'string',
				'required'          => true,
				'enum'              => $this->model->valid_types( true ),
				'validate_callback' => array( 'MG_UPC_REST_Lists_Controller', 'string_validate_callback' ),
			);
			$query_params['adding'] = array(
				'description'       => __( 'Create list, and add a post (postID).', 'user-post-collections' ),
				'type'              => 'integer',
				'enum'              => $this->model->valid_types( true ),
				'sanitize_callback' => 'absint',
			);
		}

		$query_params['status'] = array(
			'description'       => __( 'List status.', 'user-post-collections' ),
			'type'              => 'string',
			'required'          => WP_REST_Server::CREATABLE === $type,
			'enum'              => $this->model->valid_status( false ),
			'validate_callback' => array( 'MG_UPC_REST_Lists_Controller', 'string_validate_callback' ),
		);

		return $query_params;
	}

	/**
	 * Get params configuration for get a list of list (a collection of list)
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$query_params = array(
			'context'  => array(
				'description'       => __( 'Scope under which the request is made; determines fields present in response.', 'user-post-collections' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => 'rest_validate_request_arg',
			),
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
				'default'           => 10,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'search'   => array(
				'description'       => __( 'Limit results to those matching a string.', 'user-post-collections' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);

		$query_params['context']['default'] = 'view';

		$query_params['after'] = array(
			'description' => __( 'Limit response to posts published after a given ISO8601 compliant date.', 'user-post-collections' ),
			'type'        => 'string',
			'format'      => 'date-time',
		);

		$query_params['modified_after'] = array(
			'description' => __( 'Limit response to posts modified after a given ISO8601 compliant date.', 'user-post-collections' ),
			'type'        => 'string',
			'format'      => 'date-time',
		);

		$query_params['author'] = array(
			'description' => __( 'Limit result set to posts assigned to specific authors.', 'user-post-collections' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
			'default'     => array(),
		);

		$query_params['before'] = array(
			'description' => __( 'Limit response to posts published before a given ISO8601 compliant date.', 'user-post-collections' ),
			'type'        => 'string',
			'format'      => 'date-time',
		);

		$query_params['modified_before'] = array(
			'description' => __( 'Limit response to posts modified before a given ISO8601 compliant date.', 'user-post-collections' ),
			'type'        => 'string',
			'format'      => 'date-time',
		);

		$query_params['offset'] = array(
			'description' => __( 'Offset the result set by a specific number of items.', 'user-post-collections' ),
			'type'        => 'integer',
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
			'default'     => 'date',
			'enum'        => array(
				'author',
				'date',
				'id',
				'modified',
				'relevance',
				'slug',
				'title',
			),
		);

		$query_params['slug'] = array(
			'description'       => __( 'Limit result set to posts with one or more specific slugs.', 'user-post-collections' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'string',
			),
			'sanitize_callback' => 'wp_parse_slug_list',
		);

		$query_params['status'] = array(
			'default'           => 'publish',
			'description'       => __( 'Limit result set to posts assigned one or more statuses.', 'user-post-collections' ),
			'type'              => 'array',
			'items'             => array(
				'enum' => array_merge( $this->model->valid_types(), array( 'any' ) ),
				'type' => 'string',
			),
			'sanitize_callback' => array( $this, 'sanitize_post_statuses' ),
		);

		return apply_filters( 'rest_mg_upc_lists_collection_params', $query_params );
	}

	/**
	 * Validate string using param config
	 *
	 * @param string          $value   Value
	 * @param WP_REST_Request $request Current request.
	 * @param string          $key     The param key
	 *
	 * @return bool|WP_Error
	 */
	public static function string_validate_callback( $value, $request, $key ) {

		// If the 'filter' argument is not a string return an error.
		if ( ! is_string( $value ) ) {
			return new WP_Error(
				'rest_invalid_param',
				esc_html__(
					'The filter argument must be a string.',
					'my-text-domain'
				),
				array( 'status' => 400 )
			);
		}

		// Get the registered attributes for this endpoint request.
		$attributes = $request->get_attributes();

		// Grab the filter param schema.
		$args = $attributes['args'][ $key ];

		if ( isset( $args['enum'] ) ) {
			if ( ! in_array( $value, $args['enum'], true ) ) {
				return new WP_Error(
					'rest_invalid_param',
					sprintf(
						// translators: %1$s is param name, %2$s is a list of allowed options
						__( '%1$s is not one of %2$s', 'user-post-collections' ),
						$key,
						implode( ', ', $args['enum'] )
					),
					array( 'status' => 400 )
				);
			}
		}

		if ( isset( $args['maxlength'] ) ) {
			if ( mg_upc_strlen( $value ) > $args['maxlength'] ) {
				return new WP_Error(
					'rest_invalid_param',
					sprintf(
						// translators: %1$s is param name, %2$s is the max length ( a number )
						__( '%1$s max length: %2$d', 'user-post-collections' ),
						$key,
						$args['maxlength'],
						array( 'status' => 400 )
					)
				);
			}
		}

		if ( isset( $args['minlength'] ) ) {
			if ( mg_upc_strlen( $value ) < $args['minlength'] ) {
				return new WP_Error(
					'rest_invalid_param',
					sprintf(
					// translators: %1$s is param name, %2$s is the min length ( a number )
						__( '%1$s min length: %2$d', 'user-post-collections' ),
						$key,
						$args['minlength'],
						array( 'status' => 400 )
					)
				);
			}
		}

		return true;
	}

	/**
	 * Sanitize status using param config
	 *
	 * @param string $statuses Value
	 * @param WP_REST_Request $request Current request.
	 * @param string $parameter The param key
	 *
	 * @return string[]|WP_Error
	 */
	public function sanitize_post_statuses( $statuses, WP_REST_Request $request, $parameter ) {

		$statuses = wp_parse_slug_list( $statuses );

		// The default status is different in WP_REST_Attachments_Controller.
		$attributes     = $request->get_attributes();
		$default_status = $attributes['args']['status']['default'];

		$valid_status = $this->model->valid_status();

		foreach ( $statuses as $status ) {
			if ( $status === $default_status ) {
				continue;
			}

			if ( in_array( $status, $valid_status, true ) ) {
				$result = rest_validate_request_arg( $status, $request, $parameter );
				if ( is_wp_error( $result ) ) {
					return $result;
				}
			} else {
				return new WP_Error(
					'rest_forbidden_status',
					__( 'Status is forbidden.', 'user-post-collections' ),
					array( 'status' => rest_authorization_required_code() )
				);
			}
		}

		return $statuses;
	}
}
