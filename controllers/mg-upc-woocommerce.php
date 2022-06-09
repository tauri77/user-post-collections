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
		add_filter( 'mg_post_item_product_for_response', array( $this, 'product_item' ) );
	}

	/**
	 * Product variation post type item prepare
	 *
	 * @param array $item
	 *
	 * @return array
	 */
	public function product_variant_item( $item ) {

		if ( ! function_exists( 'wc_get_product' ) ) {
			return $item;
		}

		// If empty image, check if the parent post has image
		if ( empty( $item['image'] ) ) {
			$variant = get_post( $item['post_id'] );
			if ( $variant->post_parent > 0 ) {
				$product_id = $variant->post_parent;
				$product    = wc_get_product( $product_id );

				if ( $product ) {
					if ( $product->get_image_id() ) {
						$item['featured_media'] = $product->get_image_id();
						$item['image']          = wp_get_attachment_image_url( $product->get_image_id() ); // or add size , 'medium'
					}
				}
			}
		}

		$item = $this->add_product_properties( $item, wc_get_product( $item['post_id'] ) );

		return $item;
	}

	/**
	 * Product post type item prepare
	 *
	 * @param array $item
	 *
	 * @return array
	 */
	public function product_item( $item ) {
		if ( function_exists( 'wc_get_product' ) ) {
			$item = $this->add_product_properties( $item, wc_get_product( $item['post_id'] ) );
		}

		return $item;
	}

	/**
	 * Add properties to product item
	 *
	 * @param array $item
	 * @param WC_Product $product
	 *
	 * @return array
	 */
	private function add_product_properties( $item, $product ) {
		if ( $product ) {
			$item['product_type'] = $product->get_type();
			if ( '' === $product->get_price() ) {
				$item['price'] = apply_filters( 'woocommerce_empty_price_html', '', $this );
			} elseif ( $product->is_on_sale() ) {
				$item['regular_price'] = $product->get_regular_price();
				$item['sale_price']    = $product->get_sale_price();
			} else {
				$item['price'] = $product->get_price();
			}
			$item['price_suffix'] = wp_strip_all_tags( $product->get_price_suffix() );
			$item['price_html']   = $product->get_price_html();
			if ( $product->is_type( 'variable' ) ) {
				$item = $this->set_item_price_range( $item, $product );
			}

			$price_keys = array( 'regular_price', 'sale_price', 'price', 'price_min', 'price_max' );
			foreach ( $price_keys as $price_key ) {
				if ( isset( $item[ $price_key ] ) ) {
					$item[ $price_key ] = wc_get_price_to_display( $product, array( 'price' => $item[ $price_key ] ) );
					$item[ $price_key ] = wc_price( $item[ $price_key ] );
					$item[ $price_key ] = html_entity_decode( $item[ $price_key ] );
					$item[ $price_key ] = wp_strip_all_tags( $item[ $price_key ] );
				}
			}
		}

		return $item;
	}

	/**
	 * Set price range for product item
	 *
	 * @param array $item
	 * @param WC_Product $product
	 *
	 * @return array
	 */
	private function set_item_price_range( $item, $product ) {
		$tax_display_mode = get_option( 'woocommerce_tax_display_shop' );
		$child_prices     = array();
		$children         = array_map( 'wc_get_product', $product->get_children() );

		foreach ( $children as $child ) {
			if ( '' !== $child->get_price() ) {
				$child_prices[] = 'incl' === $tax_display_mode ? wc_get_price_including_tax( $child ) : wc_get_price_excluding_tax( $child );
			}
		}

		if ( ! empty( $child_prices ) ) {
			$min_price = min( $child_prices );
			$max_price = max( $child_prices );
			if ( $min_price !== $max_price ) {
				$item['price_min'] = $min_price;
				$item['price_max'] = $max_price;
			}
		}

		return $item;
	}

	/**************************************************
	 *               Template methods
	 ***************************************************/

	/**
	 * Print the button "Add to list..."
	 */
	public function product_button() {
		global $post;
		if ( ! empty( $post ) && $post->ID > 0 ) {
			if ( ! empty( MG_UPC_Helper::get_instance()->get_available_list_types( $post->post_type ) ) ) {
				remove_filter( 'the_content', array( MG_UPC_Buttons::get_instance(), 'the_content' ) );
				mg_upc_get_template( 'mg-upc-wc/single-product-buttons.php' );
			}
		}
	}

	/**
	 * Show price of items on list page
	 */
	public static function show_price() {
		global $mg_upc_item;
		if ( ! function_exists( 'wc_get_product' ) ) {
			return;
		}
		if ( 'product' === $mg_upc_item['post_type'] || 'product_variation' === $mg_upc_item['post_type'] ) {
			$product = wc_get_product( $mg_upc_item['post_id'] );
			if ( $product ) {
				echo '<div class="mg-upc-list-item-price">' . $product->get_price_html() . '</div>'; // phpcs:ignore
			}
		}
	}

	/**
	 * Add button on item actions section (on list page)
	 */
	public static function item_cart_button() {
		global $mg_upc_item;

		if ( isset( $mg_upc_item['product_type'] ) && 'variable' === $mg_upc_item['product_type'] ) {
			mg_upc_get_template( 'single-mg-upc/item/actions/add-to-cart-variable.php' );
		} elseif ( 'product' === $mg_upc_item['post_type'] || 'product_variation' === $mg_upc_item['post_type'] ) {
			mg_upc_get_template( 'single-mg-upc/item/actions/add-to-cart.php' );
		}
	}

	/*public function include_woo_variable_cart() {
		global $mg_upc_item;

		$product = wc_get_product( $mg_upc_item['post_id'] );

		wp_enqueue_script( 'wc-add-to-cart-variation' );

		// Get Available variations?
		$get_variations = count( $product->get_children() ) <= apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product );

		// Load the template.
		wc_get_template(
			'single-product/add-to-cart/variable.php',
			array(
				'available_variations' => $get_variations ? $product->get_available_variations() : false,
				'attributes'           => $product->get_variation_attributes(),
				'selected_attributes'  => $product->get_default_attributes(),
			)
		);
	}*/

	public function activate( $network_wide ) { }

	public function deactivate() { }

	public function register_hook_callbacks() { }

	public function upgrade( $db_version = 0 ) { }
}
