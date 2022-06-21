<?php
/**
 * Single Collection Item Vote Data
 *
 * This template can be overridden by copying it to yourtheme/mg-upc/single-mg-upc/item/vote-data.php.
 *
 */

global $mg_upc_item, $mg_upc_list;

$show_on_vote = MG_UPC_Helper::get_instance()->get_list_type_option( $mg_upc_list['type'], 'show_on_vote', 'off' );

$class = '';
if ( 'on' !== $show_on_vote ) {
	$votes   = $mg_upc_item['votes'];
	$total   = $mg_upc_list['vote_counter'];
	$percent = $total > 0 ? $votes * 100 / $total : 0;
} else {
	$votes   = 0;
	$total   = 0;
	$percent = 0;
	$class   = 'mg-upc-hide';
}
?>
<div class="mg-upc-votes <?php echo esc_attr( $class ); ?>" data-votes="<?php echo esc_attr( $votes ); ?>">
	<div>
		<div class='mg-upc-item-bar'>
			<div class='mg-upc-item-bar-fill'></div>
			<div class='mg-upc-item-bar-progress' style='<?php echo esc_attr( 'width: ' . $percent . '%' ); ?>'></div>
		</div>
		<strong class='mg-upc-item-percent'>
			<?php
			echo round( $percent, 1 ) . '%'; // phpcs:ignore
			?>
		</strong>
		<span class='mg-upc-item-votes'>
			<?php
			echo sprintf(
				esc_html( mg_upc_get_text( '%s votes' ) ),
				'<span class="mg-upc-item-votes-number">' . esc_html( $votes ) . '</span>'
			);
			?>
		</span>
	</div>
</div>
