<?php


class MG_UPC_Buttons extends MG_UPC_Module {

	public function __construct() {
	}

	public function init() {
		add_filter( 'the_content', array( 'MG_UPC_Buttons', 'the_content' ) );
	}

	public static function the_content( $content ) {
		global $post;
		if ( ! empty( $post ) && $post->ID > 0 ) {
			if ( MG_UPC_Helper::get_instance()->current_user_can_add_to_any( $post->post_type ) ) {
				$position = get_option( 'mg_upc_button_position', 'end' );

				$btn  = '<div class="post-adding">';
				$btn .= '<button onclick="window.addItemToList(' . (int) $post->ID . ')">Add to list...</button>';
				$btn .= '</div>';

				if ( 'end' === $position ) {
					$content .= $btn;
				} elseif ( 'begin' === $position ) {
					$content = $btn . $content;
				}
			}
		}
		return $content;
	}

	public function activate( $network_wide ) { }

	public function deactivate() { }

	public function register_hook_callbacks() { }

	public function upgrade( $db_version = 0 ) { }
}
