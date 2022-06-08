<?php
/**
 * Single Collection Product Variable Item
 *
 * This template can be overridden by copying it to yourtheme/mg-upc/single-mg-upc/actions/add-to-cart-variable.php.
 *
 */

global $mg_upc_item;

?>
<a class="mg-upc-item-product-variable button" href="<?php echo esc_url( $mg_upc_item['link'] ); ?>" data-product="<?php echo esc_attr( $mg_upc_item['post_id'] ); ?>">
	<?php echo esc_html__( 'Add to cart...', 'user-post-collections' ); ?>
</a>
