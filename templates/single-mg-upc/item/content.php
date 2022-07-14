<?php
/**
 * Single Collection Item
 *
 * This template can be overridden by copying it to yourtheme/mg-upc/single-mg-upc/item/content.php.
 *
 */

global $mg_upc_item, $mg_upc_list;

?>
<article class="mg-upc-item tp-<?php echo esc_attr( $mg_upc_item['post_type'] ); ?>" data-pid="<?php echo esc_attr( $mg_upc_item['post_id'] ); ?>">
	<?php
	/**
	 * Hook: mg_upc_single_list_item_before_first_child.
	 *
	 * @hooked mg_upc_single_list_item_numbered_position - 10
	 */
	do_action( 'mg_upc_single_list_item_before_first_child' );
	?>
	<div class="mg-upc-item-img">
		<?php mg_upc_get_template( 'single-mg-upc/item/image.php' ); ?>
	</div>
	<aside class="mg-upc-item-data">
		<?php do_action( 'mg_upc_single_list_item_before_title' ); ?>
		<header>
			<h2>
				<a href="<?php echo esc_url( $mg_upc_item['link'] ); ?>"><?php echo esc_html( $mg_upc_item['title'] ); ?></a>
			</h2>
		</header>
		<?php
		/**
		 * Hook: mg_upc_single_list_item_after_title.
		 *
		 * @hooked MG_UPC_Woocommerce::show_price - 10
		 * @hooked MG_UPC_Woocommerce::show_stock - 15
		 */
		do_action( 'mg_upc_single_list_item_after_title' );
		?>
		<p class="mg-upc-item-desc"><?php echo esc_html( $mg_upc_item['description'] ); ?></p>
		<?php
		/**
		 * Hook: mg_upc_single_list_item_after_description.
		 *
		 * @hooked mg_upc_single_list_item_vote_data - 10
		 */
		do_action( 'mg_upc_single_list_item_after_description' );
		?>
	</aside>
	<?php
	/**
	 * Hook: mg_upc_single_list_item_after_data.
	 *
	 * @hooked mg_upc_show_item_quantity - 10
	 */
	do_action( 'mg_upc_single_list_item_after_data' );
	?>
	<div class="mg-upc-item-actions">
		<?php
		/**
		 * Hook: mg_upc_single_list_item_action.
		 *
		 * @hooked mg_upc_single_item_vote_button - 5
		 * @hooked MG_UPC_Woocommerce::item_cart_button - 10
		 */
		do_action( 'mg_upc_single_list_item_action' );
		?>
	</div>
	<?php
	/**
	 * Hook: mg_upc_single_list_item_after_last_child.
	 */
	do_action( 'mg_upc_single_list_item_after_last_child' );
	?>
</article>
