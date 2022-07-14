<?php
global $mg_upc_list;

$enabled = get_option( 'mg_upc_share_buttons', array( 'twitter', 'facebook', 'whatsapp', 'telegram', 'line', 'email' ) );
if ( empty( $enabled ) ) {
	return;
}

$list_link  = rawurlencode( $mg_upc_list['link'] );
$list_title = rawurlencode( $mg_upc_list['title'] );

$buttons = array(
	array(
		'name' => 'Twitter',
		'url'  => 'https://twitter.com/share?url=' . $list_link . '&text=' . $list_title,
	),
	array(
		'name' => 'Facebook',
		'url'  => 'https://www.facebook.com/sharer/sharer.php?u=' . $list_link . '&quote=' . $list_title,
	),
	array(
		'name' => 'Pinterest',
		'url'  => 'https://pinterest.com/pin/create/button/?url=' . $list_link . '&description=' . $list_title,
	),
	array(
		'name' => 'Whatsapp',
		'url'  => 'whatsapp://send?text=' . $list_link,
	),
	array(
		'name' => 'Telegram',
		'url'  => 'https://t.me/share/url?url=' . $list_link . '&text=' . $list_title,
	),
	array(
		'name' => 'LiNE',
		'url'  => 'https://social-plugins.line.me/lineit/share?url=' . $list_link . '&text=' . $list_title,
	),
	array(
		'slug' => 'email',
		'name' => mg_upc_get_text( 'Email' ),
		'url'  => 'mailto:?subject=' . $list_title . '&body=' . $list_link,
	),
);
foreach ( $buttons as $i => $button ) {
	if ( ! isset( $button['slug'] ) ) {
		$buttons[$i]['slug'] = strtolower( $button['name'] );
	}
}

foreach ( $buttons as $i => $button ) {
	if ( ! in_array( $button['slug'], $enabled, true ) ) {
		unset( $buttons[ $i ] );
	}
}
$protocols = array( 'https', 'mailto', 'whatsapp' );

echo '<div class="mg-upc-share-link">';
foreach ( $buttons as $button ) {
	?><a href="<?php echo esc_url( $button['url'], $protocols ); ?>"
		 title="Share with <?php echo esc_attr( $button['name'] ); ?>"
		 class="mg-upc-share" target='_blank' rel='noopener'
	><div class="mg-upc-share-btn-img mg-upc-share-<?php echo esc_attr( $button['slug'] ); ?>"> </div></a><?php
}
echo '</div>';
