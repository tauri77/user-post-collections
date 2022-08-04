<?php



class MG_UPC_List_Type implements ArrayAccess {

	public $name;

	public $label;

	public $plural_label;

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

	public $possible_statuses = array( 'publish', 'private' );

	public $available_statuses = array();

	public $public = true;

	/**
	 * @var bool If a single/archive can generate
	 */
	public $publicly_queryable = true;

	/**
	 * @var bool
	 */
	public $exclude_from_search = true;


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
	 * @var int $max_votes_per_user The max number of votes allowed for user
	 */
	private $max_votes_per_user = 1;

	/**
	 * @var int $max_votes_per_ip The max number of votes allowed per IP
	 */
	private $max_votes_per_ip = 5;

	/**
	 * @var bool $vote_require_login User require login for vote
	 */
	private $vote_require_login = true;

	/**
	 * The features that can be use
	 *
	 * @var array|bool $supports
	 */
	private $supported_features;

	/**
	 * The features that can be enable/disable by user.
	 *
	 * @var array|bool $supports
	 */
	private $configurable_features;

	/**
	 * The features supported by the list type.
	 *
	 * @var array|bool $supports
	 */
	public $supports;

	/**
	 * @var bool
	 */
	private $map_meta_cap;

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

		if (
			! empty( $args['supported_features'] ) &&
			in_array( 'show_in_settings', $args['supported_features'], true )
		) {
			$args = $this->complete_from_settings( $args );
		}

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
			'label'                 => '',
			'plural_label'          => '',
			'description'           => '',
			'default_status'        => 'private',
			'sticky'                => 0,
			'default_content'       => '',
			'default_title'         => false,
			'default_orderby'       => 'added',
			'default_order'         => 'asc',
			'enabled'               => true,
			'max_items'             => 50,
			'possible_statuses'     => array( 'publish', 'private' ),
			'available_statuses'    => null,
			'available_post_types'  => array( 'post' ),
			'public'                => true, // for public, ex: can show on widget? (determine publicly_queryable, exclude_from_search)
			'exclude_from_search'   => null,
			'publicly_queryable'    => null, // can show on single?
			'delete_with_user'      => true,
			'configurable_features' => array(
				'editable_title',
				'editable_content',
				'editable_item_description',
			),
			'supported_features'    => array(
				'editable_title',
				'editable_content',
				'editable_item_description',
				'show_in_my_lists',
				'show_in_settings',
			),
			'supports'              => null,
			'max_votes_per_user'    => 1,
			'max_votes_per_ip'      => 5,
			'vote_require_login'    => true,
			'capability_type'       => "mg_{$list_type}_collection",
			'capabilities'          => array(),
			'map_meta_cap'          => true,
		);

		$args = array_merge( $defaults, $args );

		$args['name'] = $this->name;

		// If not set, default to false.
		if ( null === $args['map_meta_cap'] ) {
			$args['map_meta_cap'] = false;
		}

		// If not set, set from possible statuses
		if ( null === $args['available_statuses'] ) {
			$args['available_statuses'] = $args['possible_statuses'];
		}

		// If not set, set from supported_features
		if ( null === $args['supports'] ) {
			$args['supports'] = $args['supported_features'];
		}
		// Set supports
		$enabled          = array_intersect( $args['supports'], $args['configurable_features'] );
		$required         = array_diff( $args['supported_features'], $args['configurable_features'] );
		$args['supports'] = array_values( array_merge( $enabled, $required ) );

		// If not set, default to public.
		if ( null === $args['publicly_queryable'] ) {
			$args['publicly_queryable'] = $args['public'];
		}

		// If not set, default to not public.
		if ( null === $args['exclude_from_search'] ) {
			$args['exclude_from_search'] = ! $args['public'];
		}

		// default title
		if ( false === $args['default_title'] ) {
			$args['default_title'] = $args['label'];
		}

		$this->cap = $this->get_list_type_capabilities( (object) $args );
		unset( $args['capabilities'] );

		if ( is_array( $args['capability_type'] ) ) {
			$args['capability_type'] = $args['capability_type'][0];
		}

		foreach ( $args as $property_name => $property_value ) {
			$this->$property_name = $property_value;
		}

		if ( ! $args['plural_label'] ) {
			$this->plural_label = $this->label . 's';
		}

		if ( $args['map_meta_cap'] ) {
			add_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 10, 4 );
		}

	}

	private function complete_from_settings( $args ) {

		$args = apply_filters( 'mg_upc_before_list_type_options_saved_set', $args, $this->name );

		$prefix = 'mg_upc_type_';
		$option = get_option( $prefix . $this->name, '' );

		if ( ! is_array( $option ) ) {
			return $args;
		}
		if ( isset( $option['enabled'] ) ) {
			$args['enabled'] = 'off' !== $option['enabled'];
		}
		if ( isset( $option['sticky'] ) && is_numeric( $option['sticky'] ) ) {
			$args['sticky'] = (int) $option['sticky'];
		}
		if ( ! empty( $option['label'] ) ) {
			$args['label'] = $option['label'];
		}
		if ( ! empty( $option['description'] ) ) {
			$args['description'] = $option['description'];
		}
		if ( ! empty( $option['max_items'] ) ) {
			$args['max_items'] = (int) $option['max_items'];
		}
		if ( isset( $option['available_post_types'] ) && is_array( $option['available_post_types'] ) ) {
			$args['available_post_types'] = $option['available_post_types'];
		}
		if ( ! empty( $option['default_orderby'] ) ) {
			$args['default_orderby'] = $option['default_orderby'];
		}
		if ( ! empty( $option['default_order'] ) ) {
			$args['default_order'] = $option['default_order'];
		}
		if ( ! empty( $option['default_status'] ) ) {
			$args['default_status'] = $option['default_status'];
		}
		if ( ! empty( $option['default_title'] ) ) {
			$args['default_title'] = $option['default_title'];
		}
		if ( ! empty( $option['available_statuses'] ) ) {
			$args['available_statuses'] = $option['available_statuses'];
		}
		if ( isset( $option['supports'] ) && is_array( $option['supports'] ) ) {
			$args['supports'] = $option['supports'];
		}
		if ( isset( $option['vote_require_login'] ) ) {
			$args['vote_require_login'] = 'on' === $option['vote_require_login'];
		}
		if ( isset( $option['max_votes_per_user'] ) ) {
			$args['max_votes_per_user'] = (int) $option['max_votes_per_user'];
		}
		if ( isset( $option['max_votes_per_ip'] ) ) {
			$args['max_votes_per_ip'] = (int) $option['max_votes_per_ip'];
		}
		return $args;
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
			try {
				$list = MG_List_Model::get_instance()->find_one( $list_id );
			} catch ( MG_UPC_Invalid_Field_Exception $e ) {
				$caps[] = 'do_not_allow';

				return $caps;
			}
			$author  = (int) $list->author;
			$user_id = (int) $user_id;
			$status  = MG_UPC_Helper::get_instance()->get_list_status( $list->status, true );
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
				if ( ! mg_upc_is_list_publicly_viewable( $list ) ) {
					if ( ! $status->private ) {
						$caps[] = 'read';
					} elseif ( $user_id === $author ) {
						$caps[] = 'read';
					} else {
						$caps[] = $this->cap->read_private_posts;
					}
				}
			}

			if ( 'vote_' . $type === $cap ) {
				$model          = MG_List_Model::get_instance();
				$user_max_votes = $this->get_max_votes_per_user();
				$ip_max_votes   = $this->get_max_votes_per_ip();

				try {
					if (
						! $this->support( 'vote' ) ||
						( $this->vote_require_login() && empty( $user_id ) ) ||
						( ! empty( $user_id ) && 0 !== $user_max_votes && $model->user_count_votes( $list_id, $user_id ) >= $user_max_votes ) ||
						0 !== $ip_max_votes && $model->ip_count_votes( $list_id ) >= $ip_max_votes
					) {
						$caps[] = 'do_not_allow';
					}
				} catch ( Exception $e ) {
					$caps[] = 'do_not_allow';

					return $caps;
				}

				if ( ! $status->private || $user_id === $author ) {
					if ( $this->vote_require_login() ) {
						$caps[] = 'read';
					}
				} else {
					$caps[] = $this->cap->read_private_posts;
				}
			}
		}

		return $caps;
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
			'vote'               => 'vote_' . $singular_base,
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

	public function get_configurable_features() {
		return array_intersect( $this->configurable_features, $this->supported_features );
	}

	public function get_default_config_features() {
		return array_intersect( $this->configurable_features, $this->supports );
	}

	public function delete_with_user() {
		return $this->delete_with_user;
	}

	public function get_cap() {
		return $this->cap;
	}

	public function get_max_votes_per_user() {
		return $this->max_votes_per_user;
	}

	public function vote_require_login() {
		return $this->vote_require_login;
	}

	public function get_max_votes_per_ip() {
		return $this->max_votes_per_ip;
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
			'show_in_settings',
			'sortable',
			'vote',
			'quantity',
		);

		if ( in_array( $offset, $supports, true ) ) {
			return $this->support( $offset );
		}

		return isset( $this->$offset ) ? $this->$offset : null;
	}

}
