<?php


class MG_UPC_Woocommerce extends MG_UPC_Module {

	public function __construct() {
		//before added list types on init with priority 10.. and WooCommerce already defined
		add_action( 'init', array( $this, 'pre_init' ), 5 );
	}

	/**
	 * Before added list types on init with priority 10.. and WooCommerce already defined
	 */
	public function pre_init() {

		if ( class_exists( 'WooCommerce' ) ) {
			//Add 'product' to allowed post types by default
			add_filter(
				'mg_upc_before_list_type_options_saved_set',
				function ( $list_type_arg ) {
					if ( ! isset( $list_type_arg['available_post_types'] ) ) {
						$list_type_arg['available_post_types'] = array( 'post' );
					}
					$list_type_arg['available_post_types'][] = 'product';

					return $list_type_arg;
				}
			);
			//If product in available post type, then add product_variation
			add_filter(
				'register_list_type_args',
				function ( $list_type_arg ) {
					if (
						isset( $list_type_arg['available_post_types'] ) &&
						in_array( 'product', $list_type_arg['available_post_types'], true )
					) {
						$list_type_arg['available_post_types'][] = 'product_variation';
					}

					return $list_type_arg;
				}
			);

			$btn_position = get_option( 'mg_upc_button_position_product', 'after_cart' );
			if ( 'after_cart' === $btn_position ) {
				add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'product_button' ) );
			} elseif ( 'before_cart' === $btn_position ) {
				add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'product_button' ) );
			} elseif ( 'after_title' === $btn_position ) {
				add_action( 'woocommerce_single_product_summary', array( $this, 'product_button' ), 7 );
			} elseif ( 'after_meta' === $btn_position ) {
				add_action( 'woocommerce_product_meta_end', array( $this, 'product_button' ), 10 );
			}

			$option_loop_button = get_option( 'mg_upc_loop_button_position_product', 'onsale' );
			if ( 'after_cart' === $option_loop_button ) {
				add_action( 'woocommerce_after_shop_loop_item', array( $this, 'product_button_shop_loop_item' ), 13 );
			} elseif ( 'before_cart' === $option_loop_button ) {
				add_action( 'woocommerce_after_shop_loop_item', array( $this, 'product_button_shop_loop_item' ), 8 );
			} elseif ( 'before_item' === $option_loop_button ) {
				add_action( 'woocommerce_before_shop_loop_item', array( $this, 'product_button_shop_loop_item' ), 10 );
			} elseif ( 'before_title' === $option_loop_button ) {
				add_action( 'woocommerce_shop_loop_item_title', array( $this, 'product_button_shop_loop_item' ), 8 );
			}

			add_filter( 'mg_upc_settings_sections', array( $this, 'mg_upc_settings_sections' ) );
			add_filter( 'mg_upc_settings_fields', array( $this, 'mg_upc_settings_fields' ) );
		}

		add_filter( 'mg_post_item_product_variation_for_response', array( $this, 'product_variant_item' ) );
		add_filter( 'mg_post_item_product_for_response', array( $this, 'product_item' ) );
	}

	public function mg_upc_settings_sections( $sections ) {
		$section = array(
			'id'       => 'mg_upc_product',
			'title'    => __( 'Product Settings', 'user-post-collections' ),
			'as_array' => false,
		);
		array_splice( $sections, 1, 0, array( $section ) );

		return $sections;
	}

	public function mg_upc_settings_fields( $settings_fields ) {
		$settings_fields['mg_upc_product'] = array();

		$settings_fields['mg_upc_product'][] = array(
			'name'    => 'mg_upc_button_position_product',
			'label'   => __( 'Product button position', 'user-post-collections' ),
			'desc'    => __( 'Where the "Add to list" button will be inserted on single product', 'user-post-collections' ),
			'default' => 'after_cart',
			'type'    => 'radio',
			'options' => array(
				'not'         => __( 'Not add button', 'user-post-collections' ),
				'after_cart'  => __( 'After add to cart form', 'user-post-collections' ),
				'before_cart' => __( 'Before add to cart form', 'user-post-collections' ),
				'after_title' => __( 'After title', 'user-post-collections' ),
				'after_meta'  => __( 'After product meta', 'user-post-collections' ),
			),
		);
		$settings_fields['mg_upc_product'][] = array(
			'name'    => 'mg_upc_loop_button_position_product',
			'label'   => __( 'Product button loop', 'user-post-collections' ),
			'desc'    => __( 'Where the "Add to list" button will be inserted on loop products', 'user-post-collections' ),
			'default' => 'after_cart',
			'type'    => 'radio',
			'options' => array(
				'not'          => __( 'Not add button', 'user-post-collections' ),
				'after_cart'   => __( 'After add to cart button', 'user-post-collections' ),
				'before_cart'  => __( 'Before add to cart button', 'user-post-collections' ),
				'before_title' => __( 'Before product title', 'user-post-collections' ),
				'before_item'  => __( 'Before product item', 'user-post-collections' ),
			),
		);
		$settings_fields['mg_upc_product'][] = array(
			'name'    => 'mg_upc_page_show_price',
			'label'   => __( 'Price on collection page', 'user-post-collections' ),
			'desc'    => __( 'Show prices on collection page', 'user-post-collections' ),
			'default' => 'onsale',
			'type'    => 'radio',
			'options' => array(
				'always' => __( 'Always show', 'user-post-collections' ),
				'onsale' => __( 'Show when on sale', 'user-post-collections' ),
				'never'  => __( 'Never show', 'user-post-collections' ),
			),
		);
		$settings_fields['mg_upc_product'][] = array(
			'name'    => 'mg_upc_page_show_stock',
			'label'   => __( 'Stock on collection page', 'user-post-collections' ),
			'desc'    => __( 'Show stock on collection page', 'user-post-collections' ),
			'default' => '0',
			'type'    => 'radio',
			'options' => array(
				'100' => __( 'Stock of 100 or less', 'user-post-collections' ),
				'10'  => __( 'Stock of 10 or less', 'user-post-collections' ),
				'0'   => __( 'Out of Stock', 'user-post-collections' ),
				'-1'  => __( 'Never', 'user-post-collections' ),
			),
		);
		$settings_fields['mg_upc_product'][] = array(
			'name'    => 'mg_upc_page_add_to_cart',
			'label'   => __( 'Cart button on collection page', 'user-post-collections' ),
			'desc'    => __( 'Show "Add to cart" button on collection page. (A product variation is different from a variable product)', 'user-post-collections' ),
			'default' => 'on',
			'type'    => 'radio',
			'options' => array(
				'on'         => __( 'Always (that is not out of stock)', 'user-post-collections' ),
				'novariable' => __( 'Always less in variable product (otherwise the button is used as a link to the variable product)', 'user-post-collections' ),
				'off'        => __( 'Never', 'user-post-collections' ),
			),
		);

		$settings_fields['mg_upc_product'][] = array(
			'name'    => 'mg_upc_modal_show_price',
			'label'   => __( 'Price on collection modal', 'user-post-collections' ),
			'desc'    => __( 'Show prices on collection modal', 'user-post-collections' ),
			'default' => 'onsale',
			'type'    => 'radio',
			'options' => array(
				'always' => __( 'Always show', 'user-post-collections' ),
				'onsale' => __( 'Show when on sale', 'user-post-collections' ),
				'never'  => __( 'Never show', 'user-post-collections' ),
			),
		);
		$settings_fields['mg_upc_product'][] = array(
			'name'    => 'mg_upc_modal_show_stock',
			'label'   => __( 'Stock on collection modal', 'user-post-collections' ),
			'desc'    => __( 'Show stock on collection modal', 'user-post-collections' ),
			'default' => '0',
			'type'    => 'radio',
			'options' => array(
				'100' => __( 'Stock of 100 or less', 'user-post-collections' ),
				'10'  => __( 'Stock of 10 or less', 'user-post-collections' ),
				'0'   => __( 'Out of Stock', 'user-post-collections' ),
				'-1'  => __( 'Never', 'user-post-collections' ),
			),
		);


		$settings_fields['mg_upc_texts'][] = array(
			'name'    => 'add_to_list_product',
			'label'   => __( 'Add to list... (Single Product)', 'user-post-collections' ),
			'desc'    => __( 'Add to list button text on product page.', 'user-post-collections' ),
			'default' => '',
			'type'    => 'text',
		);
		$settings_fields['mg_upc_texts'][] = array(
			'name'    => 'add_to_list_product_loop',
			'label'   => __( 'Add to list... (Product loop)', 'user-post-collections' ),
			'desc'    => __( 'Add to list button text on shop/loop page.', 'user-post-collections' ),
			'default' => '',
			'type'    => 'text',
		);

		$settings_fields['mg_upc_texts'][] = array(
			'name'    => 'add_to_cart',
			'label'   => __( 'Add to cart', 'user-post-collections' ),
			'desc'    => __( 'Add to cart button text.', 'user-post-collections' ),
			'default' => '',
			'type'    => 'text',
		);
		$settings_fields['mg_upc_texts'][] = array(
			'name'    => 'add_to_cart_link',
			'label'   => __( 'Add to cart...', 'user-post-collections' ),
			'desc'    => __( 'Add to cart text when action requires selecting from options.', 'user-post-collections' ),
			'default' => '',
			'type'    => 'text',
		);

		return $settings_fields;
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
			$item['is_in_stock']  = $product->is_in_stock();
			$item['is_on_sale']   = $product->is_on_sale();

			if ( '' === $product->get_price() ) {
				$item['price'] = apply_filters( 'woocommerce_empty_price_html', '', $this );
			} elseif ( $product->is_on_sale() ) {
				$item['regular_price'] = $product->get_regular_price();
				$item['sale_price']    = $product->get_sale_price();
			} else {
				$item['price'] = $product->get_price();
			}
			$item['price_suffix'] = wp_strip_all_tags( $product->get_price_suffix() );

			$option = get_option( 'mg_upc_modal_show_price', 'onsale' );
			if ( 'never' === $option || ( 'onsale' === $option && ! $item['is_on_sale'] ) ) {
				$item['price_html'] = '';
			} else {
				$item['price_html'] = $product->get_price_html();
			}

			$option = (int) get_option( 'mg_upc_modal_show_stock', '0' );
			if (
				-1 === $option ||
				( 0 === $option && $item['is_in_stock'] ) ||
				$product->get_stock_quantity() > $option
			) {
				$item['stock_html'] = '';
			} else {
				$item['stock_html'] = wc_get_stock_html( $product );
			}

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
		if ( $post instanceof WP_Post && $post->ID > 0 ) {
			if ( MG_UPC_Helper::get_instance()->current_user_can_add_to_any( $post->post_type ) ) {
				remove_filter( 'the_content', array( 'MG_UPC_Buttons', 'the_content' ) );
				mg_upc_get_template( 'mg-upc-wc/single-product-buttons.php' );
			}
		}
	}

	/**
	 * Print the button "Add to list..." on woocommerce loop items
	 */
	public function product_button_shop_loop_item() {
		global $post;
		if ( $post instanceof WP_Post && $post->ID > 0 ) {
			if ( MG_UPC_Helper::get_instance()->current_user_can_add_to_any( $post->post_type ) ) {
				mg_upc_get_template( 'mg-upc-wc/loop-product-buttons.php' );
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
			$option = get_option( 'mg_upc_page_show_price', 'onsale' );
			if ( 'never' === $option || ( 'onsale' === $option && ! $mg_upc_item['is_on_sale'] ) ) {
				return;
			}
			$product = wc_get_product( $mg_upc_item['post_id'] );
			if ( $product ) {
				echo '<div class="mg-upc-list-item-price">' . $product->get_price_html() . '</div>'; // phpcs:ignore
			}
		}
	}

	/**
	 * Show stock of items on list page
	 */
	public static function show_stock() {
		global $mg_upc_item;
		if ( ! function_exists( 'wc_get_product' ) ) {
			return;
		}
		if ( 'product' === $mg_upc_item['post_type'] || 'product_variation' === $mg_upc_item['post_type'] ) {
			$product = wc_get_product( $mg_upc_item['post_id'] );
			if ( $product ) {
				$option = (int) get_option( 'mg_upc_page_show_stock', '0' );
				if (
					-1 === $option ||
					( 0 === $option && $mg_upc_item['is_in_stock'] ) ||
					$product->get_stock_quantity() > $option
				) {
					return;
				}
				echo wc_get_stock_html( $product ); // phpcs:ignore
			}
		}
	}

	/**
	 * Add button on item actions section (on list page)
	 */
	public static function item_cart_button() {
		global $mg_upc_item;
		if ( false === $mg_upc_item['is_in_stock'] ) {
			return;
		}
		$option = get_option( 'mg_upc_page_add_to_cart', 'on' );
		if (
			'off' === $option ||
			( 'novariable' === $option && 'variable' === $mg_upc_item['product_type'] )
		) {
			return;
		}

		$product = wc_get_product( $mg_upc_item['post_id'] );
		if ( $product ) {
			if ( ! $product->supports( 'ajax_add_to_cart' ) || ! $product->is_purchasable() ) {
				mg_upc_get_template( 'single-mg-upc/item/actions/add-to-cart-variable.php' );
			} elseif ( 'product' === $mg_upc_item['post_type'] || 'product_variation' === $mg_upc_item['post_type'] ) {
				mg_upc_get_template( 'single-mg-upc/item/actions/add-to-cart.php' );
			}
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

	public function init() {}

	public function activate( $network_wide ) { }

	public function deactivate() { }

	public function register_hook_callbacks() { }

	public function upgrade( $db_version = 0 ) { }
}
