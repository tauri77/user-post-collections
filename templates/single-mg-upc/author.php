<?php
/**
 * Single Collection title
 *
 * This template can be overridden by copying it to yourtheme/mg-upc/single-mg-upc/title.php.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
global $mg_upc_list;

?>
<div class="mg-upc-author-box">
	<img
				class="mg-upc-author-avatar"
				src="<?php echo esc_url( $mg_upc_list['user_img'] ); ?>"
				alt="<?php echo esc_attr( $mg_upc_list['user_login'] ); ?> Avatar"
		>
	<h4>Created by
		<?php if ( ! empty( $mg_upc_list['user_link'] ) ) { ?>
			<a href="<?php echo esc_url( $mg_upc_list['user_link'] ); ?>" rel='nofollow'><?php echo esc_html( $mg_upc_list['user_login'] ); ?></a>
		<?php } else { ?>
			<?php echo esc_html( $mg_upc_list['user_login'] ); ?>
		<?php } ?>
	</h4>
	<span class="mg-upc-author-data"><?php echo esc_html( date_i18n( get_option( 'date_format' ), $mg_upc_list['modified'] ) ); ?></span>
</div>
