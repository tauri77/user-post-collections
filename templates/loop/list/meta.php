<?php
/**
 * Loop Item Collection meta
 *
 * This template can be overridden by copying it to yourtheme/mg-upc/loop/list/meta.php.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
global $mg_upc_list;

?>
<span class="mg-upc-loop-list-meta mg-upc-loop-list-meta-total">
	<span class="mg-upc-loop-list-total"><?php echo (int) $mg_upc_list['count']; ?></span> <span class="mg-upc-loop-list-total-suffix"><?php esc_html_e( 'items', 'user-post-collections' ); ?></span>
</span>
<?php if ( 'vote' === $mg_upc_list['type'] ) { ?>
<span class="mg-upc-loop-list-meta g-upc-loop-list-meta-votes">
	<span class="mg-upc-loop-list-votes"><?php echo (int) $mg_upc_list['vote_counter']; ?></span> <span class="mg-upc-loop-list-votes-suffix"><?php esc_html_e( 'votes', 'user-post-collections' ); ?></span>
</span>
	<?php
}
