<?php
/**
 * Single Collection Empty Items
 *
 * This template can be overridden by copying it to yourtheme/mg-upc/single-mg-upc/empty-items.php.
 *
 */
?>
<div id="mg-upc-empty-items">
	<?php
	/**
	 * Hook: mg_upc_before_empty_items.
	 */
	do_action( 'mg_upc_before_empty_items' );
	?>
	<h2 class="mg-upc-empty-items-text">
		<?php echo esc_html__( 'There are no items to show', 'user-post-collection' ); ?>
	</h2>
	<?php
	/**
	 * Hook: mg_upc_after_empty_items.
	 */
	do_action( 'mg_upc_after_empty_items' );
	?>
</div>

