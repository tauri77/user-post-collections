<?php
/**
 * Single Collection Items
 *
 * This template can be overridden by copying it to yourtheme/mg-upc/single-mg-upc/items.php.
 *
 */

global $mg_upc_list;

if ( ! empty( $mg_upc_list['items'] ) ) {

	/**
	 * Hook: mg_upc_before_item_loop.
	 *
	 * @hooked mg_upc_items_count - 20
	 */
	do_action( 'mg_upc_before_item_loop' );

	?>
	<div class="mg-upc-items-container" 
	<?php
	if ( 'vote' === $mg_upc_list['type'] ) {
		echo 'data-votes="' . esc_attr( $mg_upc_list['vote_counter'] ) . '"';
	}
	?>
	>
		<?php
		foreach ( $mg_upc_list['items'] as $mg_upc_item ) {

			$GLOBALS['mg_upc_item'] = $mg_upc_item;

			/**
			 * Hook: mg_upc_items_loop.
			 */
			do_action( 'mg_upc_items_loop' );

			mg_upc_get_template_part( 'single-mg-upc/item/content', $mg_upc_list['type'] );
		}
		?>
	</div>
	<?php
	/**
	 * Hook: mg_upc_after_items_loop.
	 *
	 * @hooked mg_upc_pagination - 10
	 */
	do_action( 'mg_upc_after_items_loop' );
} else {
	/**
	 * Hook: mg_upc_no_items_found.
	 *
	 * @hooked mg_upc_no_items_found - 10
	 */
	do_action( 'mg_upc_no_items_found' );
}
