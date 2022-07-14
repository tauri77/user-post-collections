<?php
/**
 * The template for displaying collection content in the single-collection.php template
 *
 * This template can be overridden by copying it to yourtheme/mg-upc/content-single-product.php.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

global $mg_upc_list;

if ( ! $mg_upc_list ) {
	return;
}

/**
 * Hook: mg_upc_before_single_list.
 */
do_action( 'mg_upc_before_single_list' );

if ( post_password_required() ) {
	echo get_the_password_form(); // phpcs:ignore XSS ok.
	return;
}
?>
<div id="mg-upc-<?php echo esc_attr( $mg_upc_list['ID'] ); ?>"
	data-id="<?php echo esc_attr( $mg_upc_list['ID'] ); ?>"
	<?php mg_upc_class( 'page-inner mg-upc-page-inner', $mg_upc_list ); ?>>

	<?php
	/**
	 * Hook: mg_upc_before_single_list_content.
	 */
	do_action( 'mg_upc_before_single_list_content' );
	?>

	<div class="mg-upc-list entry-mg-upc-list">
		<?php
		/**
		 * Hook: mg_upc_single_list_summary.
		 *
		 * @hooked mg_upc_template_single_title - 5
		 * @hooked mg_upc_template_single_author - 10
		 * @hooked mg_upc_template_single_sharing - 15
		 * @hooked mg_upc_template_single_description - 20
		 * @hooked MG_UPC_Woocommerce::item_cart_all_button - 25
		 * @hooked mg_upc_template_single_items - 30
		 */
		do_action( 'mg_upc_single_list_content' );
		?>
	</div>

	<?php
	/**
	 * Hook: mg_upc_after_single_list_summary.
	 *
	 * @hooked mg_upc_template_items_pagination - 10
	 */
	do_action( 'mg_upc_after_single_list_content' );
	?>
</div>

<?php
do_action( 'mg_upc_after_single_list' );
