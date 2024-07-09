<?php
/**
 * Loop Item List title
 *
 * This template can be overridden by copying it to yourtheme/mg-upc/loop/list/title.php.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?><h2 class="mg-upc-loop-list-title"><a href="<?php mg_upc_the_permalink(); ?>"><?php

mg_upc_the_title();

?></a></h2>