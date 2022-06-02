<?php



class MG_UPC_List_Type implements ArrayAccess {

	public $name;

	public $label;

	public $description = '';

	public $enabled = true;

	public $sticky = 0;

	public $default_status = 'private';

	public $default_content = '';

	public $default_title = false;

	public $default_orderby = false;

	public $default_order = false;

	public $max_items = 100;

	public $available_post_types = array( 'post' );

	public $available_statuses = array( 'publish', 'private' );


	/**
	 * Whether to delete posts of this type when deleting a user.
	 *
	 * - If true, posts of this type belonging to the user will be deleted.
	 * - If false, posts of this type belonging to the user will not be deleted.
	 *
	 * Default null.
	 *
	 * @var bool $delete_with_user
	 */
	private $delete_with_user = true;

	/**
	 * List type capabilities.
	 *
	 * @var stdClass $cap
	 */
	private $cap;

	/**
	 * Used has suffix on capabilities
	 *
	 * @var string $capability_type
	 */
	private $capability_type = 'user_post_collection';

	/**
	 * The features supported by the list type.
	 *
	 * @var array|bool $supports
	 */
	public $supports;

	/**
	 * Constructor.
	 *
	 * See the register_list_type() function for accepted arguments for `$args`.
	 *
	 * Will populate object properties from the provided arguments and assign other
	 * default properties based on that information.
	 *
	 *
	 * @param string       $list_type List type key.
	 * @param array|string $args      Optional. Array or string of arguments for registering a list type.
	 *                                Default empty array.
	 */
	public function __construct( $list_type, $args = array() ) {
		$this->name = $list_type;

		$this->set_props( $args );
	}

	/**
	 * Sets list type properties.
	 *
	 * See the register_list_type() function for accepted arguments for `$args`.
	 *
	 * @since 4.6.0
	 *
	 * @param array|string $args Array or string of arguments for registering a list type.
	 */
	public function set_props( $args ) {
		$args = wp_parse_args( $args );

		/**
		 * Filters the arguments for registering a list type.
		 *
		 * @param array  $args      Array of arguments for registering a list type.
		 * @param string $list_type List type key.
		 */
		$args = apply_filters( 'register_list_type_args', $args, $this->name );

		$list_type = $this->name;

		/**
		 * Filters the arguments for registering a specific list type.
		 *
		 * The dynamic portion of the filter name, `$list_type`, refers to the list type key.
		 *
		 * Possible hook names include:
		 *
		 *  - `register_simple_list_type_args`
		 *  - `register_numbered_list_type_args`
		 *
		 * @param array  $args      Array of arguments for registering a list type.
		 * @param string $list_type List type key.
		 */
		$args = apply_filters( "register_{$list_type}_list_type_args", $args, $this->name );

		$defaults = array(
			'label'                => '',
			'description'          => '',
			'default_status'       => 'private',
			'sticky'               => false,
			'default_content'      => '',
			'default_title'        => false,
			'default_orderby'      => false,
			'default_order'        => false,
			'enabled'              => true,
			'max_items'            => 50,
			'available_statuses'   => array( 'publish', 'private' ),
			'available_post_types' => array( 'post' ),
			'delete_with_user'     => true,
			'supports'             => array(
				'editable_title',
				'editable_content',
				'editable_item_description',
				//'max_items_rotate',
				'show_in_my_lists',
				//'sortable',
				//'vote',
				//'always_exists', //this create an end point with bookmarks instead the ID
			),
			'capability_type'      => 'user_post_collection',
			'capabilities'         => array(),
			'map_meta_cap'         => true,
		);

		$args = array_merge( $defaults, $args );

		$args['name'] = $this->name;

		// If not set, default to false.
		if ( null === $args['map_meta_cap'] ) {
			$args['map_meta_cap'] = false;
		}

		$this->cap = $this->get_list_type_capabilities( (object) $args );
		unset( $args['capabilities'] );

		if ( is_array( $args['capability_type'] ) ) {
			$args['capability_type'] = $args['capability_type'][0];
		}

		foreach ( $args as $property_name => $property_value ) {
			$this->$property_name = $property_value;
		}

		$this->label = $args['label'];

		if ( $args['map_meta_cap'] ) {
			add_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 10, 4 );
		}

