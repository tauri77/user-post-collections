<?php

if ( ! function_exists( 'mg_upc_template_single_title' ) ) {

	/**
	 * Output the list title.
	 */
	function mg_upc_template_single_title() {
		mg_upc_get_template( 'single-mg-upc/title.php' );
	}
}

if ( ! function_exists( 'mg_upc_template_single_author' ) ) {

	/**
	 * Output the list author.
	 */
	function mg_upc_template_single_author() {
		mg_upc_get_template( 'single-mg-upc/author.php' );
	}
}

if ( ! function_exists( 'mg_upc_template_single_sharing' ) ) {

	/**
	 * Output the share buttons.
	 */
	function mg_upc_template_single_sharing() {
		mg_upc_get_template( 'single-mg-upc/sharing.php' );
	}
}

if ( ! function_exists( 'mg_upc_template_single_description' ) ) {

	/**
	 * Output the list author.
	 */
	function mg_upc_template_single_description() {
		mg_upc_get_template( 'single-mg-upc/description.php' );
	}
}


if ( ! function_exists( 'mg_upc_template_single_items' ) ) {

	/**
	 * Output the list items.
	 */
	function mg_upc_template_single_items() {
		mg_upc_get_template( 'single-mg-upc/items.php' );
	}
}

if ( ! function_exists( 'mg_upc_template_no_items_found' ) ) {

	/**
	 * Empty list collections
	 */
	function mg_upc_template_no_items_found() {
		mg_upc_get_template( 'single-mg-upc/empty-items.php' );
	}
}



if ( ! function_exists( 'mg_upc_template_items_pagination' ) ) {

	/**
	 * Output the list items.
	 */
	function mg_upc_template_items_pagination() {
		global $mg_upc_list;

		$args = array(
			'total'   => $mg_upc_list['items_page']['X-WP-TotalPages'],
			'current' => $mg_upc_list['items_page']['X-WP-Page'],
			'base'    => esc_url_raw( add_query_arg( 'list-page', '%#%', false ) ),
			'format'  => '?list-page=%#%',
		);

		mg_upc_get_template( 'single-mg-upc/pagination.php', $args );
	}
}

if ( ! function_exists( 'mg_upc_single_product_button' ) ) {

	/**
	 * Output the button "Add to list..."
	 */
	function mg_upc_single_product_button() {
		mg_upc_get_template( 'mg-upc-wc/single-product-button.php' );
	}
}

if ( ! function_exists( 'mg_upc_single_item_vote_button' ) ) {

	/**
	 * Output the button "Vote"
	 */
	function mg_upc_single_item_vote_button() {
		global $mg_upc_list;
		if ( MG_UPC_Helper::get_instance()->list_type_support( $mg_upc_list['type'], 'vote', true ) ) {
			mg_upc_get_template( 'single-mg-upc/item/actions/vote.php' );
		}
	}
}


if ( ! function_exists( 'mg_upc_single_list_item_vote_data' ) ) {

	/**
	 * Output the vote data
	 */
	function mg_upc_single_list_item_vote_data() {
		global $mg_upc_list;
		if ( MG_UPC_Helper::get_instance()->list_type_support( $mg_upc_list['type'], 'vote', true ) ) {
			mg_upc_get_template( 'single-mg-upc/item/vote-data.php' );
		}
	}
}

if ( ! function_exists( 'mg_upc_single_list_item_numbered_position' ) ) {

	/**
	 * Output the position number
	 */
	function mg_upc_single_list_item_numbered_position() {
		global $mg_upc_list;
		if ( 'numbered' === $mg_upc_list['type'] ) {
			mg_upc_get_template( 'single-mg-upc/item/position-number.php' );
		}
	}
}

if ( ! function_exists( 'mg_upc_loop_product_button' ) ) {

	/**
	 * Output the button "Add to list..." on loop product
	 */
	function mg_upc_loop_product_button() {
		mg_upc_get_template( 'mg-upc-wc/loop-product-button.php' );
	}
}

if ( ! function_exists( 'mg_upc_btn_classes' ) ) {
	/**
	 * Get the classes for buttons
	 *
	 * @param string $class
	 *
	 * @return string
	 */
	function mg_upc_btn_classes( $class ) {
		//TODO: option to add or not this classes
		return $class . ' button wp-block-button__link';
	}
}

