<?php


class MG_UPC_Buttons extends MG_UPC_Module {

	public function __construct() {
	}

	public function init() {
		add_filter(
			'the_content',
			array( $this, 'the_content' )
		);

		if ( class_exists( 'WooCommerce' ) ) {
			//Add 'product' to allowed post types
			add_filter(
				'register_list_type_args',
				function ( $list_type_arg ) {
					if ( ! isset( $list_type_arg['available_post_types'] ) ) {
						$list_type_arg['available_post_types'] = array( 'post' );
					}
					$list_type_arg['available_post_types'][] = 'product';

					return $list_type_arg;
				}
			);
			add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'product_button' ) );
		}
	}

	public function product_button() {
		global $post;
		if ( ! empty( $post ) && $post->ID > 0 ) {
			if ( ! empty( MG_UPC_Helper::get_instance()->get_available_list_types( $post->post_type ) ) ) {
				remove_filter( 'the_content', array( $this, 'the_content' ) );
				echo '<div class="post-adding">';
				echo '<button onclick="window.addItemToList(' . (int) $post->ID . ')">Add to list...</button>';
				echo '</div>';
			}
		}
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
