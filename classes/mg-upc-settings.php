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
		 *
		 * @noinspection PhpUnused ( add_filter register_hook_callbacks callback )
		 */
		public static function add_plugin_action_links( $links ) {
			array_unshift( $links, '<a href="admin.php?page=mg_upc_settings">Settings</a>' );

			return $links;
		}

		/**
		 * Prepares site to use the plugin during activation
		 *
		 * @param bool $network_wide
		 */
		public function activate( $network_wide ) { }

		/**
		 * Rolls back activation procedures when de-activating the plugin
		 */
		public function deactivate() { }

		/**
		 * Executes the logic of upgrading from specific older versions of the plugin to the current version
		 *
		 * @mvc Model
		 *
		 * @param int|string $db_version
		 */
		public function upgrade( $db_version = 0 ) { }

		/**
		 * Adds pages to the Admin Panel menu
		 */
		public function admin_menu() {

			$menu = add_menu_page(
				'User Post Lists',
				'User Post Lists',
				self::REQUIRED_CAPABILITY,
				'user-post-list',
				array( &$this, 'render_admin' ),
				'dashicons-list-view',
				25
			);

			add_action( 'admin_print_scripts-' . $menu, array( $this, 'enqueue_admin_scripts' ) );

			$this->settings_api->page_ref = add_submenu_page(
				'user-post-list',
				__( 'User Post Collections Settings', 'user-post-collections' ),
				__( 'Settings', 'user-post-collections' ),
				self::REQUIRED_CAPABILITY,
				'mg_upc_settings',
				array( $this, 'plugin_page' )
			);
		}

		public function render_admin() {
			echo "<div id='mg-upc-admin-app'></div>";
		}

		public function enqueue_admin_scripts() {
			User_Post_Collections::load_resources();
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
			);

			$sections[] = array(
				'id'       => 'mg_upc_advanced',
				'title'    => __( 'Advanced Settings', 'user-post-collections' ),
				'as_array' => false,
			);

			$sections[] = array(
				'id'       => 'mg_upc_texts',
				'title'    => __( 'Texts', 'user-post-collections' ),
				'as_array' => true,
				'desc'     => 'Overwrite texts used for the plugin.',
			);

			$desc       = __( 'The settings will commonly be applied to new lists or actions. For example, if you disable comments on items, this will not delete existing comments but will prevent new comments from being made.', 'user-post-collections' );
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
					'desc'     => $desc,
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

			//***************************************
			//           General
			//***************************************
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

			$settings_fields['mg_upc_general'][] = array(
				'name'    => 'mg_upc_api_item_per_page',
				'label'   => __( 'Items per page (API)', 'user-post-collections' ),
				'desc'    => __( 'Default number of items per page on API request.', 'user-post-collections' ),
				'max'     => 100,
				'min'     => 1,
				'default' => 12,
				'type'    => 'number',
			);

			$settings_fields['mg_upc_general'][] = array(
				'name'    => 'mg_upc_item_per_page',
				'label'   => __( 'Items per page (List Page)', 'user-post-collections' ),
				'desc'    => __( 'Default number of items per page (List Page).', 'user-post-collections' ),
				'max'     => 100,
				'min'     => 1,
				'default' => 50,
				'type'    => 'number',
			);

			$settings_fields['mg_upc_general'][] = array(
				'name'    => 'mg_upc_post_stats',
				'label'   => __( 'Save general count in posts', 'user-post-collections' ),
				'desc'    => __( 'Save count of how many times an item is added to lists.', 'user-post-collections' ),
				'default' => 'on',
				'type'    => 'checkbox',
			);

			$settings_fields['mg_upc_general'][] = array(
				'name'    => 'mg_upc_share_buttons',
				'label'   => __( 'Share buttons (List Page)', 'user-post-collections' ),
				'desc'    => __( 'Share buttons enabled on collection page.', 'user-post-collections' ),
				'default' => array( 'twitter', 'facebook', 'whatsapp', 'telegram', 'line', 'email' ),
				'type'    => 'multicheck',
				'options' => array(
					'twitter'   => __( 'Twitter', 'user-post-collections' ),
					'facebook'  => __( 'Facebook', 'user-post-collections' ),
					'pinterest' => __( 'Pinterest', 'user-post-collections' ),
					'whatsapp'  => __( 'Whatsapp', 'user-post-collections' ),
					'telegram'  => __( 'Telegram', 'user-post-collections' ),
					'line'      => __( 'LiNE', 'user-post-collections' ),
					'email'     => __( 'Email', 'user-post-collections' ),
				),
			);

			$settings_fields['mg_upc_general'][] = array(
				'name'    => 'mg_upc_share_buttons_client',
				'label'   => __( 'Share buttons (Client)', 'user-post-collections' ),
				'desc'    => __( 'Share buttons enabled on modal client.', 'user-post-collections' ),
				'default' => array( 'twitter', 'facebook', 'whatsapp', 'telegram', 'line', 'email' ),
				'type'    => 'multicheck',
				'options' => array(
					'twitter'   => __( 'Twitter', 'user-post-collections' ),
					'facebook'  => __( 'Facebook', 'user-post-collections' ),
					'pinterest' => __( 'Pinterest', 'user-post-collections' ),
					'whatsapp'  => __( 'Whatsapp', 'user-post-collections' ),
					'telegram'  => __( 'Telegram', 'user-post-collections' ),
					'line'      => __( 'LiNE', 'user-post-collections' ),
					'email'     => __( 'Email', 'user-post-collections' ),
				),
			);

			//***************************************
			//           ADVANCED
			//***************************************
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
				// translators: not change %title%, %author%, %sitename%
				'desc'    => __( 'You can use %title%, %author%, %sitename%.', 'user-post-collections' ),
				'default' => '%title% by %author% | User List | %sitename%',
				'type'    => 'text',
			);

			$settings_fields['mg_upc_advanced'][] = array(
				'name'    => 'mg_upc_store_vote_ip',
				'default' => 'on',
				'label'   => 'Store voting IP',
				'desc'    => '',
				'type'    => 'radio',
				'options' => array(
					'off'    => __( 'Non store', 'user-post-collections' ),
					'on'     => __( 'Store IP', 'user-post-collections' ),
				),
			);
			$settings_fields['mg_upc_advanced'][] = array(
				'name'    => 'mg_upc_store_vote_anonymize_ip',
				'default' => 'on',
				'label'   => '',
				'desc'    => __( 'Anonymize stored IP', 'user-post-collections' ),
				'type'    => 'checkbox',
			);

			$settings_fields['mg_upc_advanced'][] = array(
				'name'    => 'mg_upc_ajax_load',
				'default' => 'on',
				'label'   => 'Ajax load',
				'desc'    => 'To resolve potential cache issues.',
				'type'    => 'radio',
				'options' => array(
					'off' => __( 'Load nonce and user info on page.', 'user-post-collections' ),
					'on'  => __( 'Load nonce and user info with ajax.', 'user-post-collections' ),
				),
			);

			//***************************************
			//           TEXTS
			//***************************************

			$settings_fields['mg_upc_texts'][] = array(
				'name'    => 'add_to_list',
				'label'   => __( 'Add to list...', 'user-post-collections' ),
				'desc'    => __( 'Add to list button text on single content.', 'user-post-collections' ),
				'default' => '',
				'type'    => 'text',
			);
			$settings_fields['mg_upc_texts'][] = array(
				'name'    => 'vote_action',
				'label'   => __( 'Vote', 'user-post-collections' ),
				'desc'    => __( 'Vote button text.', 'user-post-collections' ),
				'default' => '',
				'type'    => 'text',
			);
			$settings_fields['mg_upc_texts'][] = array(
				'name'    => 'total_votes',
				'label'   => __( 'Total Votes', 'user-post-collections' ),
				// translators: %s are literal
				'desc'    => __( 'Use "%1$s" for number of votes. Ex: "Total votes: %1$s"', 'user-post-collections' ),
				'default' => '',
				'type'    => 'text',
			);
			$settings_fields['mg_upc_texts'][] = array(
				'name'    => 'created_by',
				'label'   => __( 'Created by', 'user-post-collections' ),
				// translators: %s are literal
				'desc'    => __( 'Use "%1$s" for author name. Ex: "Created by %1$s"', 'user-post-collections' ),
				'default' => '',
				'type'    => 'text',
			);
			$settings_fields['mg_upc_texts'][] = array(
				'name'    => 'quantity',
				'label'   => __( 'Quantity', 'user-post-collections' ),
				'desc'    => __( 'Quantity item label on list page.', 'user-post-collections' ),
				'default' => '',
				'type'    => 'text',
			);

			$settings_fields['mg_upc_texts'][] = array(
				'name'    => 'client_my_lists',
				'label'   => __( 'My Lists (client js)', 'user-post-collections' ),
				'default' => '',
				'type'    => 'text',
			);
			$settings_fields['mg_upc_texts'][] = array(
				'name'    => 'client_create_list',
				'label'   => __( 'Create List (client js)', 'user-post-collections' ),
				'default' => '',
				'type'    => 'text',
			);
			$settings_fields['mg_upc_texts'][] = array(
				'name'    => 'client_save',
				'label'   => __( 'Save (client js)', 'user-post-collections' ),
				'default' => '',
				'type'    => 'text',
			);
			$settings_fields['mg_upc_texts'][] = array(
				'name'    => 'client_cancel',
				'label'   => __( 'Cancel (client js)', 'user-post-collections' ),
				'default' => '',
				'type'    => 'text',
			);
			$settings_fields['mg_upc_texts'][] = array(
				'name'    => 'client_add_comment',
				'label'   => __( 'Add Comment (client js)', 'user-post-collections' ),
				'default' => '',
				'type'    => 'text',
			);
			$settings_fields['mg_upc_texts'][] = array(
				'name'    => 'client_edit_comment',
				'label'   => __( 'Edit Comment (client js)', 'user-post-collections' ),
				'default' => '',
				'type'    => 'text',
			);
			$settings_fields['mg_upc_texts'][] = array(
				'name'    => 'client_quantity',
				'label'   => __( 'Quantity (client js)', 'user-post-collections' ),
				'default' => '',
				'type'    => 'text',
			);
			$settings_fields['mg_upc_texts'][] = array(
				'name'    => 'client_edit',
				'label'   => __( 'Edit (client js)', 'user-post-collections' ),
				'default' => '',
				'type'    => 'text',
			);
			$settings_fields['mg_upc_texts'][] = array(
				'name'    => 'client_title',
				'label'   => __( 'Title (client js)', 'user-post-collections' ),
				'default' => '',
				'type'    => 'text',
			);
			$settings_fields['mg_upc_texts'][] = array(
				'name'    => 'client_description',
				'label'   => __( 'Description (client js)', 'user-post-collections' ),
				'default' => '',
				'type'    => 'text',
			);
			$settings_fields['mg_upc_texts'][] = array(
				'name'    => 'client_status',
				'label'   => __( 'Status (client js)', 'user-post-collections' ),
				'default' => '',
				'type'    => 'text',
			);
			$settings_fields['mg_upc_texts'][] = array(
				'name'    => 'client_remove_list',
				'label'   => __( 'Remove List (client js)', 'user-post-collections' ),
				'default' => '',
				'type'    => 'text',
			);
			$settings_fields['mg_upc_texts'][] = array(
				'name'    => 'client_share',
				'label'   => __( 'Share (client js)', 'user-post-collections' ),
				'default' => '',
				'type'    => 'text',
			);
			$settings_fields['mg_upc_texts'][] = array(
				'name'    => 'client_copy',
				'label'   => __( 'Copy (client js)', 'user-post-collections' ),
				'default' => '',
				'type'    => 'text',
			);
			$settings_fields['mg_upc_texts'][] = array(
				'name'    => 'client_copied',
				'label'   => __( 'Copied! (client js)', 'user-post-collections' ),
				'default' => '',
				'type'    => 'text',
			);
			$settings_fields['mg_upc_texts'][] = array(
				'name'    => 'client_email',
				'label'   => __( 'Email (client js)', 'user-post-collections' ),
				'default' => '',
				'type'    => 'text',
			);
			$settings_fields['mg_upc_texts'][] = array(
				'name'    => 'client_select_to_add',
				'label'   => __( 'Select where the item will be added: (client js)', 'user-post-collections' ),
				'default' => '',
				'type'    => 'text',
			);
			$settings_fields['mg_upc_texts'][] = array(
				'name'    => 'client_select_list_type',
				'label'   => __( 'Select a list type: (client js)', 'user-post-collections' ),
				'default' => '',
				'type'    => 'text',
			);
			$settings_fields['mg_upc_texts'][] = array(
				'name'    => 'client_total_votes',
				'label'   => __( 'Total votes: (client js)', 'user-post-collections' ),
				'default' => '',
				'type'    => 'text',
			);
			$settings_fields['mg_upc_texts'][] = array(
				'name'    => 'client_unknown_type',
				'label'   => __( 'Unknown List Type... (client js)', 'user-post-collections' ),
				'default' => '',
				'type'    => 'text',
			);
			$settings_fields['mg_upc_texts'][] = array(
				'name'    => 'client_add_to_title',
				'label'   => __( 'Add to... (client js title)', 'user-post-collections' ),
				'default' => '',
				'type'    => 'text',
			);

			//***************************************
			//           Each List Type
			//***************************************
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

				if ( ! $list_type->support( 'always_exists' ) ) {
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
				}

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

				$settings_fields[ $prefix . $list_type->name ][] = array(
					'name'    => 'mg_upc_post_stats',
					'label'   => __( 'Save count in posts', 'user-post-collections' ),
					'desc'    => __( 'Keep count of how many times an item is added to lists of this type.', 'user-post-collections' ),
					'default' => 'off',
					'type'    => 'checkbox',
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
									esc_html__( 'At least one status must be enabled', 'user-post-collections' )
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

				$configurable_features = $list_type->get_configurable_features();
				if ( count( $configurable_features ) > 0 ) {
					$supports_options = array();

					$labels = array(
						'editable_title'            => __( 'Editable title', 'user-post-collections' ),
						'editable_content'          => __( 'Editable list description', 'user-post-collections' ),
						'editable_item_description' => __( 'Editable item comment', 'user-post-collections' ),
					);
					foreach ( $configurable_features as $feature ) {
						$supports_options[ $feature ] = isset( $labels[ $feature ] ) ? $labels[ $feature ] : $feature;
					}
					$settings_fields[ $prefix . $list_type->name ][] = array(
						'name'    => 'supports',
						'label'   => __( 'Supported features', 'user-post-collections' ),
						'desc'    => __( 'Supported features enabled', 'user-post-collections' ),
						'default' => $list_type->get_default_config_features(),
						'type'    => 'multicheck',
						'options' => $supports_options,
					);
				}

				if ( $list_type->support( 'vote' ) ) {
					$settings_fields[ $prefix . $list_type->name ][] = array(
						'name'    => 'ttl_votes',
						'label'   => __( 'TTL Votes', 'user-post-collections' ),
						'desc'    => __( 'How long voting records should remain in the database', 'user-post-collections' ),
						'default' => 365,
						'type'    => 'radio',
						'options' => array(
							'1'    => __( 'One day', 'user-post-collections' ),
							'7'    => __( 'Seven days', 'user-post-collections' ),
							'30'   => __( 'Thirty days', 'user-post-collections' ),
							'90'   => __( 'Ninety days', 'user-post-collections' ),
							'182'  => __( 'Six months', 'user-post-collections' ),
							'365'  => __( 'One year', 'user-post-collections' ),
							'730'  => __( 'Two year', 'user-post-collections' ),
							'1095' => __( 'Three years', 'user-post-collections' ),
							'2190' => __( 'Six years', 'user-post-collections' ),
						),
					);
					$settings_fields[ $prefix . $list_type->name ][] = array(
						'name'    => 'show_on_vote',
						'label'   => __( 'Showing results', 'user-post-collections' ),
						'desc'    => __( 'Show voting information only after the user has voted', 'user-post-collections' ),
						'default' => 'off',
						'type'    => 'checkbox',
					);
					$settings_fields[ $prefix . $list_type->name ][] = array(
						'name'    => 'vote_require_login',
						'label'   => __( 'Require login to vote', 'user-post-collections' ),
						'desc'    => __( 'Require login to vote', 'user-post-collections' ),
						'default' => 'on',
						'type'    => 'checkbox',
					);
					$settings_fields[ $prefix . $list_type->name ][] = array(
						'name'    => 'max_votes_per_user',
						'label'   => __( 'Max votes per logged user', 'user-post-collections' ),
						'desc'    => __( 'Set to zero to apply no limits', 'user-post-collections' ),
						'default' => 1,
						'min'     => 0,
						'type'    => 'number',
					);
					$settings_fields[ $prefix . $list_type->name ][] = array(
						'name'    => 'max_votes_per_ip',
						'label'   => __( 'Max votes per IP', 'user-post-collections' ),
						'desc'    => __( 'Set to zero to apply no limits. Make sure IP storage is enabled in the advanced settings section.', 'user-post-collections' ),
						'default' => 5,
						'min'     => 0,
						'type'    => 'number',
					);
				}
			}

			$settings_fields = apply_filters( 'mg_upc_settings_fields', $settings_fields );

			$sanitize_options  = array( __CLASS__, 'sanitize_options' );
			$sanitize_multi    = array( __CLASS__, 'sanitize_multicheck' );
			$sanitize_checkbox = array( __CLASS__, 'sanitize_checkbox' );
			$sanitize_text     = array( __CLASS__, 'sanitize_text' );
			$sanitize_number   = array( __CLASS__, 'sanitize_number' );

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
						} elseif ( 'number' === $field['type'] ) {
							$settings_fields[ $tab ][ $k ]['sanitize_callback']        = $sanitize_number;
							$settings_fields[ $tab ][ $k ]['sanitize_callback_params'] = 4;
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
								} elseif ( 'number' === $sub_field['type'] ) {
									$settings_fields[ $tab ][ $k ]['item_fields'][ $ss ]['sanitize_callback']        = $sanitize_number;
									$settings_fields[ $tab ][ $k ]['item_fields'][ $ss ]['sanitize_callback_params'] = 4;
								}
							}
						}
					}
				}
			}

			return $settings_fields;
		}

		/**
		 * Sanitize checkbox option
		 *
		 * @param $value
		 * @param $option
		 * @param $original_value
		 *
		 * @return string
		 *
		 * @noinspection PhpUnused (Option sanitize callback)
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
		 *
		 * @noinspection PhpUnused (Option sanitize callback)
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
		 *
		 * @noinspection PhpUnused (Option sanitize callback)
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
		 *
		 * @noinspection PhpUnused (Option sanitize callback)
		 */
		public static function sanitize_text( $value, $option, $original_value ) {
			return sanitize_text_field( $value );
		}

		/**
		 * Sanitize number option
		 *
		 * @param $value
		 * @param $option
		 * @param $original_value
		 * @param $config
		 *
		 * @return int|float
		 *
		 * @noinspection PhpUnused (Option sanitize callback)
		 */
		public static function sanitize_number( $value, $option, $original_value, $config ) {

			if ( ! is_numeric( $value ) ) {
				//Never happen this to normal user, no translate
				add_settings_error(
					$option,
					sanitize_title( $option ) . '_ERR',
					'Invalid option: ' . esc_html( $option ) . '.'
				);

				return $original_value;
			}

			$value = (float) $value;
			if ( isset( $config['max'] ) && $value > $config['max'] ) {
				//Never happen this to normal user, no translate
				add_settings_error(
					$option,
					sanitize_title( $option ) . '_ERR',
					'Invalid option: ' . esc_html( $option ) . '.'
				);

				return $original_value;
			}

			if ( isset( $config['min'] ) && $value < $config['min'] ) {
				//Never happen this to normal user, no translate
				add_settings_error(
					$option,
					sanitize_title( $option ) . '_ERR',
					'Invalid option: ' . esc_html( $option ) . '.'
				);

				return $original_value;
			}

			return $value;
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
