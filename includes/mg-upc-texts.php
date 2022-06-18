<?php

class MG_UPC_Texts {

	public static $texts = array();
	public static $mods  = array();

	public function __construct() {
		self::$texts['mg_upc_list'] = array(
			'Add to cart'    => array(
				'default' => __( 'Add to cart', 'user-post-collections' ),
				'option'  => 'add_to_cart',
			),
			'Add to cart...' => array(
				'default' => __( 'Add to cart...', 'user-post-collections' ),
				'option'  => 'add_to_cart_link',
			),
			'Vote'           => array(
				'default' => __( 'Vote', 'user-post-collections' ),
				'option'  => 'vote_action',
			),
			'%s votes'       => array(
				// translators: %s is the number of votes
				'default' => __( '%s votes', 'user-post-collections' ),
				'option'  => 'total_votes',
			),
			'Created by %s'  => array(
				// translators: %s is author user
				'default' => __( 'Created by %s', 'user-post-collections' ),
				'option'  => 'created_by',
			),
		);

		self::$texts['single'] = array(
			'Add to list...' => array(
				'default' => __( 'Add to list...', 'user-post-collections' ),
				'option'  => 'add_to_list',
			),
		);

		self::$texts['product'] = array(
			'Add to list...' => array(
				'default' => __( 'Add to list...', 'user-post-collections' ),
				'option'  => 'add_to_list_product',
			),
		);

		self::$texts['product_loop'] = array(
			'Add to list...' => array(
				'default' => __( 'Add to list...', 'user-post-collections' ),
				'option'  => 'add_to_list_product_loop',
			),
		);

		self::$mods = get_option( 'mg_upc_texts', array() );
	}

	public static function get_default( $text, $context = 'mg_upc_list' ) {
		if ( isset( self::$texts[ $context ] ) && isset( self::$texts[ $context ][ $text ] ) ) {
			return self::$texts[ $context ][ $text ]['default'];
		}
		return '';
	}

	public static function get( $text, $context = 'mg_upc_list' ) {
		if ( isset( self::$texts[ $context ] ) && isset( self::$texts[ $context ][ $text ] ) ) {
			if (
				isset( self::$texts[ $context ][ $text ]['option'] ) &&
				! empty( self::$mods[ self::$texts[ $context ][ $text ]['option'] ] )
			) {
				return self::$mods[ self::$texts[ $context ][ $text ]['option'] ];
			}
			return self::$texts[ $context ][ $text ]['default'];
		}

		return $text;
	}
}
new MG_UPC_Texts();
