<?php
/**
 * Single Collection Empty Loop Content
 *
 * This template can be overridden by copying it to yourtheme/mg-upc/loop/empty.php.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="mg-upc-loop-empty">
	<?php
	do_action( 'mg_upc_before_loop_empty' );
	?>
	<h2>
		<?php echo esc_html__( 'There are no collections to show.', 'user-post-collections' ); ?>
	</h2>
	<?php
	do_action( 'mg_upc_after_loop_empty' );
	?>
</div>
