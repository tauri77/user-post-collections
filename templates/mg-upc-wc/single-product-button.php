<?php
global $post;
?>
<button class="mg-upc-add-product-to-list" onclick="window.addProductToList" data-id="<?php echo (int) $post->ID; ?>">
	<?php esc_html_e( 'Add to list...', 'user-post-collections' ); ?>
</button>
