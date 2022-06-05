(function ($) {

	$( '.mg-upc-add-product-to-list' ).on(
		'click',
		function() {
			let productId = $( this ).data( 'id' );
			let $parent   = $( this ).closest( '.product,.summary' );

			//Search for variation product
			const variationId = $parent.find( "[name='variation_id']" );
			if ( variationId.length > 0 ) {
				productId = variationId.val();
			}

			window.addItemToList( productId );
		}
	);

	$( function() {
		if ( typeof wc_add_to_cart_params === 'undefined' ) {
			return false;
		}
		$( '.mg-upc-item-product' ).removeClass( 'mg-upc-hide' ).on(
			'click',
			function (e) {

				let $btn = $( this );

				let data = {
					'product_id': $btn.data( 'product' )
				};

				$( document.body ).trigger( 'adding_to_cart', [ $btn, data ] );

				const ajax_url = woocommerce_params.wc_ajax_url.toString().replace( '%%endpoint%%', 'add_to_cart' );

				const loadingClass = 'mg-upc-btn-loading';

				const stringAddingCartError = "Sorry, an error occurred.";

				$.ajax(
					{
						type: 'POST',
						url: ajax_url,
						data: data,
						beforeSend: function (response) {
							$btn.removeClass( 'mg-upc-product-added' ).addClass( loadingClass );
						},
						success: function (response) {
							if ( ! response ) {
								return;
							}

							$btn.addClass( 'mg-upc-product-added' ).removeClass( loadingClass );

							if (response.error && response.product_url) {
								console.log( response.error );
								alert( stringAddingCartError );
								return;
							}

							$( document.body ).trigger( 'added_to_cart', [ response.fragments, response.cart_hash, $btn ] );
						},
						error: function () {
							$btn.addClass( 'mg-upc-product-error' ).removeClass( loadingClass );
							alert( stringAddingCartError );
						}
					}
				);

				return false;
			}
		);
	});

})( jQuery );
