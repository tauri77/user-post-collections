<?php

class MG_UPC_List_Page extends MG_UPC_Module {

	/**
	 * @var int The id of page selected for list
	 */
	private static $page_id = 0;

	public function __construct() { }

	public function init() {

		//Search page saved as collection single page (Created on the activate)
		self::$page_id = self::get_page_id();

		if ( self::$page_id > 0 ) {
			// Add query vars for collection page: list and list-page (for pagination)
			add_filter( 'query_vars', array( $this, 'add_list_query_var' ) );
			// Add the rewrite rule using slug from $page_id
			$this->add_rewrite();
			if ( get_option( 'mg_upc_flush_rewrite', '0' ) === '1' ) {
				update_option( 'mg_upc_flush_rewrite', '0' );
				flush_rewrite_rules();
			}
		}

		if ( is_admin() ) {
			add_filter( 'mg_upc_settings_fields', array( $this, 'add_settings_fields' ) );
			add_action( 'save_post_page', array( $this, 'save_post_page' ), 10, 1 );
		}

		/* Title Hooks*/
		add_filter( 'wpseo_title', array( $this, 'list_title' ), 16, 2 );
		add_filter( 'wpseo_opengraph_title', array( $this, 'list_title' ), 10, 2 );
		add_filter( 'pre_get_document_title', array( $this, 'list_title' ), 2 );
		add_filter( 'the_title', array( $this, 'the_title' ), 10, 2 );

		/* Descriptions Hooks */
		add_filter( 'wpseo_metadesc', array( $this, 'list_desc' ), 10, 2 );
		add_filter( 'wpseo_opengraph_desc', array( $this, 'list_desc' ), 10, 2 );

		/* Links Hooks */
		add_filter( 'wpseo_canonical', array( $this, 'list_canonical' ), 10, 2 );
		add_filter( 'wpseo_opengraph_url', array( $this, 'list_canonical' ), 10, 2 );
		add_filter( 'prepare_list_data_for_response', array( $this, 'add_link_to_list_response' ) );

		/* Image Hook*/
		add_filter( 'wpseo_add_opengraph_images', array( $this, 'list_opengraph_image' ), 10, 1 );

		/* Shortcode */
		add_shortcode( 'user_post_collection', array( $this, 'list_shortcode' ) );

		/* Templates hook */
		if ( get_option( 'mg_upc_single_page_mode', 'template_page' ) === 'template_upc' ) {
			add_filter( 'template_include', array( 'MG_UPC_List_Page', 'template_loader' ) );
		}

		/* Set global $mg_upc_list if query a collection*/
		add_action( 'parse_request', array( $this, 'parse_request' ), 10, 1 );

		/* Remove page links from head */
		add_action( 'template_redirect', array( $this, 'remove_links' ) );

		// Add a post display state for special page.
		add_filter( 'display_post_states', array( $this, 'add_display_post_states' ), 10, 2 );

	}

