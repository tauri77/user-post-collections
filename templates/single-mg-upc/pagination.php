<?php
/**
 * Single Collection Items Pagination
 *
 * This template can be overridden by copying it to yourtheme/mg-upc/single-mg-upc/pagination.php.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$total   = isset( $total ) ? $total : 1;
$current = isset( $current ) ? $current : 1;
$base    = isset( $base ) ? $base : esc_url_raw(
	str_replace(
		999999999,
		'%#%',
		get_pagenum_link(
			999999999,
			false
		)
	)
);
$format  = isset( $format ) ? $format : '';

if ( $total <= 1 ) {
	return;
}

?>
<nav class="mg-upc-items-pagination">
	<?php
	// phpcs:ignore
	echo paginate_links(
		apply_filters(
			'mg_upc_items_pagination_args',
			array(
				'base'      => $base,
				'format'    => $format,
				'add_args'  => false,
				'current'   => max( 1, $current ),
				'total'     => $total,
				'prev_text' => is_rtl() ? '&rarr;' : '&larr;',
				'next_text' => is_rtl() ? '&larr;' : '&rarr;',
				'type'      => 'list',
				'end_size'  => 3,
				'mid_size'  => 3,
			)
		)
	);
	?>
</nav>
