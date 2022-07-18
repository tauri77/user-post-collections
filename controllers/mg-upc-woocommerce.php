<?php


class MG_UPC_Woocommerce extends MG_UPC_Module {

	public function __construct() {
		//before added list types on init with priority 10.. and WooCommerce already defined
		add_action( 'init', array( $this, 'pre_init' ), 5 );

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			'mg-upc/v1',
			'/cart',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'api_add_to_cart' ),
					'permission_callback' => array( $this, 'api_add_to_cart_permissions_check' ),
					'args'                => array(
						'list' => array(
							'description'       => __( 'The list id for add to cart.', 'user-post-collections' ),
							'type'              => 'integer',
							'required'          => true,
							'minimum'           => 1,
							'sanitize_callback' => 'absint',
							'validate_callback' => 'rest_validate_request_arg',
						),
					),
				),
				'schema' => array( $this, 'get_list_schema' ),
			)
		);
	}

	/**
	 * Check user permission for add a list to the cart
	 *
	 * @param $request
	 *
	 * @return bool|WP_Error
	 *
	 * @noinspection PhpUnused (API callback)
	 */
	public function api_add_to_cart_permissions_check( $request ) {
		if ( ! MG_UPC_List_Controller::get_instance()->can_read( (int) $request['list'] ) ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'You cannot view the list.', 'user-post-collections' ),
				array( 'status' => 403 )
			);
		}
		return true;
	}

	/**
	 * Add a list to the cart
	 *
	 * @param $request
	 *
	 * @return array|WP_Error
	 *
	 * @noinspection PhpUnused (API callback)
	 */
	public function api_add_to_cart( $request ) {
		$list = MG_UPC_List_Controller::get_instance()->get_list( (int) $request['list'] );

		if ( is_wp_error( $list ) ) {
			return $list;
		}

		$items       = MG_List_Model::get_instance()->items->items(
			array(
				'list_id'        => (int) $request['list'],
				'page'           => 1,
				'items_per_page' => 0,
			)
		);
		$products_id = array_map(
			function ( $item ) {
				return $item->post_id;
			},
			$items['items']
		);
		$quantities  = array_map(
			function ( $item ) {
				return $item->quantity;
			},
			$items['items']
		);
		$messages    = self::add_to_cart_batch( $products_id, $quantities );

		return self::cart_response_from_messages( $messages );
	}


	/**
	 * Before added list types on init with priority 10.. and WooCommerce already defined
	 */
	public function pre_init() {

		if ( class_exists( 'WooCommerce' ) ) {

			// Add new properties to item schema
			add_filter( 'mg_upc_api_schema_item', array( $this, 'schema_item' ) );

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

			$this->woo_texts();
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

		foreach ( $settings_fields['mg_upc_type_cart'] as $k => $config ) {
			if ( 'available_post_types' === $config['name'] ) {
				unset( $settings_fields['mg_upc_type_cart'][ $k ] );
			}
		}

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
			'desc'    => __(
				'Show "Add to cart" button on collection page. (A product variation is different from a variable product)',
				'user-post-collections'
			),
			'default' => 'on',
			'type'    => 'radio',
			'options' => array(
				'on'         => __( 'Always (that is not out of stock)', 'user-post-collections' ),
				'novariable' => __(
					'Always less in variable product (otherwise the button is used as a link to the variable product)',
					'user-post-collections'
				),
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

		$settings_fields['mg_upc_texts'][] = array(
			'name'    => 'cart_all',
			'label'   => __( 'Add all to cart', 'user-post-collections' ),
			'desc'    => __( 'Add all to cart button text on list page.', 'user-post-collections' ),
			'default' => '',
			'type'    => 'text',
		);

		$settings_fields['mg_upc_texts'][] = array(
			'name'    => 'client_cart_all',
			'label'   => __( 'Add all to cart (client js)', 'user-post-collections' ),
			'desc'    => '',
			'default' => '',
			'type'    => 'text',
		);

		return $settings_fields;
	}

	public function woo_texts() {

		MG_UPC_Texts::add_string(
			'modal_client',
			'Add all to cart',
			array(
				'default' => __( 'Add all to cart', 'user-post-collections' ),
				'option'  => 'client_cart_all',
			)
		);

		MG_UPC_Texts::add_string(
			'product',
			'Add to list...',
			array(
				'default' => __( 'Add to list...', 'user-post-collections' ),
				'option'  => 'add_to_list_product',
			)
		);

		MG_UPC_Texts::add_string(
			'product_loop',
			'Add to list...',
			array(
				'default' => __( 'Add to list...', 'user-post-collections' ),
				'option'  => 'add_to_list_product_loop',
			)
		);

		MG_UPC_Texts::add_string(
			'mg_upc_list',
			'Add to cart',
			array(
				'default' => __( 'Add to cart', 'user-post-collections' ),
				'option'  => 'add_to_cart',
			)
		);
		MG_UPC_Texts::add_string(
			'mg_upc_list',
			'Add to cart...',
			array(
				'default' => __( 'Add to cart...', 'user-post-collections' ),
				'option'  => 'add_to_cart_link',
			)
		);
		MG_UPC_Texts::add_string(
			'mg_upc_list',
			'Add all to cart',
			array(
				'default' => __( 'Add all to cart', 'user-post-collections' ),
				'option'  => 'cart_all',
			)
		);
	}

	public function schema_item( $schema ) {
		$schema_plus = array(
			'product_type'  => array(
				'description' => esc_html__(
					'Product type (simple, variable, variation...).',
					'user-post-collections'
				),
				'type'        => 'string',
				'readonly'    => true,
			),
			'price_html'    => array(
				'description' => esc_html__( 'Item price in html format.', 'user-post-collections' ),
				'type'        => 'string',
				'readonly'    => true,
			),
			'price'         => array(
				'description' => esc_html__( 'Product price.', 'user-post-collections' ),
				'type'        => 'string',
				'readonly'    => true,
			),
			'sale_price'    => array(
				'description' => esc_html__( 'Product sale price.', 'user-post-collections' ),
				'type'        => 'string',
				'readonly'    => true,
			),
			'regular_price' => array(
				'description' => esc_html__( 'Product regular price.', 'user-post-collections' ),
				'type'        => 'string',
				'readonly'    => true,
			),
			'price_min'     => array(
				'description' => esc_html__( 'Product min price (variable product).', 'user-post-collections' ),
				'type'        => 'string',
				'readonly'    => true,
			),
			'price_max'     => array(
				'description' => esc_html__( 'Product max price (variable product).', 'user-post-collections' ),
				'type'        => 'string',
				'readonly'    => true,
			),
			'price_suffix'  => array(
				'description' => esc_html__( 'Product price suffix.', 'user-post-collections' ),
				'type'        => 'string',
				'readonly'    => true,
			),
			'is_on_sale'    => array(
				'description' => esc_html__( 'Is product on sale?', 'user-post-collections' ),
				'type'        => 'boolean',
				'readonly'    => true,
			),
			'is_in_stock'   => array(
				'description' => esc_html__( 'Is product in stock?', 'user-post-collections' ),
				'type'        => 'boolean',
				'readonly'    => true,
			),
			'stock_html'    => array(
				'description' => esc_html__( 'Item stock html.', 'user-post-collections' ),
				'type'        => 'string',
				'readonly'    => true,
			),
		);

		return array_merge( $schema, $schema_plus );
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
						$item['image']          = wp_get_attachment_image_url( $product->get_image_id() ); // or add 'medium'
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
			if ( null === WC()->cart ) {
				wc_load_cart();
			}
			$GLOBALS['product'] = $product;

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
				if ( mg_upc_list_check_support( (int) $item['list_id'], 'quantity' ) ) {
					$item['price_html'] = self::get_price_html( $product, $item['quantity'] );
				} else {
					$item['price_html'] = self::get_price_html( $product, 1 );
				}
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
	 * Create response for add to cart request
	 *
	 * @param $messages
	 *
	 * @return array
	 */
	private static function cart_response_from_messages( $messages ) {
		ob_start();
		woocommerce_mini_cart();
		$mini_cart = ob_get_clean();
		$data      = array(
			'fragments' => apply_filters(
				'woocommerce_add_to_cart_fragments',
				array(
					'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
				)
			),
			'cart_hash' => WC()->cart->get_cart_hash(),
		);

		$data['msg'] = implode(
			"\n",
			array_filter(
				array_map(
					function ( $err ) {
						return false === $err['error'] ? $err['msg'] : false;
					},
					$messages
				)
			)
		);

		$data['err'] = implode(
			"\n",
			array_filter(
				array_map(
					function ( $err ) {
						return false === $err['error'] ? false : $err['msg'];
					},
					$messages
				)
			)
		);

		return $data;
	}

	public static function add_to_cart_batch( $products_id, $quantities ) {
		if ( null === WC()->cart ) {
			wc_load_cart();
		}
		WC()->cart->maybe_set_cart_cookies();

		$messages    = array();
		$on_cart     = array();
		$products_id = array_values( $products_id );
		$quantities  = array_values( $quantities );

		foreach ( $products_id as $i => $product_id ) {
			$product_id = $products_id[ $i ];
			$product_id = apply_filters( 'woocommerce_add_to_cart_product_id', $product_id );
			$product    = wc_get_product( $product_id );
			if ( isset( $quantities[ $i ] ) && 0 === (int) $quantities[ $i ] ) {
				$messages[] = array(
					'msg'   => sprintf(
						// translators: %s is product name
						__( 'Product "%s" with quantity equal to zero was not added.', 'user-post-collections' ),
						wp_strip_all_tags( $product->get_title() )
					),
					'error' => true,
					'url'   => get_permalink( $product_id ),
				);
				continue;
			}
			$quantity = empty( $quantities[ $i ] ) ? 1 : wc_stock_amount( absint( $quantities[ $i ] ) );

			$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity );
			$product_status    = get_post_status( $product_id );
			$variation_id      = 0;
			$variation         = array();

			if ( $product && 'variation' === $product->get_type() ) {
				$variation_id = $product_id;
				$product_id   = $product->get_parent_id();
				$variation    = $product->get_variation_attributes();
			}

			try {
				$cart_res = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation );
				if (
					$passed_validation &&
					false !== $cart_res &&
					'publish' === $product_status
				) {

					do_action( 'woocommerce_ajax_added_to_cart', $product_id );

					$on_cart[ $product_id ] = $quantity;
				} else {
					$messages[] = array(
						'msg'   => sprintf(
							// translators: %s is product name
							__( 'Error on add item "%s" to cart.', 'user-post-collections' ),
							$product->get_title()
						),
						'error' => true,
						'url'   => get_permalink( $product_id ),
					);
				}
			} catch ( Exception $e ) {
				$messages[] = array(
					'msg'   => __( 'Unknown Error on add items to cart', 'user-post-collections' ),
					'error' => true,
					'url'   => get_permalink( $product_id ),
				);
			}
		}

		//Use woocommerce translation...
		$titles = array();
		$count  = 0;
		foreach ( $on_cart as $product_id => $qty ) {
			$times    = ( $qty > 1 ? absint( $qty ) . ' x ' : '' );
			$desc     = sprintf(
				'“%s”',
				strip_tags( get_the_title( $product_id ) )
			);
			$titles[] = $times . $desc;
			$count   += $qty;
		}

		$titles = array_filter( $titles );

		$general_message = sprintf(
			/* translators: %s: product name */
			_n(
				'%s has been added to your cart.',
				'%s have been added to your cart.',
				$count,
				'woocommerce'
			),
			wc_format_list_of_items( $titles )
		);

		$messages[] = array(
			'msg'   => $general_message,
			'error' => false,
			'url'   => wc_get_cart_url(),
		);

		return $messages;
	}

	/**
	 * Returns the price in html format.
	 *
	 * @param $product
	 * @param $quantity
	 *
	 * @return string
	 */
	public static function get_price_html( $product, $quantity = 1 ) {
		$pre = '';
		$pos = '';
		if ( 0 === (int) $quantity ) {
			$pre      = '<span class="mg-upc-zero-quantity" aria-hidden="true">';
			$pos      = '</span>';
			$quantity = 1;
		}

		if ( '' === $product->get_price() ) {
			//TODO: quantity?
			$price = apply_filters( 'woocommerce_empty_price_html', '', $product );
		} elseif ( $product->is_on_sale() ) {
			$price = wc_format_sale_price(
				wc_get_price_to_display(
					$product,
					array(
						'price' => $product->get_regular_price(),
						'qty'   => $quantity,
					)
				),
				wc_get_price_to_display( $product, array( 'qty' => $quantity ) )
			) . $product->get_price_suffix();
		} else {
			$price = wc_price( wc_get_price_to_display( $product, array( 'qty' => $quantity ) ) ) . $product->get_price_suffix();
		}

		return $pre . apply_filters( 'woocommerce_get_price_html', $price, $product ) . $pos;
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
				if ( 'incl' === $tax_display_mode ) {
					$child_prices[] = wc_get_price_including_tax( $child );
				} else {
					$child_prices[] = wc_get_price_excluding_tax( $child );
				}
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

	public static function item_cart_all_button() {
		global $mg_upc_list;
		if ( 'cart' === $mg_upc_list['type'] ) {
			mg_upc_get_template( 'mg-upc-wc/single-all-cart-buttons.php' );
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
				echo '<div class="mg-upc-list-item-price">';
				if ( mg_upc_list_check_support( (int) $mg_upc_item['list_id'], 'quantity' ) ) {
					echo self::get_price_html( $product, $mg_upc_item['quantity'] ); // phpcs:ignore
				} else {
					echo self::get_price_html( $product, 1 ); // phpcs:ignore
				}
				echo '</div>';
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

		if ( ! function_exists( 'wc_get_product' ) || false === $mg_upc_item['is_in_stock'] ) {
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

	public function init() {
		if ( class_exists( 'WooCommerce' ) ) {

			$list_type_config = array(
				'label'                => __( 'Cart', 'user-post-collections' ),
				'plural_label'         => __( 'Cart Lists', 'user-post-collections' ),
				'description'          => __( 'List to add items to a virtual cart', 'user-post-collections' ),
				'default_status'       => 'private',
				'available_post_types' => array( 'product' ),
				'supported_features'   => array(
					'editable_title',
					'editable_content',
					'editable_item_description',
					'show_in_my_lists',
					'show_in_settings',
					'quantity',
				),
			);
			mg_upc_register_list_type( 'cart', $list_type_config );

			add_filter(
				'mg_upc_pre_add_item',
				function( $to_save, $list ) {
					if ( 'cart' === $list->type ) {
						$product = wc_get_product( $to_save['post_id'] );
						if ( ! isset( $to_save['quantity'] ) ) {
							$to_save['quantity'] = $product->get_min_purchase_quantity();
						}
						if ( ! $product->is_purchasable() ) {
							return new WP_Error(
								'mg_upc_no_purchasable',
								__( 'This list type only support purchasable items', 'user-post-collections' ),
								array(
									'status' => 409,
								)
							);
						}
					}
					return $to_save;
				},
				10,
				2
			);

			//This not managed with version! Woo can install after that UPC..
			$activated = get_option( 'mg_upc_woo_activated', array() );
			if ( ! in_array( 'cart_type', $activated, true ) ) {
				$cart_type = MG_UPC_Helper::get_instance()->get_list_type( 'cart' );
				if ( false !== $cart_type ) {
					MG_UPC_List_Types_Register::set_initial_roles_caps( array( $cart_type ) );
				}
				$activated[] = 'cart_type';
				update_option( 'mg_upc_woo_activated', $activated, true );
			}
		}
	}

	public function activate( $network_wide ) { }

	public function deactivate() { }

	public function register_hook_callbacks() { }

	public function upgrade( $db_version = 0 ) { }
}
