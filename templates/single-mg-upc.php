<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}



get_header( 'mg_upc' ); ?>
	<?php
		do_action( 'mg_upc_before_main_content' );
	?>
		<?php while ( have_posts() ) : ?>
			<?php the_post(); ?>

			<?php mg_upc_get_template_part( 'content', 'single-mg-upc' ); ?>

		<?php endwhile; // end of the loop. ?>

	<?php
		do_action( 'mg_upc_after_main_content' );
	?>

<?php
get_footer( 'mg_upc' );

/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */
