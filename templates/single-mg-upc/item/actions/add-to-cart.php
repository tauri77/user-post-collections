<?php
/**
 * Single Collection Product Item
 *
 * This template can be overridden by copying it to yourtheme/mg-upc/single-mg-upc/actions/add-to-cart.php.
 *
 */

global $mg_upc_item;

$quantity = 1;
if ( mg_upc_list_check_support( (int) $mg_upc_item['list_id'], 'quantity' ) ) {
	$quantity = $mg_upc_item['quantity'];
}
?>
<a href="#" class="<?php echo esc_attr( mg_upc_btn_classes( 'mg-upc-item-product mg-upc-hide' ) ); ?>"
			data-product="<?php echo esc_attr( $mg_upc_item['post_id'] ); ?>"
			data-quantity="<?php echo esc_attr( $quantity ); ?>">
	<?php echo esc_html( mg_upc_get_text( 'Add to cart' ) ); ?>
</a>
