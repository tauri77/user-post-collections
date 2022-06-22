<?php
/**
 * Content wrappers
 *
 * This template can be overridden by copying it to yourtheme/mg-upc/global/wrapper-end.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$template = mg_upc_get_theme_slug_for_templates();

switch ( $template ) {
	case 'twentyten':
		echo '</div></div>';
		break;
	case 'twentyeleven':
		echo '</div>';
		get_sidebar( 'shop' );
		echo '</div>';
		break;
	case 'twentytwelve':
		echo '</div></div>';
		break;
	case 'twentythirteen':
		echo '</div></div>';
		break;
	case 'twentyfourteen':
		echo '</div></div></div>';
		get_sidebar( 'content' );
		break;
	case 'twentyfifteen':
		echo '</div></div>';
		break;
	case 'twentysixteen':
		echo '</main></div>';
		break;
	case 'twentyseventeen':
		echo '</main></div></div>';
		break;
	case 'twentytwenty':
		echo '</div></main></div>';
		break;
	case 'twentytwentyone':
		echo '</div>';
		break;
	case 'twentytwentytwo':
		echo '</main></div>';
		break;
	case 'storefront':
		echo '</main></div></div>';
		break;
	default:
		echo '</main></div>';
		break;
}