		add_filter( 'user_has_cap', array( $this, 'user_has_cap' ), 10, 3 );
	}

	public function map_meta_cap( $caps, $cap, $user_id, $args ) {
		$type = $this->capability_type;

		// Cap is a meta ?
		if (
			'vote_' . $type === $cap ||
			'edit_' . $type === $cap ||
			'delete_' . $type === $cap ||
			'read_' . $type === $cap
		) {

			$list_id = (int) $args[0];
			$list    = MG_List_Model::get_instance()->find_one( $list_id );
			$author  = (int) $list->author;
			$user_id = (int) $user_id;
			$status  = $list->status;
			// empty $caps
			$caps = array();

			if ( 'edit_' . $type === $cap ) {
				if ( (int) $user_id === (int) $author ) {
					$caps[] = $this->cap->edit_posts;
				} else {
					$caps[] = $this->cap->edit_others_posts;
				}
			}

			if ( 'delete_' . $type === $cap ) {
				if ( $user_id === $author ) {
					$caps[] = $this->cap->delete_posts;
				} else {
					$caps[] = $this->cap->delete_others_posts;
				}
			}

			if ( 'read_' . $type === $cap ) {
				if ( 'private' !== $status ) {
					$caps[] = 'read';
				} elseif ( $user_id ===  $author ) {
					$caps[] = 'read';
				} else {
					$caps[] = $this->cap->read_private_posts;
				}
			}

			if ( 'vote_' . $type === $cap ) {
				$model = MG_List_Model::get_instance();
				if (
					empty( $user_id ) ||
					! $model->support( $list_id, 'vote' ) ||
					$model->user_already_vote( $list_id, $user_id )
				) {
					$caps[] = 'do_not_allow';
				}
				if ( 'private' !== $status || $user_id === $author ) {
					$caps[] = 'read';
				} else {
					$caps[] = $this->cap->read_private_posts;
				}
			}
		}

		return $caps;
	}

	public function user_has_cap( $allcaps, $primitive_caps, $args ) {

		$requested = $this->get_core_cap( $args[0] );
		if ( $requested ) {
			foreach ( $primitive_caps as $primitive_cap ) {
				if ( 'edit_user_post_collections' === $primitive_cap && ! isset( $allcaps[ $primitive_cap ] ) ) {
					$allcaps[ $primitive_cap ] = ! empty( $args[1] );
					continue;
				}
				//copy from core equivalent
				$core = $this->get_core_cap( $primitive_cap );
				if ( isset( $allcaps[ $core ] ) ) {
					$allcaps[ $primitive_cap ] = $allcaps[ $core ];
				}
			}
		}

		return $allcaps;
	}

	private function get_core_cap( $cap_name ) {
		foreach ( $this->cap as $core => $custom ) {
			if ( $custom === $cap_name ) {
				return $core;
			}
		}
		return false;
	}

	private function get_list_type_capabilities( $args ) {

		if ( ! is_array( $args->capability_type ) ) {
			$args->capability_type = array( $args->capability_type, $args->capability_type . 's' );
		}

		// Singular base for meta capabilities, plural base for primitive capabilities.
		list( $singular_base, $plural_base ) = $args->capability_type;

		$default_capabilities = array(
			// Meta capabilities.
			'edit_post'          => 'edit_' . $singular_base,
			'read_post'          => 'read_' . $singular_base,
			'delete_post'        => 'delete_' . $singular_base,
			// Primitive capabilities used outside of map_meta_cap():
			'edit_posts'         => 'edit_' . $plural_base,
			'edit_others_posts'  => 'edit_others_' . $plural_base,
			'delete_posts'       => 'delete_' . $plural_base,
			'publish_posts'      => 'publish_' . $plural_base,
			'read_private_posts' => 'read_private_' . $plural_base,
		);

		// Primitive capabilities used within map_meta_cap():
		if ( $args->map_meta_cap ) {
			$default_capabilities_for_mapping = array(
				'read'                   => 'read',
				'delete_private_posts'   => 'delete_private_' . $plural_base,
				'delete_published_posts' => 'delete_published_' . $plural_base,
				'delete_others_posts'    => 'delete_others_' . $plural_base,
				'edit_private_posts'     => 'edit_private_' . $plural_base,
				'edit_published_posts'   => 'edit_published_' . $plural_base,
			);
			$default_capabilities             = array_merge( $default_capabilities, $default_capabilities_for_mapping );
		}

		$capabilities = array_merge( $default_capabilities, $args->capabilities );

		// Post creation capability simply maps to edit_posts by default:
		if ( ! isset( $capabilities['create_posts'] ) ) {
			$capabilities['create_posts'] = $capabilities['edit_posts'];
		}

		return (object) $capabilities;
	}

	public function support( $feature ) {
		return in_array( $feature, $this->supports, true );
	}

	public function delete_with_user() {
		return $this->delete_with_user;
	}

	public function get_cap() {
		return $this->cap;
	}







	public function offsetSet( $offset, $valor ) {
		//No set as array...
	}

	public function offsetExists( $offset ) {
		return isset( $this->$offset );
	}

	public function offsetUnset( $offset ) {
		unset( $this->$offset );
	}

	public function offsetGet( $offset ) {

		$supports = array(
			'editable_title',
			'editable_content',
			'editable_item_description',
			'max_items_rotate',
			'show_in_my_lists',
			'always_exists',
		);

		if ( in_array( $offset, $supports, true ) ) {
			return $this->support( $offset );
		}

		return isset( $this->$offset ) ? $this->$offset : null;
	}

}
