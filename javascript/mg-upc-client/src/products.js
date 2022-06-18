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

	$( function() {
		if ( typeof wc_add_to_cart_params === 'undefined' ) {
			return false;
		}
		$( '.mg-upc-item-product' ).removeClass( 'mg-upc-hide' ).on(
			'click',
			function (e) {

				const loadingClass = 'mg-upc-btn-loading';
				const addedClass   = 'mg-upc-product-added';
				const errorClass   = 'mg-upc-product-error';

				let $btn = $( this );

				if ( $btn.hasClass( loadingClass ) ) {
					return;
				}

				let data = {
					'product_id': $btn.data( 'product' )
				};

				$( document.body ).trigger( 'adding_to_cart', [ $btn, data ] );

				const ajax_url = woocommerce_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'add_to_cart' );

				const stringAddingCartError = "Sorry, an error occurred.";

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
		);
	});

})( jQuery );
