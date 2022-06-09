<?php
/**
 * Single Collection Item Position Number
 *
 * This template can be overridden by copying it to yourtheme/mg-upc/single-mg-upc/item/position-number.php.
 *
 */

global $mg_upc_item;

?>
<div class="mg-upc-item-number">
	<span><?php echo esc_html( $mg_upc_item['position'] ); ?></span>
</div>
