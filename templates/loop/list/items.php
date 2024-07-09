<?php
/**
 * Loop Item Collection Items
 *
 * This template can be overridden by copying it to yourtheme/mg-upc/loop/list/items.php.
 *
 */

global $mg_upc_list;
if ( ! empty( $mg_upc_list['items'] ) ) {
	?><a href="<?php mg_upc_the_permalink(); ?>" class="mg-upc-thumbs-container">
	<?php
	foreach ( $mg_upc_list['items'] as $mg_upc_item ) {
		$GLOBALS['mg_upc_item'] = $mg_upc_item;
		?>
			<figure>
			<?php if ( ! empty( $mg_upc_item['image'] ) ) { ?>
					<img src="<?php echo esc_url( $mg_upc_item['image'] ); ?>" alt="<?php echo esc_attr( $mg_upc_item['title'] ); ?>">
				<?php } else { ?>
					<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVQYV2NgYAAAAAMAAWgmWQ0AAAAASUVORK5CYII=" alt="<?php esc_attr_e( 'Without Image', 'user-post-collections' ); ?>">
				<?php } ?>
			</figure>
		<?php } ?>
	</a><?php
} else {
	echo '<div class="mg-upc-thumbs-container mp-upc-empty"></div>';
}
