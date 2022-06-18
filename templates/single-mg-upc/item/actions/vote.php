<?php
/**
 * Single Collection Vote Button
 *
 * This template can be overridden by copying it to yourtheme/mg-upc/single-mg-upc/item/actions/vote.php.
 *
 */

global $mg_upc_list;
global $mg_upc_item;

?>
<a href="#" class="<?php echo esc_attr( mg_upc_btn_classes( 'mg-upc-item-vote mg-upc-hide' ) ); ?>" data-vote="<?php echo esc_attr( $mg_upc_list['ID'] . ',' . $mg_upc_item['post_id'] ); ?>">
	<?php echo esc_html( mg_upc_get_text( 'Vote' ) ); ?>
</a>
