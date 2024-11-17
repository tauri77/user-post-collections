<?php
/**
 * The template for displaying collection content in the single-collection.php template
 *
 * This template can be overridden by copying it to yourtheme/mg-upc/content-single-product.php.
 *
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit;
/**
 * @global MG_UPC_List  $mg_upc_list
 * @global MG_UPC_Query $mg_upc_query
 */
global $mg_upc_query, $mg_upc_list;

if ( ! $mg_upc_query ) {
	return;
}

/**
 * Hook: mg_upc_before_archive.
 */
do_action( 'mg_upc_before_archive' );

if ( post_password_required() ) {
	echo get_the_password_form(); // phpcs:ignore XSS ok.
	return;
}

$default_class =  'page-inner mg-upc-page-inner';

$mg_classes = isset($mg_classes) ? $mg_classes : $default_class;
?>
<div id="mg-upc-archive" <?php mg_upc_class( $mg_classes ); ?>>

	<?php
	/**
	 * Hook: mg_upc_before_archive_content.
	 */
	do_action( 'mg_upc_before_archive_content' );
	?>

	<div class="mg-upc-archive">
		<?php
		while ( $mg_upc_query->have_lists() ) {
			$mg_upc_query->the_list();
			?><div <?php mg_upc_class( 'mg-upc-archive-list', $mg_upc_list ); ?>><?php
			/**
			 * Hook: mg_upc_loop_single_list_content.
			 *
			 * @hooked mg_upc_template_loop_single_thumbs - 10
			 * @hooked mg_upc_template_loop_single_title - 20
			 * @hooked mg_upc_template_loop_single_author - 30
			 * @hooked mg_upc_template_loop_single_meta - 40
			 * @hooked mg_upc_template_loop_single_description - 50
			 */
			do_action( 'mg_upc_loop_single_list_content' );
			?></div><?php
		}
		if ( 0 === $mg_upc_query->list_count ) {
			/**
			 * Hook: mg_upc_loop_empty.
			 *
			 * @hooked mg_upc_loop_empty - 10
			 */
			do_action( 'mg_upc_loop_empty' );
		}
		?>
	</div>

	<?php
	/**
	 * Hook: mg_upc_after_archive_content.
	 *
	 * @hooked mg_upc_template_archive_pagination - 10
	 */
	do_action( 'mg_upc_after_archive_content' );
	?>
</div>