if ( ! function_exists( 'mg_upc_get_theme_slug_for_templates' ) ) {
	/**
	 * Get a slug identifying the current theme.
	 *
	 * @return string
	 */
	function mg_upc_get_theme_slug_for_templates() {
		return apply_filters( 'mg_upc_theme_slug_for_templates', get_option( 'template' ) );
	}
}

if ( ! function_exists( 'mg_upc_output_content_wrapper' ) ) {

	/**
	 * Output the start of the page wrapper.
	 */
	function mg_upc_output_content_wrapper() {
		mg_upc_get_template( 'global/wrapper-start.php' );
	}
}

if ( ! function_exists( 'mg_upc_output_content_wrapper_end' ) ) {

	/**
	 * Output the end of the page wrapper.
	 */
	function mg_upc_output_content_wrapper_end() {
		mg_upc_get_template( 'global/wrapper-end.php' );
	}
}

if ( ! function_exists( 'mg_upc_get_text' ) ) {

	/**
	 * Get a text mutated by translate or by settings.
	 *
	 * @param string $text
	 * @param string $context
	 *
	 * @return string
	 */
	function mg_upc_get_text( $text, $context = 'mg_upc_list' ) {
		return MG_UPC_Texts::get( $text, $context );
	}
}


if ( ! function_exists( 'mg_upc_show_item_quantity' ) ) {

	/**
	 * Show item quantity.
	 */
	function mg_upc_show_item_quantity() {
		global $mg_upc_list;
		if ( MG_UPC_Helper::get_instance()->list_type_support( $mg_upc_list['type'], 'quantity', true ) ) {
			mg_upc_get_template( 'single-mg-upc/item/quantity.php' );
		}
	}
}



/****************************************************************
 *            ARCHIVE
 ****************************************************************/

if ( ! function_exists( 'mg_upc_template_loop_single_info_start' ) ) {

	/**
	 * Start infodiv
	 */
	function mg_upc_template_loop_single_info_start() {
		echo '<div class="mg-upc-loop-list-info">';
	}
}
if ( ! function_exists( 'mg_upc_template_loop_single_info_end' ) ) {

	/**
	 * Close info div
	 */
	function mg_upc_template_loop_single_info_end() {
		echo '</div>';
	}
}

if ( ! function_exists( 'mg_upc_template_loop_single_title' ) ) {

	/**
	 * Output the list title.
	 */
	function mg_upc_template_loop_single_title() {
		mg_upc_get_template( 'loop/list/title.php' );
	}
}

if ( ! function_exists( 'mg_upc_template_loop_single_author' ) ) {

	/**
	 * Output the list author.
	 */
	function mg_upc_template_loop_single_author() {
		mg_upc_get_template( 'loop/list/author.php' );
	}
}

if ( ! function_exists( 'mg_upc_template_loop_single_meta' ) ) {

	/**
	 * Output the list meta.
	 */
	function mg_upc_template_loop_single_meta() {
		mg_upc_get_template( 'loop/list/meta.php' );
	}
}

if ( ! function_exists( 'mg_upc_template_loop_single_description' ) ) {

	/**
	 * Output list description.
	 */
	function mg_upc_template_loop_single_description() {
		mg_upc_get_template( 'loop/list/description.php' );
	}
}


if ( ! function_exists( 'mg_upc_template_loop_single_thumbs' ) ) {

	/**
	 * Output the list items.
	 */
	function mg_upc_template_loop_single_thumbs() {
		mg_upc_get_template( 'loop/list/items.php' );
	}
}



if ( ! function_exists( 'mg_upc_template_archive_pagination' ) ) {

	/**
	 * Output the archive pagination.
	 */
	function mg_upc_template_archive_pagination() {
		global $mg_upc_query;

		$args = array(
			'total'   => $mg_upc_query->max_num_pages,
			'current' => $mg_upc_query->query_vars['paged'],
			'base'    => esc_url_raw( add_query_arg( 'lists-page', '%#%', false ) ),
			'format'  => '?lists-page=%#%',
		);

		mg_upc_get_template( 'loop/pagination.php', $args );
	}
}