	public function save_post_page( $post_id ) {
		if ( $post_id === self::$page_id ) {
			$this->add_rewrite();
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
	 * If a list is requested load global $mg_upc_list
	 *
	 * @param $query
	 *
	 * @return mixed
	 */
	public function parse_request( $query ) {
		if (
			isset( $query->query_vars['page_id'] ) &&
			(int) self::$page_id === (int) $query->query_vars['page_id'] &&
			isset( $query->query_vars['list'] )
		) {
			$this->get_list_requested( false );
			remove_filter( 'the_content', array( 'MG_UPC_Buttons', 'the_content' ) );
		}

		return $query;
	}

	/**
	 * Set the collection "single" url
	 */
	public function add_rewrite() {
		if ( self::$page_id > 0 ) {
			$list_page_link = get_page_link( self::$page_id );
			if ( ! empty( $list_page_link ) ) {
				$reg = '^' . trim( wp_make_link_relative( $list_page_link ), '/' ) . '/([A-Za-z0-9\._\-@ ]+)/?$';
				add_rewrite_rule(
					$reg,
					'index.php?page_id=' . self::$page_id . '&post_type=page&list=$matches[1]',
					'top'
				);
			}
		}
	}

	/**
	 * Remove the links of single page from head
	 */
	public function remove_links() {
		if ( self::is_requesting_list_page() ) {
			remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
			remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
			remove_action( 'wp_head', 'rest_output_link_wp_head' );

			// remove HTTP header
			// Link: <https://example.com/?p=25>; rel=shortlink
			remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
			remove_action( 'template_redirect', 'rest_output_link_header', 11 );

			//TODO: replace data with collection info
			add_filter( 'wpseo_json_ld_output', '__return_false' );
		}
	}

	/**
	 * Hook for set own templates
	 *
	 * @param $template
	 *
	 * @return string
	 */
	public static function template_loader( $template ) {
		if ( is_embed() ) {
			return $template;
		}

		$default_file = self::get_template_loader_default_file();

		if ( $default_file ) {
			$search_files = self::get_template_loader_files( $default_file );
			$template     = locate_template( $search_files );
			if ( ! $template ) {
				$template = mg_upc_get_templates_path() . '/' . $default_file;
			}
		}

		return $template;
	}

	/**
	 * If reserved page is requested return the upc single template
	 *
	 * @return string
	 */
	private static function get_template_loader_default_file() {

		$default_file = '';
		if ( is_singular( 'page' ) ) {
			if ( self::is_requesting_list_page() ) {
				if ( false === self::get_list_requested() ) {
					$default_file = '404.php';
				} else {
					$default_file = 'single-mg-upc.php';
				}
			}
		}

		return $default_file;
	}

	/**
	 * Get an array of filenames to search for a given template.
	 *
	 * @param $default_file
	 *
	 * @return mixed|void
	 */
	private static function get_template_loader_files( $default_file ) {
		$templates = apply_filters( 'mg_upc_template_loader_files', array(), $default_file );

		$templates[] = $default_file;
		$templates[] = 'mg-upc/' . $default_file;

		return array_unique( $templates );
	}

	/**
	 * Get the page ID for collection single
	 *
	 * @return int The id of reserved page, or -1 if not saved
	 */
	public static function get_page_id() {
		$id = get_option( 'mg_upc_single_page', '' );
		if ( ! empty( $id ) && is_numeric( $id ) && is_string( get_post_status( $id ) ) ) {
			return absint( $id );
		}
		return -1;
	}

	/**
	 * Add the upc query vars
	 *
	 * @param $vars
	 *
	 * @return mixed
	 */
	public function add_list_query_var( $vars ) {
		$vars[] = 'list';
		$vars[] = 'list-page';

		return $vars;
	}

	/**
	 * Add the list link to returned list from API
	 *
	 * @param $list
	 *
	 * @return mixed
	 */
	public function add_link_to_list_response( $list ) {

		if ( mg_upc_is_list_publicly_viewable( $list ) ) {
			$list['link'] = $this->get_list_url( $list );
		}

		return $list;
	}

	/**
	 * Check if the requested page is the reserved page
	 *
	 * @return bool
	 */
	private static function is_requesting_list_page() {
		$post = get_queried_object();
		if ( ! $post instanceof WP_Post || 'page' !== $post->post_type ) {
			return false;
		}
		if ( empty( $post->ID ) || self::$page_id !== $post->ID ) {
			return false;
		}

		return true;
	}

	/**
	 * Set and get list requested
	 *
	 * @param bool $check_list_req
	 *
	 * @return array|bool
	 */
	public static function get_list_requested( $check_list_req = true ) {

		if ( isset( $GLOBALS['mg_upc_list'] ) ) {
			return $GLOBALS['mg_upc_list'];
		}

		if ( $check_list_req && ! self::is_requesting_list_page() ) {
			return false;
		}

		if ( ! empty( get_query_var( 'list', false ) ) ) {
			return self::set_global_list( get_query_var( 'list', false ) );
		}

		return false;
	}

	/**
	 * Set global list
	 *
	 * @param $list
	 *
	 * @return array|bool|object|WP_Error
	 */
	private static function set_global_list( $list ) {
		$GLOBALS['mg_upc_list'] = false;

		$list = $GLOBALS['mg_upc']->model->find_one( $list );

		if ( $list && mg_upc_is_list_publicly_viewable( $list ) ) {
			$GLOBALS['mg_upc_list'] = MG_UPC_List_Controller::get_instance()->get_list_for_response(
				array(
					'id'             => (int) $list->ID,
					'items_per_page' => (int) get_option( 'mg_upc_item_per_page', 50 ),
					'items_page'     => get_query_var( 'list-page', 1 ),
				)
			);

			if ( is_wp_error( $GLOBALS['mg_upc_list'] ) ) {
				$GLOBALS['mg_upc_list'] = false;
			}

			return $GLOBALS['mg_upc_list'];
		}

		return false;
	}

	/**
	 * Replace page title for pre_get_document_title and SEO titles
	 *
	 * @param string        $old_title
	 * @param bool|object   $presentation
	 *
	 * @return string
	 */
	public function list_title( $old_title, $presentation = false ) {
		$list = self::get_list_requested();
		if ( ! empty( $list ) ) {
			$parts     = array(
				'%title%'    => $list['title'],
				'%author%'   => MG_UPC_Helper::get_instance()->get_user_login( $list['author'] ),
				'%sitename%' => get_bloginfo( 'name' ),
			);
			$template  = get_option( 'mg_upc_single_title', '%title% by %author% | User List | %sitename%' );
			$new_title = str_replace( array_keys( $parts ), array_values( $parts ), $template );
			return apply_filters( 'mg_upc_list_doc_title_replace', $new_title, $old_title, $presentation );
		}

		return $old_title;
	}

	/**
	 * Replace the title of the page, ex: on template
	 * @param $title
	 * @param $id
	 *
	 * @return mixed
	 */
	public function the_title( $title, $id ) {
		if ( self::$page_id === (int) $id ) {
			remove_filter( 'the_title', array( $this, 'the_title' ), 10 );
			$list = self::get_list_requested();
			if ( ! empty( $list ) ) {
				$title = $list['title'];
			}
			add_filter( 'the_title', array( $this, 'the_title' ), 10, 2 );
		}
		return $title;
	}

	/**
	 * Replace description for SEO and opengraph
	 *
	 * @param string      $desc
	 * @param bool|object $presentation
	 *
	 * @return mixed
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function list_desc( $desc, $presentation = false ) {
		$list = self::get_list_requested();
		if ( ! empty( $list ) ) {
			return wp_strip_all_tags( $list['content'] );
		}

		return $desc;
	}

	/**
	 * Replace canonical for SEO and opengraph
	 *
	 * @param string      $link
	 * @param bool|object $presentation
	 *
	 * @return mixed
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function list_canonical( $link, $presentation = false ) {
		$list = self::get_list_requested();
		if ( ! empty( $list ) ) {
			return $this->get_list_url( $list );
		}

		return $link;
	}

	/**
	 * Set images for opengraph
	 *
	 * @param $image_container
	 *
	 * @return mixed
	 */
	public function list_opengraph_image( $image_container ) {
		$list = self::get_list_requested();
		if ( ! empty( $list ) ) {
			$items = $list['items'];

			$count = 0;
			foreach ( $items as $item ) {
				if ( ! empty( $item['featured_media'] ) ) {
					$image_container->add_image_by_id( $item['featured_media'] );
					$count++;
				} elseif ( ! empty( $item['image'] ) ) {
					$image_container->add_image_by_url( $item['image'] );
					$count++;
				}
				if ( $count >= 3 ) {
					break;
				}
			}
		}

		return $image_container;
	}

	/**
	 * Shortcode on the reserved page
	 *
	 * @param array $atts
	 *
	 * @return false|string
	 */
	public function list_shortcode( $atts = array() ) {
		if ( isset( $atts['id'] ) ) {
			self::set_global_list( (int) $atts['id'] );
		}

		// Using page template title:
		remove_action( 'mg_upc_single_list_content', 'mg_upc_template_single_title', 5 );

		ob_start();
		if ( ! isset( $GLOBALS['mg_upc_list'] ) || false === $GLOBALS['mg_upc_list'] ) {
			mg_upc_get_template( 'shortcode-404.php' );
		} else {
			mg_upc_get_template( 'content-single-mg-upc.php' );
		}
		return ob_get_clean();
	}

	/**
	 * Get the lint url
	 *
	 * @param array|object $list
	 *
	 * @return string|void
	 */
	public function get_list_url( $list ) {
		$slug = '';
		if ( is_array( $list ) && ! empty( $list['slug'] ) ) {
			$slug = $list['slug'];
		} elseif ( is_object( $list ) && property_exists( $list, 'slug' ) ) {
			$slug = $list->slug;
		}
		if ( ! empty( $slug ) ) {
			if ( self::$page_id > 0 ) {
				$val = get_page_link( self::$page_id );
				if ( ! empty( $val ) ) {
					$val = wp_make_link_relative( $val );
					if ( false === strpos( $val, '?' ) ) {
						return home_url( '/' . trim( $val, '/' ) . '/' . rawurlencode( $slug ) );
					}
					return home_url( '/' . add_query_arg( array( 'list' => rawurlencode( $slug ) ), $val ) );
				}
			}
		}
		return '';
	}

	/**
	 * On plugin activated
	 *
	 * @param bool $network_wide
	 */
	public function activate( $network_wide ) {

		update_option( 'mg_upc_flush_rewrite', '1' );

		self::$page_id = self::get_page_id();
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

		$this->add_rewrite();
	}

	/**
	 * Add settings filed for manage page
	 *
	 * @param $settings_fields
	 *
	 * @return mixed
	 */
	public function add_settings_fields( $settings_fields ) {
		$new                               = array(
			array(
				'name'                     => 'mg_upc_single_page',
				'label'                    => __( 'Collection Page', 'user-post-collections' ),
				'desc'                     => __( 'make sure the shortcode [user_post_collection] is present on the selected page', 'user-post-collections' ),
				'default'                  => self::get_page_id(),
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

		return $settings_fields;
	}

	public function deactivate() {
		self::$page_id = self::get_page_id();
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
	}
}
