<?php
/**
 * Single Collection Item Image
 *
 * This template can be overridden by copying it to yourtheme/mg-upc/single-mg-upc/item/image.php.
 *
 */


global $mg_upc_item, $mg_upc_list;

?>
<a href="<?php echo esc_url( $mg_upc_item['link'] ); ?>"><figure>
	<?php if ( ! empty( $mg_upc_item['image'] ) ) { ?>
		<img src="<?php echo esc_url( $mg_upc_item['image'] ); ?>" alt="<?php echo esc_attr( $mg_upc_item['title'] ); ?>">
	<?php } else { ?>
		<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVQYV2NgYAAAAAMAAWgmWQ0AAAAASUVORK5CYII=" alt="<?php esc_attr_e( 'Without Image', 'user-post-collections' ); ?>">
	<?php } ?>
</figure></a>
