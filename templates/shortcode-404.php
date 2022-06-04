<?php
?>
<div id="mg-upc-err-404">
<?php
/**
 * Hook: mg_upc_before_shortcode_404.
 */
do_action( 'mg_upc_before_shortcode_404' );
?>
<h2>
	<?php echo esc_html__( 'List not found.', 'user-post-collection' ); ?>
</h2>
<?php
/**
 * Hook: mg_upc_after_single_list_summary.
 */
do_action( 'mg_upc_after_single_list_summary' );
?>
</div>