if ( ! function_exists( 'mg_upc_template_loop_empty' ) ) {

	/**
	 * Empty list collections
	 */
	function mg_upc_template_loop_empty() {
		mg_upc_get_template( 'loop/empty.php' );
	}
}





/**
 * Show UPC Loop
 *
 *     Options:
 *         pagination                       Show pagination. Set to "1", it will use the main lists-page variable to show pagination (this should be used with a single page widget, otherwise all widgets/archives will be paginated together)
 *         tpl-items                        (card|list) List type
 *         tpl-cols                         Number of columns, comma separated: xxl,xl,lg,md,sm,xs (for card list type) Default: 4,4,4,3,2,1
 *         tpl-cols-[xs|sm|md|lg|xl|xxl]    (1|2|3|4|5) Number of columns (for card list type). Override "tpl-cols".
 *         tpl-thumbs                       Default thumbnails layout. Set to "off" to not show
 *         tpl-thumbs-[xs|sm|md|lg|xl|xxl]  (0|2x2|2x3|3x2|4x1|[1-4]x[1-4]) Thumbnails layout
 *         tpl-desc                         (on|off) Show description. Set to "off" to hide description
 *         tpl-user                         (on|off) Show author. Set to "off" to hide user
 *         tpl-meta                         (on|off) Show meta. Set to "off" to hide meta
 *
 * @param array $original_atts Array or empty string
 *
 */
