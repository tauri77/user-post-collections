import translate from "./helpers/translate";
import {get_alert, str_nl2br} from "./helpers/functions";

(function ($) {

	$( '.mg-upc-add-product-to-list' ).on(
		'click',
		function() {
			let productId = $( this ).data( 'id' );
			let $parent   = $( this ).closest( '.product,.summary' );

			//Search for variation product
			const variationId = $parent.find( "[name='variation_id']" );
			if ( variationId.length > 0 && parseInt( variationId.val(), 10 ) > 0 ) {
				productId = variationId.val();
			}

			window.addItemToList( productId );

			return false;
		}
	);

	const loadingClass = 'mg-upc-btn-loading';
	const addedClass   = 'mg-upc-product-added';
	const errorClass   = 'mg-upc-product-error';

	const stringAddingCartError = "Sorry, an error occurred.";

	function add_item_product_to_cart(e, $btn, force_one) {

		if ( $btn.hasClass( loadingClass ) ) {
			return false;
		}

		let data = {
			'product_id': $btn.data( 'product' ),
			'quantity': $btn.data( 'quantity' )
		};

		if ( data.quantity == "0" ) {
			if ( force_one ) {
				data.quantity = 1;
			} else {
				if ( confirm( translate( "The quantity is zero! Do you want to add a unit?" ) ) ) {
					return add_item_product_to_cart( e, $btn, true );
				}
				return false;
			}
		}

		$( document.body ).trigger( 'adding_to_cart', [ $btn, data ] );

		const ajax_url = woocommerce_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'add_to_cart' );

		$.ajax(
			{
				type: 'POST',
				url: ajax_url,
				data: data,
				beforeSend: function (response) {
					$btn.removeClass( addedClass + ' ' + errorClass ).addClass( loadingClass );
				},
				success: function (response) {
					$btn.removeClass( loadingClass );

					if ( ! response ) {
						return;
					}

					if (response.error && response.product_url) {
						//Out of stock
						alert( stringAddingCartError );
						return;
					}

					$btn.addClass( addedClass )
					$( document.body ).trigger( 'added_to_cart', [ response.fragments, response.cart_hash, $btn ] );
				},
				error: function () {
					$btn.addClass( errorClass ).removeClass( loadingClass );
					alert( stringAddingCartError );
				}
			}
		);

		return false;
	}

	function add_item_product_to_cart(e, $btn, force_one) {

		if ( $btn.hasClass( loadingClass ) ) {
			return false;
		}

		let data = {
			'product_id': $btn.data( 'product' ),
			'quantity': $btn.data( 'quantity' )
		};

		if ( data.quantity == "0" ) {
			if ( force_one ) {
				data.quantity = 1;
			} else {
				if ( confirm( translate( "The quantity is zero! Do you want to add a unit?" ) ) ) {
					return add_item_product_to_cart( e, $btn, true );
				}
				return false;
			}
		}

		$( document.body ).trigger( 'adding_to_cart', [ $btn, data ] );

		const ajax_url = woocommerce_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'add_to_cart' );

		$.ajax(
			{
				type: 'POST',
				url: ajax_url,
				data: data,
				beforeSend: function (response) {
					$btn.removeClass( addedClass + ' ' + errorClass ).addClass( loadingClass );
				},
				success: function (response) {
					$btn.removeClass( loadingClass );

					if ( ! response ) {
						return;
					}

					if (response.error && response.product_url) {
						//Out of stock
						alert( stringAddingCartError );
						return;
					}

					$btn.addClass( addedClass )
					$( document.body ).trigger( 'added_to_cart', [ response.fragments, response.cart_hash, $btn ] );
				},
				error: function () {
					$btn.addClass( errorClass ).removeClass( loadingClass );
					alert( stringAddingCartError );
				}
			}
		);

		return false;
	}

	$( function() {
		if ( typeof wc_add_to_cart_params === 'undefined' ) {
			return false;
		}
		$( '.mg-upc-item-product' ).removeClass( 'mg-upc-hide' ).on(
			'click',
			function (e) {
				return add_item_product_to_cart( e, $( this ), false );
			}
		);
		$( '.mg-upc-add-list-to-cart' ).removeClass( 'mg-upc-hide' ).on(
			'click',
			function (e) {
				const $btn = $(this);
				if ( $btn.hasClass( loadingClass ) ) {
					return false;
				}
				$btn.removeClass( addedClass + ' ' + errorClass ).addClass( loadingClass );
				window.mgUpcAddListToCart( $( this ).data( 'id' ) ).then( function (response) {
					$btn.removeClass( loadingClass );
					if ( response.err ) {
						$btn.before( get_alert( str_nl2br( response.err ) ) );
					}
					if ( response.msg ) {
						$btn.before( get_alert( str_nl2br( response.msg ), 'success' ) );
					}
					$btn.addClass( addedClass )
					$( document.body ).trigger( 'added_to_cart', [ response.fragments, response.cart_hash, $btn ] );
				}).catch( ( reason ) => {
					$btn.removeClass( loadingClass );
					if ( reason.response?.data?.message ) {
						$btn.before( get_alert( str_nl2br( reason.response.data.message ) ) );
					} else {
						alert( stringAddingCartError );
					}
				});
				return false;
			}
		);
	});

})( jQuery );
