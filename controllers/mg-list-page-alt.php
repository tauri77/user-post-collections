<?php

class MG_UPC_List_Page extends MG_UPC_Module {

	/**
	 * @var int The id of page selected for list
	 */
	private static $page_id = 0;

	private static $doing_shortcodes = false;

	public function __construct() {
		// Add base url hook
		add_filter( 'mg_upc_base_url', array( 'MG_UPC_List_Page', 'base_url_filter' ), 10, 1 );
	}

	public function init() {

		//Search page saved as collection single page (Created on the activated)
		self::$page_id = self::get_page_id();

		if ( self::$page_id > 0 ) {
			// Add query vars for collection page. Example: list and list-page (for pagination)
			add_filter( 'query_vars', array( $this, 'add_list_query_var' ) );
			// Add the rewrite rule using slug from $page_id
			self::add_rewrite();
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
		add_filter( 'mg_upc_get_the_permalink', array( $this, 'filter_get_the_permalink' ), 10, 2 );
		add_filter( 'mg_upc_list_url', array( $this, 'mg_upc_list_url' ), 10, 2 );

		/* Image Hook*/
		add_filter( 'wpseo_add_opengraph_images', array( $this, 'list_opengraph_image' ), 10, 1 );

		/* Shortcode */
		add_shortcode( 'user_post_collection', array( $this, 'page_shortcode' ) );

		/* Templates hook */
		if ( get_option( 'mg_upc_single_page_mode', 'template_page' ) === 'template_upc' ) {
			add_filter( 'template_include', array( 'MG_UPC_List_Page', 'template_loader' ) );
		}

		/* Set global $mg_upc_list if query a collection*/
		add_action( 'parse_request', array( $this, 'parse_request' ), 90, 1 );

		/* Remove page links from head */
		add_action( 'template_redirect', array( $this, 'remove_links' ) );
	}

	/**
	 * If list page is requested set variables
	 *
	 * @param $query
	 *
	 * @return mixed
	 */
	public function parse_request( $query ) {
		global $mg_upc_the_query;
		if ( ! empty( $query->query_vars['pagename'] ) ) {
			$page = WP_Post::get_instance( self::$page_id );
			if ( $page && $page->post_name === $query->query_vars['pagename'] ) {
				$query->query_vars['page_id'] = self::$page_id;
			}
		}

		if (
			isset( $query->query_vars['page_id'] ) &&
			(int) self::$page_id === (int) $query->query_vars['page_id']
		) {
			$atts = array();
			if ( isset( $query->query_vars['list'] ) ) {
				if ( is_int( $query->query_vars['list'] ) ) {
					$atts['ID'] = $query->query_vars['list'];
				} elseif ( is_string( $query->query_vars['list'] ) ) {
					$atts['name'] = $query->query_vars['list'];
				}
				$atts['lists_per_page'] = 1;
			} elseif ( 'on' === get_option( 'mg_upc_archive_enable', 'on' ) ) {
				if ( 'on' === get_option( 'mg_upc_archive_filter_author', 'on' ) ) {
					$atts['author']      = $query->query_vars['list-author'] ?? '';
					$atts['author_name'] = $query->query_vars['list-author-name'] ?? '';
				}
				if ( 'on' === get_option( 'mg_upc_archive_filter_type', 'on' ) ) {
					$atts['list_type'] = $query->query_vars['list-type'] ?? '';
				}
				$atts['paged']          = $query->query_vars['lists-page'] ?? 1;
				$atts['orderby']        = $query->query_vars['lists-orderby'] ?? '';
				$atts['order']          = $query->query_vars['lists-order'] ?? '';
				$atts['lists_per_page'] = get_option( 'mg_upc_archive_item_per_page', 12 );
			} else {
				return $query;
			}
			$mg_upc_the_query = new MG_UPC_Query( $atts );

			if ( $mg_upc_the_query->is_single() ) {
				remove_filter( 'the_content', array( 'MG_UPC_Buttons', 'the_content' ) );
			}
		}
		return $query;
	}

	/**
	 * Set the collection "single" url
	 */
	public static function add_rewrite() {
		$base_url = self::get_base_url( true );
		if ( ! empty( $base_url ) && self::$page_id > 0 ) {
			$reg = '^' . trim( $base_url, '/' ) . '/([A-Za-z0-9\._\-@ ]+)/?$';
			add_rewrite_rule(
				$reg,
				'index.php?page_id=' . self::$page_id . '&post_type=page&list=$matches[1]',
				'top'
			);
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
				$template = trailingslashit( mg_upc_get_templates_path() ) . $default_file;
			}

			// Set global query
			global $mg_upc_the_query, $mg_upc_query;
			if ( ! empty( $mg_upc_the_query ) ) {
				$mg_upc_query = $mg_upc_the_query;
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
				if (
					empty( get_query_var( 'list', false ) ) &&
					'on' === get_option( 'mg_upc_archive_enable', 'on' )
				) {
					$default_file = 'archive-mg-upc.php';
				} elseif ( false === self::get_list_requested() ) {
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
		$vars[] = 'list-type';
		$vars[] = 'list-author';
		$vars[] = 'list-author-name';
		$vars[] = 'lists-page';
		$vars[] = 'lists-orderby';
		$vars[] = 'lists-order';

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
	 * Set the list link
	 *
	 * @param string|null $url
	 * @param MG_UPC_List $list
	 *
	 * @return null|string
	 */
	public function mg_upc_list_url( $url, $list ) {

		if ( mg_upc_is_list_publicly_viewable( $url ) ) {
			return $this->get_list_url( $list );
		}

		return $url;
	}

	/**
	 * Set the list link
	 *
	 * @param $permalink
	 * @param $list
	 *
	 * @return string
	 */
	public function filter_get_the_permalink( $permalink, $list ) {
		$link = $this->get_list_url( $list );

		return empty( $link ) ? $permalink : $link;
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
	 * @return MG_UPC_List|bool
	 */
	public static function get_list_requested( $check_list_req = true ) {
		/** @global MG_UPC_Query $mg_upc_query */
		global $mg_upc_the_query;
		if ( ! $mg_upc_the_query || ! $mg_upc_the_query->is_single() ) {
			return false;
		}

		if ( isset( $GLOBALS['mg_upc_list'] ) ) {
			return $GLOBALS['mg_upc_list'];
		}

		if ( $check_list_req && ! self::is_requesting_list_page() ) {
			return false;
		}

		if ( $mg_upc_the_query && $mg_upc_the_query->is_single() && $mg_upc_the_query->have_lists() ) {
			$mg_upc_the_query->the_list();
			return $GLOBALS['mg_upc_list'];
		}

		return false;
	}

	/**
	 * Set global list
	 *
	 * @param $list
	 *
	 * @return bool|MG_UPC_List|object
	 */
	private static function set_global_list( $list ) {
		$GLOBALS['mg_upc_list'] = false;

		$list = $GLOBALS['mg_upc']->model->find_one( $list );

		if ( $list && mg_upc_is_list_publicly_viewable( $list ) ) {
			$GLOBALS['mg_upc_list'] = MG_UPC_List::get_instance( $list );
			if ( ! empty( $GLOBALS['mg_upc_list']->errors ) ) {
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
		global $mg_upc_the_query;

		$new_title = false;

		$list = self::get_list_requested();
		if ( $list instanceof MG_UPC_List && $list->ID > 0 ) {
			$parts     = array(
				'%title%'    => $list['title'],
				'%author%'   => MG_UPC_Helper::get_instance()->get_user_login( $list['author'] ),
				'%sitename%' => get_bloginfo( 'name' ),
			);
			$template  = get_option( 'mg_upc_single_title', '%title% by %author% | User Lists | %sitename%' );
			$new_title = str_replace( array_keys( $parts ), array_values( $parts ), $template );
		} else {
			if ( $mg_upc_the_query ) {
				if ( $mg_upc_the_query->is_author() ) {
					$new_title = $this->get_author_title( $mg_upc_the_query->is_type(), true );
				} elseif ( $mg_upc_the_query->is_type() ) {
					$new_title = $this->get_type_title( true );
				} elseif ( self::is_requesting_list_page() ) {
					$new_title = $this->get_archive_title(
						'mg_upc_archive_title',
						array(),
						true,
						'User Lists'
					);
				}
			}
		}
		if ( false !== $new_title ) {
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
			if ( $list instanceof MG_UPC_List && $list->ID > 0 ) {
				$title = $list['title'];
			} else {
				/** @global $mg_upc_the_query MG_UPC_Query */
				global $mg_upc_the_query;
				if ( $mg_upc_the_query ) {
					if ( $mg_upc_the_query->is_author() ) {
						$title = $this->get_author_title( $mg_upc_the_query->is_type(), false );
					} elseif ( $mg_upc_the_query->is_type() ) {
						$title = $this->get_type_title( false );
					} elseif ( self::is_requesting_list_page() ) {
						$title = $this->get_archive_title(
							'mg_upc_archive_title',
							array(),
							false,
							'User Lists'
						);
					}
				}
			}
			add_filter( 'the_title', array( $this, 'the_title' ), 10, 2 );
		}
		return $title;
	}

	/**
	 * Get the title for list types archive lists
	 *
	 * @return string
	 */
	private function get_type_title( $document_title ) {
		return $this->get_archive_title(
			'mg_upc_archive_title_type',
			array( '%type%' => self::get_query_type() ),
			$document_title,
			'User Lists | %type%'
		);
	}

	/**
	 * Get the title for an author archive lists
	 *
	 * @return string
	 */
	private function get_author_title( $with_type, $document_title ) {
		$author = self::get_query_login();

		if ( empty( $author ) ) {
			$new_title = __( 'User not found', 'user-post-collections' );
		} else {
			$option  = 'mg_upc_archive_title_author';
			$default = __( 'Lists created by %author%', 'user-post-collections' );
			$parts   = array( '%author%' => $author );
			if ( $with_type ) {
				$parts['%type%'] = self::get_query_type();
				$option          = 'mg_upc_archive_title_author_type';
				$default         = __( 'Lists created by %author% | %type%', 'user-post-collections' );
			}
			$new_title = $this->get_archive_title( $option, $parts, $document_title, $default );
		}
		return $new_title;
	}

	private function get_archive_title( $template_option, $parts, $document_title, $default_template ) {
		$parts['%sitename%'] = get_bloginfo( 'name' );

		$template  = get_option( $template_option, $default_template );
		$new_title = str_replace( array_keys( $parts ), array_values( $parts ), $template );

		if ( $document_title ) {
			$template = get_option( 'mg_upc_archive_document_title', '%upctitle% | %sitename%' );
			$parts    = array(
				'%sitename%' => get_bloginfo( 'name' ),
				'%upctitle%' => $new_title,
			);

			$new_title = str_replace( array_keys( $parts ), array_values( $parts ), $template );
		}
		return $new_title;
	}

	public static function get_query_type( $query = null ) {
		if ( ! $query ) {
			global $mg_upc_query, $mg_upc_the_query;
			if ( ! empty( $mg_upc_query ) ) {
				$query = $mg_upc_query;
			} elseif ( ! empty( $mg_upc_the_query ) ) {
				$query = $mg_upc_the_query;
			} else {
				return '';
			}
		}
		$type_labels = array();
		if ( $query->get( 'list_type', true ) ) {
			$types = $query->get( 'list_type', true );
			foreach ( $types as $type ) {
				$list_type = MG_UPC_Helper::get_instance()->get_list_type( $type, true );
				if ( $list_type ) {
					$type_labels[] = $list_type['plural_label'];
				}
			}
		}

		return implode( ', ', $type_labels );
	}
	public static function get_query_login( $query = null ) {
		if ( ! $query ) {
			global $mg_upc_query, $mg_upc_the_query;
			if ( ! empty( $mg_upc_query ) ) {
				$query = $mg_upc_query;
			} elseif ( ! empty( $mg_upc_the_query ) ) {
				$query = $mg_upc_the_query;
			} else {
				return '';
			}
		}
		$author_login = '';
		if ( $query->get( 'author', false ) ) {
			$author_login = MG_UPC_Helper::get_instance()->get_user_login( (int) $query->get( 'author', false ) );
		}
		if ( $query->get( 'author_name', false ) ) {
			$author_name = mg_upc_sanitize_username( $query->get( 'author_name', false ) );
			$author      = get_user_by( 'slug', $author_name );
			if ( $author ) {
				$author_login = $author->user_login;
			}
		}

		return $author_login;
	}

	/**
	 * Replace description for SEO and opengraph
	 *
	 * @param string      $desc
	 * @param bool|object $presentation
	 *
	 * @return string
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function list_desc( $desc, $presentation = false ) {
		$list = self::get_list_requested();
		if ( $list instanceof MG_UPC_List && $list->ID > 0 ) {
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
	 * @return string|null
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function list_canonical( $link, $presentation = false ) {
		$list = self::get_list_requested();
		if ( $list instanceof MG_UPC_List && $list->ID > 0 ) {
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
		if ( $list instanceof MG_UPC_List && $list->ID > 0 ) {
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
	 * Shortcode on the reserved page ( legacy )
	 *
	 * @param array $atts Array or empty string
	 *
	 * @return false|string
	 *
	 * @deprecated since 0.9.0
	 */
	public function list_shortcode( $atts = array() ) {
		return $this->page_shortcode( $atts );
	}

	/**
	 * Shortcode on the reserved page
	 *
	 * @param array $atts Array or empty string
	 *
	 * @return false|string
	 */
	public function page_shortcode( $atts = array() ) {
		global $mg_upc_the_query, $mg_upc_query;
		if ( self::$doing_shortcodes ) {
			return '';
		}
		self::$doing_shortcodes = true;
		ob_start();
		if ( ! is_array( $atts ) ) {
			$atts = array();
		}
		// Using page template title:
		remove_action( 'mg_upc_single_list_content', 'mg_upc_template_single_title', 5 );

		if ( isset( $atts['id'] ) ) {
			self::set_global_list( (int) $atts['id'] );
			if ( empty( $GLOBALS['mg_upc_list'] ) ) {
				mg_upc_get_template( 'shortcode-404.php' );
			} else {
				mg_upc_get_template( 'content-single-mg-upc.php' );
			}
			return ob_get_clean();
		}
		if ( ! empty( $mg_upc_the_query ) ) {
			mg_upc_reset_query();
		}
		if ( ! isset( $mg_upc_query ) ) {
			self::$doing_shortcodes = false;
			return '';
		}
		if ( $mg_upc_query->is_single() ) {
			if ( empty( $GLOBALS['mg_upc_list'] ) ) {
				mg_upc_get_template( 'shortcode-404.php' );
			} else {
				mg_upc_get_template( 'content-single-mg-upc.php' );
			}
		} elseif ( 'on' === get_option( 'mg_upc_archive_enable', 'on' ) ) {
			$atts = array(
				'pagination'     => 1,
				'tpl-desc'       => get_option( 'mg_upc_archive_item_template_desc', 'off' ),
				'tpl-thumbs'     => '2x2',
				'tpl-user'       => get_option( 'mg_upc_archive_item_template_user', 'on' ),
				'tpl-meta'       => get_option( 'mg_upc_archive_item_template_meta', 'on' ),
				'tpl-items'      => get_option( 'mg_upc_archive_item_template', 'list' ),
				'tpl-cols-xxl'   => get_option( 'mg_upc_archive_item_template_cols_xxl', '4' ),
				'tpl-cols-xl'    => get_option( 'mg_upc_archive_item_template_cols_xl', '4' ),
				'tpl-cols-lg'    => get_option( 'mg_upc_archive_item_template_cols_lg', '4' ),
				'tpl-cols-md'    => get_option( 'mg_upc_archive_item_template_cols_md', '3' ),
				'tpl-cols-sm'    => get_option( 'mg_upc_archive_item_template_cols_sm', '2' ),
				'tpl-cols-xs'    => get_option( 'mg_upc_archive_item_template_cols_xs', '1' ),
				'tpl-thumbs-xxl' => get_option( 'mg_upc_archive_item_template_thumbs_xxl', '2x2' ),
				'tpl-thumbs-xl'  => get_option( 'mg_upc_archive_item_template_thumbs_xl', '2x2' ),
				'tpl-thumbs-lg'  => get_option( 'mg_upc_archive_item_template_thumbs_lg', '2x2' ),
				'tpl-thumbs-md'  => get_option( 'mg_upc_archive_item_template_thumbs_md', '2x2' ),
				'tpl-thumbs-sm'  => get_option( 'mg_upc_archive_item_template_thumbs_sm', '2x2' ),
				'tpl-thumbs-xs'  => get_option( 'mg_upc_archive_item_template_thumbs_xs', '4x1' ),
			);
			mg_upc_template_loop( $atts );
		} else {
			mg_upc_get_template( 'shortcode-404.php' );
		}

		self::$doing_shortcodes = false;
		return ob_get_clean();
	}

	/**
	 * Get the list url
	 *
	 * @param array|object $list
	 *
	 * @return string
	 */
	public function get_list_url( $list ) {
		$slug = '';
		if ( is_array( $list ) && ! empty( $list['slug'] ) ) {
			$slug = $list['slug'];
		} elseif ( is_object( $list ) && property_exists( $list, 'slug' ) ) {
			$slug = $list->slug;
		}
		if ( ! empty( $slug ) ) {
			$val = self::get_base_url( true );
			if ( ! empty( $val ) ) {
				if ( false === strpos( $val, '?' ) ) {
					return home_url( '/' . trim( $val, '/' ) . '/' . rawurlencode( $slug ) );
				}
				return home_url( '/' . add_query_arg( array( 'list' => rawurlencode( $slug ) ), $val ) );
			}
		}
		return '';
	}

	public static function base_url_filter( $url ) {
		if ( 0 === self::$page_id ) {
			self::$page_id = self::get_page_id();
		}
		if ( self::$page_id > 0 ) {
			$url = get_page_link( self::$page_id );
		}
		return $url;
	}

	public static function get_base_url( $relative = false ) {
		$url = apply_filters( 'mg_upc_base_url', '' );
		if ( $relative ) {
			return wp_make_link_relative( $url );
		}
		return $url;
	}

	public function register_hook_callbacks() { }

	public function upgrade( $db_version = 0 ) { }

	public function activate( $network_wide ) { }

	public function deactivate() { }
}
