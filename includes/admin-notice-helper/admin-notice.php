<div class="anh_message <?php
// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
esc_attr_e( $class, 'user-post-collections' );
?>">
	<?php foreach ( $this->notices[ $type ] as $notice ) : ?>
		<p><?php echo wp_kses( $notice, wp_kses_allowed_html( 'post' ) ); ?></p>
	<?php endforeach; ?>
</div>
