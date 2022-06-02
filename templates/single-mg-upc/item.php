<?php

global $mg_upc_item, $mg_upc_list;

?>
<article class="mg-upc-item tp-<?php echo esc_attr( $mg_upc_item['post_type'] ); ?>" data-pid="<?php echo esc_attr( $mg_upc_item['post_id'] ); ?>">
	<?php if ( 'numbered' === $mg_upc_list['type'] ) : ?>
		<div class="mg-upc-item-number">
			<span><?php echo esc_html( $mg_upc_item['position'] ); ?></span>
		</div>
	<?php endif; ?>
	<div class="mg-upc-item-img">
		<a href="<?php echo esc_url( $mg_upc_item['link'] ); ?>"><figure>
			<?php if ( ! empty( $mg_upc_item['image'] ) ) { ?>
				<img src="<?php echo esc_url( $mg_upc_item['image'] ); ?>" alt="Poster <?php echo esc_attr( $mg_upc_item['title'] ); ?>">
			<?php } else { ?>
				<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVQYV2NgYAAAAAMAAWgmWQ0AAAAASUVORK5CYII=" alt="Without Image">
			<?php } ?>
		</figure></a>
	</div>
	<aside class="mg-upc-item-data">
		<header>
			<h2>
				<a href="<?php echo esc_url( $mg_upc_item['link'] ); ?>"><?php echo esc_html( $mg_upc_item['title'] ); ?></a>
			</h2>
		</header>
		<p class="mg-upc-item-desc"><?php echo esc_html( $mg_upc_item['description'] ); ?></p>
		<?php
		if ( 'vote' === $mg_upc_list['type'] ) {
			$show_on_vote = true;

			$class = '';
			if ( true !== $show_on_vote ) {
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
					<div class='item-bar'>
						<div class='item-bar-fill'></div>
						<div class='item-bar-progress' style='<?php echo esc_attr( 'width: ' . $percent . '%' ); ?>'></div>
					</div>
					<strong class='item-percent'>
						<?php
							echo round( $percent, 1 ) . '%'; // phpcs:ignore
						?>
					</strong>
					<span class='item-votes'>
						<span class='item-votes-number'><?php echo esc_html( $votes ); ?></span> votes
					</span>
				</div>
			</div>
			<?php
		}
		?>
	</aside>
<?php if ( 'vote' === $mg_upc_list['type'] ) : ?>
	<div class="mg-upc-item-actions">
		<button class="mg-upc-item-vote mg-upc-hide" data-vote="<?php echo esc_attr( $mg_upc_list['ID'] . ',' . $mg_upc_item['post_id'] ); ?>">
			Vote
		</button>
	</div>
<?php endif; ?>
</article>
<?php
