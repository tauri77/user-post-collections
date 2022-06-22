<?php
/**
 * Single Collection description
 *
 * This template can be overridden by copying it to yourtheme/mg-upc/single-mg-upc/description.php.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
global $mg_upc_list;

$content = $mg_upc_list['content'];
if ( strpos( $content, '<' ) !== false ) {
	$content = force_balance_tags( $content );
}
?>
<div class="mg-upc-description">
	<p>
		<?php
			echo wp_kses( nl2br( $content ), MG_UPC_List_Controller::get_instance()->list_allowed_tags() );
		?>
	</p>
</div>
