<?php
/**
 * Loop Item Collection author
 *
 * This template can be overridden by copying it to yourtheme/mg-upc/loop/list/author.php.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
global $mg_upc_list;

?>
<div class="mg-upc-loop-author-list">
	<img
				class="mg-upc-author-avatar"
				src="<?php echo esc_url( $mg_upc_list['user_img'] ); ?>"
				alt="<?php echo esc_attr( $mg_upc_list['user_login'] ); ?> Avatar"
	/><span>
		<?php
		if ( ! empty( $mg_upc_list['user_link'] ) ) {
			echo '<a href="' . esc_url( $mg_upc_list['user_link'] ) . " rel='nofollow'>" . esc_html( $mg_upc_list['user_login'] ) . '</a>';
		} else {
			echo esc_html( $mg_upc_list['user_login'] );
		}
		?>
	</span>
</div>
