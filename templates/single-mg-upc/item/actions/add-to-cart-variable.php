<?php
/**
 * Single Collection Product Variable Item
 *
 * This template can be overridden by copying it to yourtheme/mg-upc/single-mg-upc/actions/add-to-cart-variable.php.
 *
 */

global $mg_upc_item;

?>
<a class="<?php echo esc_attr( mg_upc_btn_classes( 'mg-upc-item-product-variable' ) ); ?>"
	href="<?php echo esc_url( $mg_upc_item['link'] ); ?>"
	data-product="<?php echo esc_attr( $mg_upc_item['post_id'] ); ?>">
	<?php echo esc_html( mg_upc_get_text( 'Add to cart...' ) ); ?>
</a>
