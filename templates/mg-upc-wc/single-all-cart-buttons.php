<?php
global $mg_upc_list;
?>
<a class="<?php echo esc_attr( mg_upc_btn_classes( 'mg-upc-add-list-to-cart' ) ); ?>"
	data-id="<?php echo (int) $mg_upc_list['ID']; ?>">
	<?php echo esc_html( mg_upc_get_text( 'Add all to cart', 'mg_upc_list' ) ); ?>
</a>
