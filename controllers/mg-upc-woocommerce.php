<?php


class MG_UPC_Woocommerce extends MG_UPC_Module {

	public function __construct() { }

	public function init() {

		if ( class_exists( 'WooCommerce' ) ) {
			//Add 'product' to allowed post types
			add_filter(
				'register_list_type_args',
				function ( $list_type_arg ) {
					if ( ! isset( $list_type_arg['available_post_types'] ) ) {
						$list_type_arg['available_post_types'] = array( 'post' );
					}
					$list_type_arg['available_post_types'][] = 'product';
					$list_type_arg['available_post_types'][] = 'product_variation';

					return $list_type_arg;
				}
			);
			add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'product_button' ) );
		}

		add_filter( 'mg_post_item_product_variation_for_response', array( $this, 'product_variant_item' ) );
	}

	public function product_variant_item( $item ) {
		if ( empty( $item['image'] ) && class_exists( 'WC_Product' ) ) {
			$variant = get_post( $item['post_id'] );
			if ( $variant->post_parent ) {
				$product_id = $variant->post_parent;
				$product    = new WC_Product( $product_id );
				if ( $product->get_image_id() ) {
					$item['featured_media'] = $product->get_image_id();
					$item['image']          = wp_get_attachment_image_url( $product->get_image_id() ); // or add size , 'medium'
				}
			}
		}
		return $item;
	}

	public function product_button() {
		global $post;
		if ( ! empty( $post ) && $post->ID > 0 ) {
			if ( ! empty( MG_UPC_Helper::get_instance()->get_available_list_types( $post->post_type ) ) ) {
				remove_filter( 'the_content', array( MG_UPC_Buttons::get_instance(), 'the_content' ) );
				echo '<div class="post-adding">';
				echo '<button class="mg-upc-add-product-to-list" ' .
						'onclick="window.addProductToList" data-id="' . (int) $post->ID . '">Add to list...</button>';
				echo '</div>';
			}
		}
	}

	public function activate( $network_wide ) { }

	public function deactivate() { }

	public function register_hook_callbacks() { }

	public function upgrade( $db_version = 0 ) { }
}
