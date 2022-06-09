<?php
/**
 * Single Collection Product Item
 *
 * This template can be overridden by copying it to yourtheme/mg-upc/single-mg-upc/actions/add-to-cart.php.
 *
 */

global $mg_upc_item;

?>
<button class="mg-upc-item-product mg-upc-hide" data-product="<?php echo esc_attr( $mg_upc_item['post_id'] ); ?>">
	<?php echo esc_html__( 'Add to cart', 'user-post-collections' ); ?>
</button>