function mg_upc_template_loop( $original_atts = array() ) {
	if ( ! is_array( $original_atts ) ) {
		$original_atts = array();
	}

	$defaults_atts = array(
		'pagination'     => null,
		'tpl-desc'       => 'on',
		'tpl-thumbs'     => 'on',
		'tpl-user'       => 'on',
		'tpl-meta'       => 'on',
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
	$atts          = array_merge( $defaults_atts, $original_atts );

	remove_action( 'mg_upc_single_list_content', 'mg_upc_template_single_title', 5 );
	/** @global MG_UPC_Query $mg_upc_query */
	global $mg_upc_query;
	$breakpoints = array( 'xxl', 'xl', 'lg', 'md', 'sm', 'xs' );

	$tpl_cols = explode( ',', $atts['tpl-cols'] );
	foreach ( $breakpoints as $key => $breakpoint ) {
		if ( isset( $tpl_cols[ $key ] ) ) {
			$atts[ 'tpl-cols-' . $breakpoint ] = $tpl_cols[ $key ];
		} elseif ( $key >= 1 && isset( $atts[ 'tpl-cols-' . $breakpoints[ $key - 1 ] ] ) ) {
			$atts[ 'tpl-cols-' . $breakpoint ] = $atts[ 'tpl-cols-' . $breakpoints[ $key - 1 ] ] - 1;
			$atts[ 'tpl-cols-' . $breakpoint ] = max( $atts[ 'tpl-cols-' . $breakpoint ], 1 );
		} elseif ( isset( $breakpoints[ $key + 1 ] ) && isset( $atts[ 'tpl-cols-' . $breakpoints[ $key + 1 ] ] ) ) {
			$atts[ 'tpl-cols-' . $breakpoint ] = $atts[ 'tpl-cols-' . $breakpoints[ $key + 1 ] ];
		}
		if ( ! empty( $original_atts[ 'tpl-cols-' . $breakpoint ] ) ) {
			$atts[ 'tpl-cols-' . $breakpoint ] = $original_atts[ 'tpl-cols-' . $breakpoint ];
		}
	}

	$map_class_tpl_args = array(
		'cols'   => array(
			'prefix'      => 'mg-upc-list-cols-',
			'default'     => 1,
			'breakpoints' => $breakpoints,
		),
		'thumbs' => array(
			'prefix'      => 'mg-upc-thumbs-',
			'default'     => $atts['tpl-thumbs'],
			'breakpoints' => $breakpoints,
		),
		'items'  => array(
			'prefix'  => 'mg-upc-list-',
			'default' => 'list',
		),
	);

	$classes = array();
	foreach ( $map_class_tpl_args as $key => $map_class_tpl_arg ) {
		if ( ! empty( $atts[ 'tpl-' . $key ] ) && '0' !== $atts[ 'tpl-' . $key ] ) {
			$classes[] = $map_class_tpl_arg['prefix'] . $atts[ 'tpl-' . $key ];
		} elseif ( ! empty( $map_class_tpl_arg['default'] ) && '0' !== $map_class_tpl_arg['default'] ) {
			$classes[] = $map_class_tpl_arg['prefix'] . $map_class_tpl_arg['default'];
		}
		if ( ! empty( $map_class_tpl_arg['breakpoints'] ) ) {
			foreach ( $map_class_tpl_arg['breakpoints'] as $breakpoint ) {
				$option_key = 'tpl-' . $key . '-' . $breakpoint;
				if ( ! empty( $atts[ $option_key ] ) && '0' !== $atts[ $option_key ] ) {
					$classes[] = $map_class_tpl_arg['prefix'] . $breakpoint . '-' . $atts[ $option_key ];
				} elseif ( ! empty( $map_class_tpl_arg['default'] ) && '0' !== $map_class_tpl_arg['default'] ) {
					$classes[] = $map_class_tpl_arg['prefix'] . $breakpoint . '-' . $map_class_tpl_arg['default'];
				}
			}
		}
	}

	$max_items = 0;
	if ( 'off' !== $atts['tpl-thumbs'] ) {
		if ( ! empty( $map_class_tpl_args['thumbs']['breakpoints'] ) ) {
			foreach ( $map_class_tpl_args['thumbs']['breakpoints'] as $breakpoint ) {
				if ( isset( $atts[ 'tpl-thumbs-' . $breakpoint ] ) ) {
					$lines     = explode( 'x', $atts[ 'tpl-thumbs-' . $breakpoint ] );
					$max_items = max( $max_items, (int) $lines[0] * (int) ( $lines[1] ?? 0 ) );
				}
			}
		}
		if ( ! empty( $atts['tpl-thumbs'] ) ) {
			$lines     = explode( 'x', $atts['tpl-thumbs'] );
			$max_items = max( $max_items, (int) $lines[0] * (int) ( $lines[1] ?? 0 ) );
		}
	}

	// Set posts to get on query
	$mg_upc_query->set_items_limit( $max_items );

	$template_args = array(
		'mg_classes' => implode( ' ', $classes ),
	);
	if ( $mg_upc_query->is_404 ) {
		mg_upc_get_template( 'archive-404.php', $template_args );
	} else {
		$hooks_to_remove = array();
		if ( isset( $atts['tpl-thumbs'] ) && 'off' === $atts['tpl-thumbs'] ) {
			$hooks_to_remove[] = array(
				'hook'     => 'mg_upc_loop_single_list_content',
				'callback' => 'mg_upc_template_loop_single_thumbs',
			);
		}
		if ( 'off' === $atts['tpl-desc'] ) {
			$hooks_to_remove[] = array(
				'hook'     => 'mg_upc_loop_single_list_content',
				'callback' => 'mg_upc_template_loop_single_description',
			);
		}
		if ( 'off' === $atts['tpl-user'] ) {
			$hooks_to_remove[] = array(
				'hook'     => 'mg_upc_loop_single_list_content',
				'callback' => 'mg_upc_template_loop_single_author',
			);
		}
		if ( 'off' === $atts['tpl-meta'] ) {
			$hooks_to_remove[] = array(
				'hook'     => 'mg_upc_loop_single_list_content',
				'callback' => 'mg_upc_template_loop_single_meta',
			);
		}
		if ( 1 !== (int) $atts['pagination'] ) {
			$hooks_to_remove[] = array(
				'hook'     => 'mg_upc_after_archive_content',
				'callback' => 'mg_upc_template_archive_pagination',
			);
		}
		foreach ( $hooks_to_remove as $k => $hook ) {
			$priority = has_action( $hook['hook'], $hook['callback'] );
			if ( false !== $priority ) {
				remove_action( $hook['hook'], $hook['callback'], $priority );
				$hooks_to_remove[ $k ]['priority'] = $priority;
				$hooks_to_remove[ $k ]['removed']  = true;
			}
		}

		mg_upc_get_template( 'content-archive-mg-upc.php', $template_args );

		foreach ( $hooks_to_remove as $hook ) {
			if ( ! empty( $hook['removed'] ) ) {
				add_action( $hook['hook'], $hook['callback'], $hook['priority'] );
			}
		}
	}
}
