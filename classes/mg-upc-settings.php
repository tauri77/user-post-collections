<?php

if ( ! class_exists( 'MG_UPC_Settings' ) ) {

	class MG_UPC_Settings extends MG_UPC_Module {

		const REQUIRED_CAPABILITY = 'manage_options';

		private $settings_api;

		/*
		 * General methods
		 */

		/**
		 * Constructor
		 */
		protected function __construct() {
			if ( is_admin() ) {
				$this->settings_api = new MG_UPC_Settings_API();
				$this->register_hook_callbacks();
			}
		}

		/**
		 * Initializes variables
		 */
		public function init() { }

		/**
		 * Register callbacks for actions and filters
		 */
		public function register_hook_callbacks() {

			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
			add_action( 'admin_init', array( $this, 'admin_init' ) );

			add_filter(
				'plugin_action_links_' . plugin_basename( dirname( __DIR__ ) ) . '/user-post-collections.php',
				__CLASS__ . '::add_plugin_action_links'
			);
		}

		/**
		 * Adds links to the plugin's action link section on the Plugins page
		 *
		 * @param array $links The links currently mapped to the plugin
		 * @return array
		 */
		public static function add_plugin_action_links( $links ) {
			array_unshift( $links, '<a href="options-general.php?page=mg_upc_settings">Settings</a>' );

			return $links;
		}

		/**
		 * Prepares site to use the plugin during activation
		 *
		 * @param bool $network_wide
		 */
		public function activate( $network_wide ) {
		}

		/**
		 * Rolls back activation procedures when de-activating the plugin
		 */
		public function deactivate() {
		}

		/**
		 * Executes the logic of upgrading from specific older versions of the plugin to the current version
		 *
		 * @mvc Model
		 *
		 * @param string $db_version
		 */
		public function upgrade( $db_version = 0 ) {

		}

		/**
		 * Adds pages to the Admin Panel menu
		 */
		public function admin_menu() {
			$this->settings_api->page_ref = add_submenu_page(
				'options-general.php',
				__( 'User Post Collections Settings', 'user-post-collections' ),
				__( 'User Post Collections', 'user-post-collections' ),
				self::REQUIRED_CAPABILITY,
				'mg_upc_settings',
				array( $this, 'plugin_page' )
			);
		}

		/**
		 * Registers settings sections, fields and settings
		 */
		public function admin_init() {

			if ( ! current_user_can( self::REQUIRED_CAPABILITY ) ) {
				return;
			}

			//set the settings
			$this->settings_api->set_sections( $this->get_settings_sections() );
			$this->settings_api->set_fields( $this->get_settings_fields() );

			//initialize settings
			$this->settings_api->admin_init();
		}

		private function get_settings_sections() {
			$sections = array(
				array(
					'id'       => 'mg_upc_general',
					'title'    => __( 'General Settings', 'user-post-collections' ),
					'as_array' => false,
				),
				array(
					'id'       => 'mg_upc_advanced',
					'title'    => __( 'Advanced Settings', 'user-post-collections' ),
					'as_array' => false,
				),
			);

			$prefix     = 'mg_upc_type_';
			$list_types = MG_UPC_Helper::get_instance()->get_list_types( true );
			foreach ( $list_types as $list_type ) {
				if ( ! $list_type->support( 'show_in_settings' ) ) {
					continue;
				}
				$sections[] = array(
					'id'       => $prefix . $list_type->name,
					'title'    => $list_type->plural_label,
					'as_array' => true,
				);
			}

			return apply_filters( 'mg_upc_settings_sections', $sections );
		}

		/**
		 * Returns all the settings fields
		 *
		 * @return array settings fields
		 */
		private function get_settings_fields() {
			$settings_fields = array();

			$settings_fields['mg_upc_general'] = array();

			$settings_fields['mg_upc_general'][] = array(
				'name'    => 'mg_upc_button_position',
				'label'   => __( 'General button position', 'user-post-collections' ),
				'desc'    => __( 'Where the "Add to list" button will be inserted', 'user-post-collections' ),
				'default' => 'end',
				'type'    => 'radio',
				'options' => array(
					'begin' => __( 'Begin of content', 'user-post-collections' ),
					'end'   => __( 'End of content', 'user-post-collections' ),
					'not'   => __( 'Not add button', 'user-post-collections' ),
				),
			);
			$settings_fields['mg_upc_general'][] = array(
				'name'    => 'mg_upc_button_position_product',
				'label'   => __( 'Product button position', 'user-post-collections' ),
				'desc'    => __( 'Where the "Add to list" button will be inserted on single product', 'user-post-collections' ),
				'default' => 'after_cart',
				'type'    => 'radio',
				'options' => array(
					'before_cart' => __( 'Before add to cart form', 'user-post-collections' ),
					'after_cart'  => __( 'After add to cart form', 'user-post-collections' ),
					'not'         => __( 'Not add button', 'user-post-collections' ),
				),
			);

			$settings_fields['mg_upc_general'][] = array(
				'name'    => 'mg_upc_my_orderby',
				'label'   => __( 'Order "My Lists" by', 'user-post-collections' ),
				'desc'    => __( 'Determines the order of the collections in the "My Lists" section.', 'user-post-collections' ),
				'default' => 'modified',
				'type'    => 'radio',
				'options' => array(
					'created'  => __( 'Creation date', 'user-post-collections' ),
					'modified' => __( 'Modification date', 'user-post-collections' ),
				),
			);
			$settings_fields['mg_upc_general'][] = array(
				'name'    => 'mg_upc_my_order',
				'label'   => __( 'Order "My Lists" direction', 'user-post-collections' ),
				'desc'    => __( 'Determines the order of the collections in the "My Lists" section.', 'user-post-collections' ),
				'default' => 'desc',
				'type'    => 'radio',
				'options' => array(
					'asc'  => __( 'Ascendant', 'user-post-collections' ),
					'desc' => __( 'Descendant', 'user-post-collections' ),
				),
			);

			$settings_fields['mg_upc_advanced'] = array();

			$settings_fields['mg_upc_advanced'][] = array(
				'name'    => 'mg_upc_purge_on_uninstall',
				'label'   => __( 'Remove data on uninstall', 'user-post-collections' ),
				'desc'    => __( 'Check this if you would like to remove ALL data upon plugin deletion. All settings and lists will be unrecoverable.', 'user-post-collections' ),
				'default' => 'off',
				'type'    => 'checkbox',
			);

			$settings_fields['mg_upc_advanced'][] = array(
				'name'    => 'mg_upc_single_title',
				'label'   => __( 'Single title', 'user-post-collections' ),
				'desc'    => __( 'You can use %title%, %author%, %sitename%.', 'user-post-collections' ),
				'default' => '%title% by %author% | User List | %sitename%',
				'type'    => 'text',
			);

			//************ Types **********

			$post_types_options = array();
			$args               = array(
				'public' => true,
			);
			$types              = get_post_types( $args, 'objects' );
			foreach ( $types as $type ) {
				if ( isset( $type->name ) ) {
					$post_types_options[ $type->name ] = $type->label;
				}
			}

			$prefix     = 'mg_upc_type_';
			$list_types = MG_UPC_Helper::get_instance()->get_list_types( true );
			foreach ( $list_types as $list_type ) {
				if ( ! $list_type->support( 'show_in_settings' ) ) {
					continue;
				}
				$settings_fields[ $prefix . $list_type->name ] = array();

				$settings_fields[ $prefix . $list_type->name ][] = array(
					'name'    => 'enabled',
					'default' => $list_type->enabled ? 'on' : 'off',
					'label'   => '',
					'desc'    => __( 'Enable this list type', 'user-post-collections' ),
					'type'    => 'checkbox',
				);
				$settings_fields[ $prefix . $list_type->name ][] = array(
					'name'        => 'label',
					'label'       => __( 'Label', 'user-post-collections' ),
					'desc'        => __( 'Name to display', 'user-post-collections' ),
					'placeholder' => '',
					'type'        => 'text',
					'default'     => '',
				);
				$settings_fields[ $prefix . $list_type->name ][] = array(
					'name'        => 'description',
					'label'       => __( 'Description', 'user-post-collections' ),
					'desc'        => __( 'Description to display', 'user-post-collections' ),
					'placeholder' => '',
					'type'        => 'text',
					'default'     => '',
				);
				$settings_fields[ $prefix . $list_type->name ][] = array(
					'name'    => 'sticky',
					'default' => $list_type->sticky,
					'label'   => 'Sticky lists',
					'desc'    => __( 'Show on top when listing lists', 'user-post-collections' ),
					'type'    => 'radio',
					'options' => array(
						'0' => __( 'Non sticky lists', 'user-post-collections' ),
						'1' => __( 'Sticky lists (High priority)', 'user-post-collections' ),
						'2' => __( 'Sticky lists (Medium priority)', 'user-post-collections' ),
						'3' => __( 'Sticky lists (Low priority)', 'user-post-collections' ),
					),
				);
				$settings_fields[ $prefix . $list_type->name ][] = array(
					'name'    => 'max_items',
					'label'   => __( 'Max items per list', 'user-post-collections' ),
					'desc'    => __( 'Maximum number of items that can contain this type of list', 'user-post-collections' ),
					'default' => $list_type->max_items,
					'type'    => 'number',
				);
				$settings_fields[ $prefix . $list_type->name ][] = array(
					'name'    => 'available_post_types',
					'label'   => __( 'Enabled post types', 'user-post-collections' ),
					'desc'    => __( 'Post types that you can add to this type of list', 'user-post-collections' ),
					'default' => $list_type->available_post_types,
					'type'    => 'multicheck',
					'options' => $post_types_options,
				);

				$roles_options  = array();
				$roles_value    = array();
				$editable_roles = array_reverse( get_editable_roles() );
				foreach ( $editable_roles as $role => $details ) {
					$roles_options[ $role ] = translate_user_role( $details['name'] );
					$role_object            = get_role( $role );
					if ( $role_object && $role_object->has_cap( $list_type->get_cap()->create_posts ) ) {
						$roles_value[] = $role;
					}
				}
				$settings_fields[ $prefix . $list_type->name ][] = array(
					'name'                     => 'roles',
					'label'                    => __( 'Roles', 'user-post-collections' ),
					'desc'                     => __( 'Roles that can create this type of list', 'user-post-collections' ),
					'default'                  => $roles_value,
					'type'                     => 'multicheck',
					'options'                  => $roles_options,
					'sanitize_callback_params' => 3,
					'sanitize_callback'        => function ( $values, $option, $original_value ) use ( $list_type, $roles_options ) {
						if ( ! empty( array_diff( $values, array_keys( $roles_options ) ) ) ) {
							return $original_value;
						}
						$caps = $list_type->get_cap();
						foreach ( $roles_options as $role_slug => $role_label ) {
							$role = get_role( $role_slug );
							if ( ! $role ) {
								add_settings_error(
									'mg_up_roles',
									'mg_up_roles' . $list_type->name . '_err',
									"Invalid role {$role_slug}"
								);
								continue;
							}
							//Capabilities for create/publish/delete list
							$grant_listing = array(
								'edit_posts',
								'create_posts',
								'delete_posts',
								'publish_posts',
							);
							$grant_listing = apply_filters( 'mg_upc_save_grant_edit', $grant_listing, $list_type, $role );
							if ( in_array( $role_slug, $values, true ) ) {
								foreach ( $grant_listing as $post_cap_name ) {
									$role->add_cap( $caps->$post_cap_name );
								}
							} else {
								foreach ( $grant_listing as $post_cap_name ) {
									$role->remove_cap( $caps->$post_cap_name );
								}
							}
						}
						return $values;
					},
				);

				if ( ! $list_type->support( 'vote' ) && ! $list_type->support( 'sortable' ) ) {
					$settings_fields[ $prefix . $list_type->name ][] = array(
						'name'    => 'default_orderby',
						'label'   => __( 'Items order by', 'user-post-collections' ),
						'desc'    => __( 'Sort the items of this type of lists by this property', 'user-post-collections' ),
						'default' => $list_type->default_orderby,
						'type'    => 'radio',
						'options' => array(
							'added'   => __( 'Added date', 'user-post-collections' ),
							'post_id' => __( 'Post ID', 'user-post-collections' ),
						),
					);
					$settings_fields[ $prefix . $list_type->name ][] = array(
						'name'    => 'default_order',
						'label'   => __( 'Items order', 'user-post-collections' ),
						'desc'    => __( 'Sort the items of this type of lists in this direction', 'user-post-collections' ),
						'default' => $list_type->default_order,
						'type'    => 'radio',
						'options' => array(
							'asc'  => __( 'Ascendant', 'user-post-collections' ),
							'desc' => __( 'Descendant', 'user-post-collections' ),
						),
					);
				}

				if ( count( $list_type->possible_statuses ) > 1 ) {
					$options_status = array();
					foreach ( $list_type->possible_statuses as $status ) {
						$status_object = MG_UPC_Helper::get_instance()->get_list_status( $status );
						if ( $status_object ) {
							$options_status[ $status ] = $status_object->label;
						}
					}
					$settings_fields[ $prefix . $list_type->name ][] = array(
						'name'                     => 'available_statuses',
						'label'                    => __( 'Enabled statuses', 'user-post-collections' ),
						'desc'                     => __( 'Enable this statuses for this list type.', 'user-post-collections' ),
						'default'                  => $list_type->available_statuses,
						'type'                     => 'multicheck',
						'options'                  => $options_status,
						'sanitize_callback_params' => 4,
						'sanitize_callback'        => function ( $value, $option, $original_value, $config ) {
							if ( empty( $value ) || ( is_array( $value ) && 0 === count( $value ) ) ) {
								add_settings_error(
									$option,
									sanitize_title( $option ) . '_ERR',
									'At least one status must be enabled'
								);
								return $original_value;
							}
							return self::sanitize_multicheck( $value, $option, $original_value, $config );
						},
					);
				}

				if ( count( $list_type->available_statuses ) > 1 ) {
					$options_status = array();
					foreach ( $list_type->available_statuses as $status ) {
						$status_object = MG_UPC_Helper::get_instance()->get_list_status( $status );
						if ( $status_object ) {
							$options_status[ $status ] = $status_object->label;
						}
					}
					$settings_fields[ $prefix . $list_type->name ][] = array(
						'name'    => 'default_status',
						'label'   => __( 'Default status', 'user-post-collections' ),
						'desc'    => __( 'The status when creating a list of this type', 'user-post-collections' ),
						'default' => $list_type->default_status,
						'type'    => 'radio',
						'options' => $options_status,
					);
				}

				$settings_fields[ $prefix . $list_type->name ][] = array(
					'name'    => 'default_title',
					'label'   => __( 'Default title', 'user-post-collections' ),
					'desc'    => __( 'The title when creating a list of this type', 'user-post-collections' ),
					'default' => $list_type->default_title,
					'type'    => 'text',
				);
			}
			//TODO: implement more settings:
			/*array(
				'default_content'    => '',
				'available_statuses' => $list_type->available_statuses, // for this implement "disable status" and then diff
			);*/

			$sanitize_options  = array( __CLASS__, 'sanitize_options' );
			$sanitize_multi    = array( __CLASS__, 'sanitize_multicheck' );
			$sanitize_checkbox = array( __CLASS__, 'sanitize_checkbox' );
			$sanitize_text     = array( __CLASS__, 'sanitize_text' );

			foreach ( $settings_fields as $tab => $fileds ) {
				foreach ( $fileds as $k => $field ) {
					if ( ! isset( $field['sanitize_callback'] ) ) {
						if ( 'checkbox' === $field['type'] ) {
							$settings_fields[ $tab ][ $k ]['sanitize_callback']        = $sanitize_checkbox;
							$settings_fields[ $tab ][ $k ]['sanitize_callback_params'] = 3;
						} elseif ( 'radio' === $field['type'] ) {
							$settings_fields[ $tab ][ $k ]['sanitize_callback']        = $sanitize_options;
							$settings_fields[ $tab ][ $k ]['sanitize_callback_params'] = 4;
						} elseif ( 'multicheck' === $field['type'] ) {
							$settings_fields[ $tab ][ $k ]['sanitize_callback']        = $sanitize_multi;
							$settings_fields[ $tab ][ $k ]['sanitize_callback_params'] = 4;
						} elseif ( 'text' === $field['type'] ) {
							$settings_fields[ $tab ][ $k ]['sanitize_callback']        = $sanitize_text;
							$settings_fields[ $tab ][ $k ]['sanitize_callback_params'] = 3;
						}
					}
					if ( 'array' === $field['type'] && isset( $field['item_fields'] ) ) {
						foreach ( $field['item_fields'] as $ss => $sub_field ) {
							if ( ! isset( $sub_field['sanitize_callback'] ) ) {
								if ( 'checkbox' === $sub_field['type'] ) {
									$settings_fields[ $tab ][ $k ]['item_fields'][ $ss ]['sanitize_callback']        = $sanitize_checkbox;
									$settings_fields[ $tab ][ $k ]['item_fields'][ $ss ]['sanitize_callback_params'] = 3;
								} elseif ( 'radio' === $sub_field['type'] ) {
									$settings_fields[ $tab ][ $k ]['item_fields'][ $ss ]['sanitize_callback']        = $sanitize_options;
									$settings_fields[ $tab ][ $k ]['item_fields'][ $ss ]['sanitize_callback_params'] = 4;
								} elseif ( 'multicheck' === $sub_field['type'] ) {
									$settings_fields[ $tab ][ $k ]['item_fields'][ $ss ]['sanitize_callback']        = $sanitize_multi;
									$settings_fields[ $tab ][ $k ]['item_fields'][ $ss ]['sanitize_callback_params'] = 4;
								} elseif ( 'text' === $sub_field['type'] ) {
									$settings_fields[ $tab ][ $k ]['item_fields'][ $ss ]['sanitize_callback']        = $sanitize_text;
									$settings_fields[ $tab ][ $k ]['item_fields'][ $ss ]['sanitize_callback_params'] = 3;
								}
							}
						}
					}
				}
			}

			return apply_filters( 'mg_upc_settings_fields', $settings_fields );
		}

		/**
		 * Sanitize checkbox option
		 *
		 * @param $value
		 * @param $option
		 * @param $original_value
		 *
		 * @return string
		 */
		public static function sanitize_checkbox( $value, $option, $original_value ) {

			if ( ! in_array( $value, array( 'on', 'off' ), true ) ) {
				//This never happen to no hackers users
				if ( in_array( $original_value, array( 'on', 'off' ), true ) ) {
					$msg = sprintf(
						'ERROR on set %s value: %s, return to %s',
						esc_html( $option ),
						esc_html( $value ),
						esc_html( $original_value )
					);
					add_settings_error( $option['name'], $option['name'] . 'ERR', $msg );
					$value = $original_value;
				}
			}

			return ( 'on' === $value ) ? 'on' : 'off';
		}

		/**
		 * Sanitize radio option
		 *
		 * @param $value
		 * @param $option
		 * @param $original_value
		 * @param $config
		 *
		 * @return mixed
		 */
		public static function sanitize_options( $value, $option, $original_value, $config ) {
			if ( ! isset( $config['options'] ) ) {
				//Never happen this to normal user, no translate
				add_settings_error(
					$option,
					sanitize_title( $option ) . '_ERR',
					'Options not available: ' . esc_html( $option ) . '.'
				);

				return $original_value;
			}
			if ( ! isset( $config['options'][ $value ] ) ) {
				if ( ! isset( $config['options'][ $original_value ] ) && isset( $config['default'] ) ) {
					$original_value = $config['default'];
				}
				//Never happen this to normal user, no translate
				add_settings_error(
					$option,
					sanitize_title( $option ) . '_ERR',
					sprintf(
						'ERROR %s: The option was not found %s',
						esc_html( $config['label'] ),
						esc_html( $value )
					)
				);

				return $original_value;
			}

			return $value;
		}

		/**
		 * Sanitize multicheck option
		 *
		 * @param $value
		 * @param $option
		 * @param $original_value
		 * @param $config
		 *
		 * @return array
		 */
		public static function sanitize_multicheck( $value, $option, $original_value, $config ) {

			if ( empty( $value ) ) {
				return array();
			}

			if ( ! isset( $config['options'] ) || ! is_array( $value ) ) {
				//Never happen this to normal user, no translate
				add_settings_error(
					$option,
					sanitize_title( $option ) . '_ERR',
					'Invalid option: ' . esc_html( $option ) . '.'
				);

				return $original_value;
			}

			$value = array_values( $value );

			$not_found = array_diff( $value, array_keys( $config['options'] ) );
			if ( ! empty( $not_found ) ) {
				if ( ! is_array( $original_value ) && isset( $config['default'] ) ) {
					$original_value = $config['default'];
				}
				//Never happen this to normal user, no translate
				add_settings_error(
					$option,
					sanitize_title( $option ) . '_ERR',
					sprintf(
						'ERROR %s: The option was not found %s',
						esc_html( $config['label'] ),
						esc_html( wp_json_encode( array_values( $not_found ) ) )
					)
				);

				return $original_value;
			}

			return $value;
		}

		/**
		 * Sanitize text option
		 *
		 * @param $value
		 * @param $option
		 * @param $original_value
		 *
		 * @return string
		 */
		public static function sanitize_text( $value, $option, $original_value ) {
			return sanitize_text_field( $value );
		}

		/**
		 * Print settings page
		 *
		 * @mvc view
		 */
		public function plugin_page() {

			if ( ! current_user_can( self::REQUIRED_CAPABILITY ) ) {
				return;
			}

			/**
			 * If return false dont print
			 */
			$short_circuit = apply_filters( 'mg_upc_settings_pre_print', true );
			if ( $short_circuit ) {

				echo '<div class="wrap">';

				$this->settings_api->show_navigation();
				$this->settings_api->show_forms();

				echo '</div>';

			}

		}


	} // end MG_UPC_Settings
}
