<?php
global $post;
?>
<a class="<?php echo esc_attr( mg_upc_btn_classes( 'mg-upc-add-product-to-list' ) ); ?>" data-id="<?php echo (int) $post->ID; ?>">
	<?php echo esc_html( mg_upc_get_text( 'Add to list...', 'product_loop' ) ); ?>
</a>
