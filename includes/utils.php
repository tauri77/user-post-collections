<?php

function mg_upc_list_check_support( $list, $feature ) {
	if ( is_scalar( $list ) ) {
		$list = MG_List_Model::get_instance()->find_one( $list );
	}
	$list_type = MG_UPC_Helper::get_instance()->get_list_type( $list->type, true );

	return $list_type && $list_type->support( $feature );
}

function mg_upc_strlen( $string, $encoding = null ) {
	if ( null === $encoding ) {
		$encoding = get_option( 'blog_charset', 'utf8' );
	}
	try {
		return mb_strlen( $string, $encoding );
	} catch ( Error $e ) {
		return strlen( $string );
	}
}

function mg_upc_get_templates_path() {
	return untrailingslashit( plugin_dir_path( MG_UPC_PLUGIN_FILE ) ) . '/templates/';
}

/**
 * Get template part.
 *
 * @param mixed  $slug Template slug.
 * @param string $name Template name (default: '').
 */
function mg_upc_get_template_part( $slug, $name = '' ) {
	$cache_key = sanitize_key( implode( '-', array( 'template-part', $slug, $name ) ) );
	$template  = (string) wp_cache_get( $cache_key, 'mg_upc' );

	if ( ! $template ) {
		if ( $name ) {
			$template = locate_template(
				array(
					"{$slug}-{$name}.php",
					"mg-upc/{$slug}-{$name}.php",
				)
			);

			if ( ! $template ) {
				$fallback = mg_upc_get_templates_path() . "/{$slug}-{$name}.php";
				$template = file_exists( $fallback ) ? $fallback : '';
			}
		}

		if ( ! $template ) {
			// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/mg-upc/slug.php.
			$template = locate_template(
				array(
					"{$slug}.php",
					"mg-upc/{$slug}.php",
				)
			);
			if ( ! $template ) {
				$fallback = mg_upc_get_templates_path() . "/{$slug}.php";
				$template = file_exists( $fallback ) ? $fallback : '';
			}
		}

		wp_cache_set( $cache_key, $template, 'mg_upc' );
	}

	// Allow 3rd party plugins to filter template file from their plugin.
	$template = apply_filters( 'mg_upc_get_template_part', $template, $slug, $name );

	if ( $template ) {
		load_template( $template, false );
	}
}

/**
 * Get other templates (e.g. product attributes) passing attributes and including the file.
 *
 * @param string $template_name Template name.
 * @param array  $args          Arguments. (default: array).
 * @param string $template_path Template path. (default: '').
 * @param string $default_path  Default path. (default: '').
 */
function mg_upc_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	$cache_key = sanitize_key( implode( '-', array( 'template', $template_name, $template_path, $default_path ) ) );
	$template  = (string) wp_cache_get( $cache_key, 'mg_upc' );

	if ( ! $template ) {
		$template = mg_upc_locate_template( $template_name, $template_path, $default_path );

		wp_cache_set( $cache_key, $template, 'mg_upc' );
	}

	// Allow 3rd party plugin filter template file from their plugin.
	$filter_template = apply_filters( 'mg_upc_get_template', $template, $template_name, $args, $template_path, $default_path );

	if ( $filter_template !== $template ) {
		if ( ! file_exists( $filter_template ) ) {
			error_log(
				sprintf(
					/* translators: %s template */
					__( '%s does not exist.', 'user-post-collections' ),
					'<code>' . $filter_template . '</code>'
				)
			);
			return;
		}
		$template = $filter_template;
	}

	$action_args = array(
		'template_name' => $template_name,
		'template_path' => $template_path,
		'located'       => $template,
		'args'          => $args,
	);

	if ( ! empty( $args ) && is_array( $args ) ) {
		if ( isset( $args['action_args'] ) ) {
			error_log(
				__( 'action_args should not be overwritten when calling wc_get_template.', 'user-post-collections' )
			);
			unset( $args['action_args'] );
		}
		extract( $args ); // @codingStandardsIgnoreLine
	}

	do_action(
		'mg_upc_before_template_part',
		$action_args['template_name'],
		$action_args['template_path'],
		$action_args['located'],
		$action_args['args']
	);

	/** @noinspection PhpIncludeInspection */
	include $action_args['located'];

	do_action(
		'mg_upc_after_template_part',
		$action_args['template_name'],
		$action_args['template_path'],
		$action_args['located'],
		$action_args['args']
	);
}


/**
 * Locate a template and return the path for inclusion.
 *
 * This is the load order:
 *
 * yourtheme/$template_path/$template_name
 * yourtheme/$template_name
 * $default_path/$template_name
 *
 * @param string $template_name Template name.
 * @param string $template_path Template path. (default: '').
 * @param string $default_path  Default path. (default: '').
 * @return string
 */
function mg_upc_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	if ( ! $template_path ) {
		$template_path = 'mg-upc';
	}

	if ( ! $default_path ) {
		$default_path = mg_upc_get_templates_path();
	}

	if ( empty( $template ) ) {
		$template = locate_template(
			array(
				trailingslashit( $template_path ) . $template_name,
				$template_name,
			)
		);
	}

	// Get default template/.
	if ( ! $template ) {
		if ( empty( $cs_template ) ) {
			$template = $default_path . $template_name;
		} else {
			$template = $default_path . $cs_template;
		}
	}

	// Return what we found.
	return apply_filters( 'mg_upc_locate_template', $template, $template_name, $template_path );
}




/**
 * Display the classes for the product div.
 *
 * @param string|array   $class      One or more classes to add to the class list.
 * @param array          $list       list.
 */
function mg_upc_class( $class = '', $list = null ) {
	$list_class = array( $class );
	if ( is_array( $list ) && isset( $list['type'] ) && 'vote' === $list['type'] ) {
		$list_class[] = 'mg-upc-vote';
	}
	echo 'class="' . esc_attr( implode( ' ', $list_class ) ) . '"';
}
