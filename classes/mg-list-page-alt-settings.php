<?php

class MG_UPC_List_Page_Settings extends MG_UPC_Module {

	/**
	 * @var int The id of page selected for list
	 */
	private static $page_id = 0;

	public function __construct() {}

	public function init() {

		//Search page saved as collection single page (Created on the activated)
		self::$page_id = MG_UPC_List_Page::get_page_id();

		if ( is_admin() ) {
			add_filter( 'mg_upc_settings_sections', array( $this, 'mg_upc_settings_sections' ) );
			add_filter( 'mg_upc_settings_fields', array( $this, 'add_settings_fields' ) );
			add_action( 'save_post_page', array( $this, 'save_post_page' ), 10, 1 );

			// Add a post display state for special page.
			add_filter( 'display_post_states', array( $this, 'add_display_post_states' ), 10, 2 );
		}
	}

	public function save_post_page( $post_id ) {
		if ( $post_id === self::$page_id ) {
			MG_UPC_List_Page::add_rewrite();
			flush_rewrite_rules();
		}
	}

	/**
	 * Add a post display state for special page in the page list table.
	 *
	 * @param array $post_states An array of post display states.
	 * @param WP_Post $post The current post object.
	 *
	 * @return array
	 */
	public function add_display_post_states( $post_states, $post ) {
		if ( self::$page_id === $post->ID ) {
			$post_states['mg_upc_page_for_list'] = __( 'User Post Collection Page', 'user-post-collections' );
		}

		return $post_states;
	}

	/**
	 * On plugin activated
	 *
	 * @param bool $network_wide
	 */
	public function activate( $network_wide ) {

		update_option( 'mg_upc_flush_rewrite', '1' );

		self::$page_id = MG_UPC_List_Page::get_page_id();
		if ( self::$page_id > 0 ) {
			$status = get_post_status( self::$page_id );
			if ( is_string( $status ) ) {
				if ( 'trash' === $status ) {
					wp_untrash_post( self::$page_id );
					wp_publish_post( self::$page_id );
				}
				$list_page_link = get_page_link( self::$page_id );
				if ( ! empty( $list_page_link ) ) {
					return;
				}
			}
		}

		//Create a page reserved for collection single
		$post = array(
			'post_title'   => 'User Post Collection',
			'post_content' => "<!-- wp:shortcode -->\n[user_post_collection]\n<!-- /wp:shortcode -->",
			'post_type'    => 'page',
			'post_status'  => 'publish',
		);

		$post_id = wp_insert_post( $post );
		update_option( 'mg_upc_single_page', $post_id );

		MG_UPC_List_Page::add_rewrite();
	}

	/**
	 * Add settings tabs
	 *
	 * @param $sections
	 *
	 * @return mixed
	 */
	public function mg_upc_settings_sections( $sections ) {
		$section = array(
			'id'       => 'mg_upc_archive',
			'title'    => __( 'Archive Settings', 'user-post-collections' ),
			'as_array' => false,
		);
		array_splice( $sections, 1, 0, array( $section ) );

		return $sections;
	}

