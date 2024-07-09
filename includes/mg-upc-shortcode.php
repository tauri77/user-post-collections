<?php


class MG_UPC_Shortcode extends MG_UPC_Module {


	public function __construct() {
	}

	public function init() {
		add_shortcode( 'user_posts_collections', array( $this, 'shortcode' ) );
	}

	/**
	 * UPC Shortcode
	 *
	 * [user_posts_collections type=vote author-name="tauri" author=332 include="23,31,412" exclude="32,45" orderby="title" order="ASC" limit=10 pagination=1]
	 *
	 *     Options:
	 *         type                             simple|numbered|vote|favorites|bookmarks|cart
	 *         author-name                      Author username
	 *         author                           Author ID
	 *         include                          Lists ID to include (comma separated)
	 *         exclude                          Lists ID to exclude (comma separated)
	 *         orderby                          ID|views|vote_counter|count|created|modified|title
	 *         order                            ASC|DESC
	 *         limit                            Max lists to show
	 *         pagination                       Show pagination. Set to "1", it will use the main lists-page variable to show pagination (this should be used with a single page widget, otherwise all widgets/archives will be paginated together)
	 *         tpl-items                        (card|list) List type
	 *         tpl-cols                         Number of columns, comma separated: xxl,xl,lg,md,sm,xs (for card list type) Default: 4,4,4,3,2,1
	 *         tpl-cols-[xs|sm|md|lg|xl|xxl]    (1|2|3|4|5) Number of columns (for card list type)
	 *         tpl-thumbs                       Default thumbnails layout. Set to "off" to not show
	 *         tpl-thumbs-[xs|sm|md|lg|xl|xxl]  (0|2x2|2x3|3x2|4x1|[1-4]x[1-4]) Thumbnails layout
	 *         tpl-desc                         (on|off) Show description. Set to "off" to hide description
	 *         tpl-user                         (on|off) Show author. Set to "off" to hide user
	 *         tpl-meta                         (on|off) Show meta. Set to "off" to hide meta
	 *
	 * @param array $atts Array or empty string
	 *
	 * @return false|string
	 */
	public function shortcode( $atts = array() ) {
		if ( ! is_array( $atts ) ) {
			$atts = array();
		}
		$defaults_atts = array(
			'pagination'     => null,
			'orderby'        => false,
			'order'          => 'asc',
			'limit'          => 12,
			'tpl-desc'       => '1',
			'tpl-thumbs'     => '2x2',
			'tpl-user'       => '1',
			'tpl-meta'       => '1',
			'tpl-items'      => 'list',
			'tpl-cols'       => '4,4,4,3,2,1',
			'tpl-cols-xxl'   => false,
			'tpl-cols-xl'    => false,
			'tpl-cols-lg'    => false,
			'tpl-cols-md'    => false,
			'tpl-cols-sm'    => false,
			'tpl-cols-xs'    => false,
			'tpl-thumbs-xxl' => false,
			'tpl-thumbs-xl'  => false,
			'tpl-thumbs-lg'  => false,
			'tpl-thumbs-md'  => false,
			'tpl-thumbs-sm'  => false,
			'tpl-thumbs-xs'  => '4x1',
		);

		$atts       = array_merge( $defaults_atts, $atts );
		$attrs_args = array(
			'type'        => 'list_type',
			'author-name' => 'author_name',
			'author'      => 'author',
			'include'     => 'list__in',
			'exclude'     => 'list__not_in',
			'limit'       => 'lists_per_page',
		);

		$query_args = array(
			'paged'   => empty( $atts['pagination'] ) ? 1 : (int) get_query_var( 'lists-page', 1 ),
			'orderby' => get_query_var( 'lists-orderby', $atts['orderby'] ),
			'order'   => get_query_var( 'lists-order', $atts['order'] ),
		);
		foreach ( $attrs_args as $attr => $arg ) {
			if ( isset( $atts[ $attr ] ) ) {
				$query_args[ $arg ] = $atts[ $attr ];
			}
		}
		$string_to_array = array( 'list__in', 'list__not_in' );
		foreach ( $string_to_array as $attr ) {
			if ( isset( $query_args[ $attr ] ) ) {
				$query_args[ $attr ] = explode( ',', $query_args[ $attr ] );
			}
		}

		ob_start();
		global $mg_upc_query;
		$mg_upc_query = new MG_UPC_Query( $query_args );
		mg_upc_template_loop( $atts );

		return ob_get_clean();
	}

	public function activate( $network_wide ) {
	}

	public function deactivate() {
	}

	public function register_hook_callbacks() {
	}

	public function upgrade( $db_version = 0 ) {
	}
}
