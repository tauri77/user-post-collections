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
<button class="mg-upc-item-vote mg-upc-hide" data-vote="<?php echo esc_attr( $mg_upc_list['ID'] . ',' . $mg_upc_item['post_id'] ); ?>">
	<?php esc_html_e( 'Vote', 'user-post-collections' ); ?>
</button>