	/**
	 * Add settings fields for manage page
	 *
	 * @param $settings_fields
	 *
	 * @return mixed
	 */
	public function add_settings_fields( $settings_fields ) {
		$new = array(
			array(
				'name'                     => 'mg_upc_single_page',
				'label'                    => __( 'Collection Page', 'user-post-collections' ),
				'desc'                     => __( 'make sure the shortcode [user_post_collection] is present on the selected page', 'user-post-collections' ),
				'default'                  => MG_UPC_List_Page::get_page_id(),
				'type'                     => 'pages',
				'sanitize_callback_params' => 3,
				'sanitize_callback'        => function ( $value, $option, $original_value ) {
					if ( ! is_numeric( $value ) ) {
						return $original_value;
					}

					if ( $value !== $original_value ) {
						update_option( 'mg_upc_flush_rewrite', '1' );
					}

					return $value;
				},
			),
			array(
				'name'    => 'mg_upc_single_page_mode',
				'label'   => __( 'Collection Page Template', 'user-post-collections' ),
				'desc'    => __( 'Try change this if the single list page not show as you like.', 'user-post-collections' ),
				'default' => 'template_page',
				'type'    => 'radio',
				'options' => array(
					'template_upc'  => __( 'Load UPC template', 'user-post-collections' ),
					'template_page' => __( 'Load inside the default selected page template', 'user-post-collections' ),
				),
			),
		);

		$settings_fields['mg_upc_general'] = array_merge(
			$new,
			$settings_fields['mg_upc_general']
		);

		/***************************************
		 ********** Archive Settings ***********
		 ***************************************/
		$archive_options = array();

		$enable = array(
			'name'    => 'mg_upc_archive_enable',
			'label'   => __( 'Enable archive', 'user-post-collections' ),
			'desc'    => __( 'Archive URL Examples: ', 'user-post-collections' ),
			'default' => 'on',
			'type'    => 'radio',
			'options' => array(
				'off' => __( 'Disable archive', 'user-post-collections' ),
				'on'  => __( 'Enable archive', 'user-post-collections' ),
			),
		);

		$base_url     = apply_filters( 'mg_upc_base_url', '' );
		$current_user = wp_get_current_user();
		$ex1          = $base_url . '?list-author=' . get_current_user_id();
		$ex2          = $base_url . '?list-author=' . get_current_user_id() . '&list-type=vote';
		$ex3          = $base_url . '?list-author-name=' . $current_user->user_login;

		$enable['desc'] .= '<br /><ul><li>' . $ex1 . '</li><li>' . $ex2 . '</li><li>' . $ex3 . '</li></ul>';

		$archive_options[] = $enable;
		$archive_options[] = array(
			'name'    => 'mg_upc_archive_filter_author',
			'label'   => __( 'Archive author filter', 'user-post-collections' ),
			'desc'    => __( 'Enable filtering by author (list-author-name, list-author)', 'user-post-collections' ),
			'default' => 'on',
			'type'    => 'checkbox',
		);
		$archive_options[] = array(
			'name'    => 'mg_upc_archive_filter_type',
			'label'   => __( 'Archive type filter', 'user-post-collections' ),
			'desc'    => __( 'Enable filtering by list type (list-type)', 'user-post-collections' ),
			'default' => 'on',
			'type'    => 'checkbox',
		);

		$archive_options[] = array(
			'name'    => 'mg_upc_archive_title',
			'label'   => __( 'Archive title', 'user-post-collections' ),
			// translators: not change %sitename%
			'desc'    => __( 'You can use %sitename%', 'user-post-collections' ),
			'default' => __( 'User Lists', 'user-post-collections' ),
			'type'    => 'text',
		);

		$archive_options[] = array(
			'name'    => 'mg_upc_archive_title_author',
			'label'   => __( 'Author archive title', 'user-post-collections' ),
			// translators: not change %author%, %sitename%
			'desc'    => __( 'You can use %author%, %sitename%.', 'user-post-collections' ),
			'default' => __( 'Lists created by %author%', 'user-post-collections' ),
			'type'    => 'text',
		);

		$archive_options[] = array(
			'name'    => 'mg_upc_archive_title_type',
			'label'   => __( 'List type archive title', 'user-post-collections' ),
			// translators: not change %type%, %sitename%
			'desc'    => __( 'You can use %type%, %sitename%.', 'user-post-collections' ),
			'default' => __( 'User Lists | %type%', 'user-post-collections' ),
			'type'    => 'text',
		);

		$archive_options[] = array(
			'name'    => 'mg_upc_archive_title_author_type',
			'label'   => __( 'Type and Author archive title', 'user-post-collections' ),
			// translators: not change %author%, %type%, %sitename%
			'desc'    => __( 'You can use %author%, %type%, %sitename%.', 'user-post-collections' ),
			'default' => __( 'Lists created by %author% | %type%', 'user-post-collections' ),
			'type'    => 'text',
		);

		$archive_options[] = array(
			'name'    => 'mg_upc_archive_document_title',
			'label'   => __( 'Lists Archive document title', 'user-post-collections' ),
			// translators: not change %upctitle%, %sitename%
			'desc'    => __( 'You can use %upctitle% and %sitename%.', 'user-post-collections' ),
			'default' => __( '%upctitle% | %sitename%', 'user-post-collections' ),
			'type'    => 'text',
		);

		$archive_options[] = array(
			'name'    => 'mg_upc_archive_item_per_page',
			'label'   => __( 'Items per page (Archive Page)', 'user-post-collections' ),
			'desc'    => __( 'Number of items per page (Archive Page).', 'user-post-collections' ),
			'max'     => 100,
			'min'     => 1,
			'default' => 12,
			'type'    => 'number',
		);

		$archive_options[] = array(
			'name'    => 'mg_upc_archive_item_template',
			'label'   => __( 'Collection item template', 'user-post-collections' ),
			'desc'    => __( 'Select the collection item template', 'user-post-collections' ),
			'default' => 'list',
			'type'    => 'radio',
			'options' => array(
				'list' => __( 'List', 'user-post-collections' ),
				'card' => __( 'Card', 'user-post-collections' ),
			),
		);

		$archive_options[] = array(
			'name'    => 'mg_upc_archive_item_template_user',
			'label'   => __( 'Collection author', 'user-post-collections' ),
			'desc'    => __( 'Show author of collections', 'user-post-collections' ),
			'default' => 'on',
			'type'    => 'checkbox',
		);

		$archive_options[] = array(
			'name'    => 'mg_upc_archive_item_template_meta',
			'label'   => __( 'Collection meta', 'user-post-collections' ),
			'desc'    => __( 'Show collection meta info', 'user-post-collections' ),
			'default' => 'on',
			'type'    => 'checkbox',
		);

		$archive_options[] = array(
			'name'    => 'mg_upc_archive_item_template_desc',
			'label'   => __( 'Collection description', 'user-post-collections' ),
			'desc'    => __( 'Show collection description', 'user-post-collections' ),
			'default' => 'off',
			'type'    => 'checkbox',
		);

		$screens = array(
			'xs'  => __( 'Extra Small', 'user-post-collections' ),
			'sm'  => __( 'Small', 'user-post-collections' ),
			'md'  => __( 'Medium', 'user-post-collections' ),
			'lg'  => __( 'Large', 'user-post-collections' ),
			'xl'  => __( 'Extra Large', 'user-post-collections' ),
			'xxl' => __( 'Extra Extra Large', 'user-post-collections' ),
		);

		$thumbs_options = array(
			'0'   => __( 'No thumbnails', 'user-post-collections' ),
			'1x1' => '1x1',
			'1x2' => '1x2',
			'1x3' => '1x3',
			'1x4' => '1x4',
			'2x1' => '2x1',
			'2x2' => '2x2',
			'2x3' => '2x3',
			'2x4' => '2x4',
			'3x1' => '3x1',
			'3x2' => '3x2',
			'3x3' => '3x3',
			'3x4' => '3x4',
			'4x1' => '4x1',
			'4x2' => '4x2',
			'4x3' => '4x3',
			'4x4' => '4x4',
		);
		foreach ( $screens as $screen_slug => $screen ) {
			$archive_options[] = array(
				'name'    => 'mg_upc_archive_item_template_thumbs_' . $screen_slug,
				// translators: %s is the screen size
				'label'   => sprintf( __( 'Thumbnails layout (%s Screen)', 'user-post-collections' ), $screen ),
				'desc'    => '',
				'type'    => 'select',
				'default' => 'xs' === $screen_slug ? '4x1' : '2x2',
				'options' => $thumbs_options,
			);
		}

		$cols_options  = array(
			'1' => '1',
			'2' => '2',
			'3' => '3',
			'4' => '4',
			'5' => '5',
		);
		$cols_defaults = array(
			'xs'  => '1',
			'sm'  => '2',
			'md'  => '3',
			'lg'  => '4',
			'xl'  => '4',
			'xxl' => '4',
		);
		foreach ( $screens as $screen_slug => $screen ) {
			$archive_options[] = array(
				'name'    => 'mg_upc_archive_item_template_cols_' . $screen_slug,
				// translators: %s is the screen size
				'label'   => sprintf( __( 'Number of columns (%s Screen)', 'user-post-collections' ), $screen ),
				'desc'    => 'For card list template only',
				'type'    => 'select',
				'default' => $cols_defaults[ $screen_slug ],
				'options' => $cols_options,
			);
		}

		$settings_fields['mg_upc_archive'] = $archive_options;

		return $settings_fields;
	}

	public function deactivate() {
		self::$page_id = MG_UPC_List_Page::get_page_id();
		if ( self::$page_id > 0 ) {
			$status = get_post_status( self::$page_id );
			if ( is_string( $status ) ) {
				wp_trash_post( self::$page_id );
			}
		}
	}

	public function register_hook_callbacks() { }

	public function upgrade( $db_version = 0 ) {
		if ( version_compare( $db_version, '0.7.1', '<' ) ) {
			update_option( 'mg_upc_flush_rewrite', '0' );
		}
		if ( ! empty( $db_version ) && version_compare( $db_version, '0.9.0', '<' ) ) {
			update_option( 'mg_upc_archive_enable', 'off' );
		}
	}
}
