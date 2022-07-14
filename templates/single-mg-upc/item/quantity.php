<?php
global $mg_upc_item;

?>
<div class='mg-upc-item-quantity'>
	<small><?php echo esc_html( mg_upc_get_text( 'Quantity', 'mg_upc_list' ) ); ?></small>
	<b><?php echo esc_html( $mg_upc_item['quantity'] ); ?></b>
</div>