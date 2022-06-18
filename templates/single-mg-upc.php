<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}



get_header( 'mg_upc' ); ?>
	<?php
		/**
		 * mg_upc_before_main_content hook.
		 *
		 * @hooked mg_upc_output_content_wrapper - 10 (outputs opening divs for the content)
		 */
		do_action( 'mg_upc_before_main_content' );
	?>
		<?php while ( have_posts() ) : ?>
			<?php the_post(); ?>

			<?php mg_upc_get_template_part( 'content', 'single-mg-upc' ); ?>

		<?php endwhile; // end of the loop. ?>

	<?php
		/**
		 * mg_upc_after_main_content hook.
		 *
		 * @hooked mg_upc_output_content_wrapper_end - 10 (outputs closing divs for the content)
		 */
		do_action( 'mg_upc_after_main_content' );
	?>

<?php
get_footer( 'mg_upc' );

/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */
