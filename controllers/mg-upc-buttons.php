<?php


class MG_UPC_Buttons extends MG_UPC_Module {

	public function __construct() {
	}

	public function init() {
		add_filter(
			'the_content',
			array( $this, 'the_content' )
		);
	}

	public function the_content( $content ) {
		global $post;
		if ( ! empty( $post ) && $post->ID > 0 ) {
			if ( ! empty( MG_UPC_Helper::get_instance()->get_available_list_types( $post->post_type ) ) ) {
				$content .= '<div class="post-adding">';
				$content .= '<button onclick="window.addItemToList(' . (int) $post->ID . ')">Add to list...</button>';
				$content .= '</div>';
			}
		}
		return $content;
	}

	public function activate( $network_wide ) { }

	public function deactivate() { }

	public function register_hook_callbacks() { }

	public function upgrade( $db_version = 0 ) { }
}
