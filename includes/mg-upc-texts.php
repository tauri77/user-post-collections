<?php

class MG_UPC_Texts {

	public static $texts = array();
	public static $mods  = array();

	public static function init() {
		self::$texts['mg_upc_list'] = array(
			'Vote'          => array(
				'default' => __( 'Vote', 'user-post-collections' ),
				'option'  => 'vote_action',
			),
			'%s votes'      => array(
				// translators: %s is the number of votes
				'default' => __( '%s votes', 'user-post-collections' ),
				'option'  => 'total_votes',
			),
			'Created by %s' => array(
				// translators: %s is author user
				'default' => __( 'Created by %s', 'user-post-collections' ),
				'option'  => 'created_by',
			),
			'Quantity'      => array(
				'default' => __( 'Quantity', 'user-post-collections' ),
				'option'  => 'quantity',
			),
		);

		self::$texts['single'] = array(
			'Add to list...' => array(
				'default' => __( 'Add to list...', 'user-post-collections' ),
				'option'  => 'add_to_list',
			),
		);

		self::$texts['modal_client'] = array(
			'My Lists'                             => array(
				'default' => __( 'My Lists', 'user-post-collections' ),
				'option'  => 'client_my_lists',
			),
			'Create List'                          => array(
				'default' => __( 'Create List', 'user-post-collections' ),
				'option'  => 'client_create_list',
			),
			'Save'                                 => array(
				'default' => __( 'Save', 'user-post-collections' ),
				'option'  => 'client_save',
			),
			'Cancel'                               => array(
				'default' => __( 'Cancel', 'user-post-collections' ),
				'option'  => 'client_cancel',
			),
			'Add Comment'                          => array(
				'default' => __( 'Add Comment', 'user-post-collections' ),
				'option'  => 'client_add_comment',
			),
			'Quantity'                             => array(
				'default' => __( 'Quantity', 'user-post-collections' ),
				'option'  => 'client_quantity',
			),
			'Edit Comment'                         => array(
				'default' => __( 'Edit Comment', 'user-post-collections' ),
				'option'  => 'client_edit_comment',
			),
			'Edit'                                 => array(
				'default' => __( 'Edit', 'user-post-collections' ),
				'option'  => 'client_edit',
			),
			'Title'                                => array(
				'default' => __( 'Title', 'user-post-collections' ),
				'option'  => 'client_title',
			),
			'Description'                          => array(
				'default' => __( 'Description', 'user-post-collections' ),
				'option'  => 'client_description',
			),
			'Status'                               => array(
				'default' => __( 'Status', 'user-post-collections' ),
				'option'  => 'client_status',
			),
			'Remove List'                          => array(
				'default' => __( 'Remove List', 'user-post-collections' ),
				'option'  => 'client_remove_list',
			),
			'Share'                                => array(
				'default' => __( 'Share', 'user-post-collections' ),
				'option'  => 'client_share',
			),
			'Copy'                                 => array(
				'default' => __( 'Copy', 'user-post-collections' ),
				'option'  => 'client_copy',
			),
			'Copied!'                              => array(
				'default' => __( 'Copied!', 'user-post-collections' ),
				'option'  => 'client_copied',
			),
			'Email'                                => array(
				'default' => __( 'Email', 'user-post-collections' ),
				'option'  => 'client_email',
			),
			'Select where the item will be added:' => array(
				'default' => __( 'Select where the item will be added:', 'user-post-collections' ),
				'option'  => 'client_select_to_add',
			),
			'Select a list type:'                  => array(
				'default' => __( 'Select a list type:', 'user-post-collections' ),
				'option'  => 'client_select_list_type',
			),
			'Total votes:'                         => array(
				'default' => __( 'Total votes:', 'user-post-collections' ),
				'option'  => 'client_total_votes',
			),
			'Unknown List Type...'                 => array(
				'default' => __( 'Unknown List Type...', 'user-post-collections' ),
				'option'  => 'client_unknown_type',
			),
			'Add to...'                            => array(
				'default' => __( 'Add to...', 'user-post-collections' ),
				'option'  => 'client_add_to_title',
			),
		);

		do_action( 'mg_upc_texts_loaded' );

		self::$mods = get_option( 'mg_upc_texts', array() );
	}

	public static function add_string( $context, $text, $value ) {
		if ( ! isset( self::$texts[ $context ] ) ) {
			self::$texts[ $context ] = array();
		}
		self::$texts[ $context ][ $text ] = $value;
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

	public static function get_context_array( $context ) {
		$ret = array();
		if ( isset( self::$texts[ $context ] ) ) {
			foreach ( self::$texts[ $context ] as $text => $config ) {
				if ( isset( $config['option'] ) && ! empty( self::$mods[ $config['option'] ] ) ) {
					$ret[ $text ] = self::$mods[ self::$texts[ $context ][ $text ]['option'] ];
					continue;
				}
				$ret[ $text ] = self::$texts[ $context ][ $text ]['default'];
			}
		}

		return $ret;
	}
}
