/*globals jQuery:false,wp:false*/

/**
 * Tabbable JavaScript codes & Initiate Color Picker
 *
 * This code uses localstorage for displaying active tabs
 */
jQuery( document ).ready(
	function ( $ ) {
		'use strict';
		if ( typeof jQuery().wpColorPicker !== "undefined" ) {
			//Initiate Color Picker
			$( '.mg-upc-color-picker-field' ).wpColorPicker();
		}
		if ( typeof jQuery().datepicker != "undefined" ) {
			//Initiate Color Picker
			$( '.mg-upc-date-picker-field' ).each(
				function () {
					var opt = {
						dateFormat: "yy-mm-dd", minDate: $( this ).data( 'min-date' ), maxDate: $( this ).data( 'max-date' )
					};
					$( this ).datepicker( opt );
				}
			);

			$( '.mg-upc-datetime-picker-field' ).each(
				function () {
					var $datetime      = $( this );
					var opt            = {
						dateFormat: "yy-mm-dd", minDate: $( this ).data( 'min-date' ), maxDate: $( this ).data( 'max-date' )
					};
					var gotFill2       = function ( i ) {
						if ( isNaN( i ) ) {
							return "00";
						}
						return (i < 10) ? ("0" + parseInt( i )) : i;
					};
					var updateDateTime = function () {
						var cd = $( date ).datepicker( "getDate" );
						$datetime.val( cd.getFullYear() + "-" + gotFill2( cd.getMonth() + 1 ) + "-" + gotFill2( cd.getDate() ) + " " + gotFill2( t[ 0 ].val() ) + ":" + gotFill2( t[ 1 ].val() ) + ":" + gotFill2( t[ 2 ].val() ) );
					};
					$datetime.css( { "display": "none" } );
					var d    = new Date( $datetime.val() );
					var date = $( '<input>' ).css( { "width": "120px" } ).datepicker( opt ).datepicker( "setDate", d ).change( updateDateTime ).insertAfter( this );
					var t    = [];
					t[ 0 ]   = $( '<input title="Hours" type="number" min="0" max="23">' ).val( gotFill2( d.getHours() ) ).insertAfter( date );
					var sep  = $( '<span>:</span>' ).insertAfter( t[ 0 ] );
					t[ 1 ]   = $( '<input title="Minutes" type="number" min="0" max="59">' ).val( gotFill2( d.getMinutes() ) ).insertAfter( sep );
					var sep2 = $( '<span>:</span>' ).insertAfter( t[ 1 ] );
					t[ 2 ]   = $( '<input title="Seconds" type="number" min="0" max="59"">' ).val( gotFill2( d.getSeconds() ) ).insertAfter( sep2 );
					t.forEach(
						function ( el ) {
							var onEventChange = function () {
								$( this ).val( gotFill2( $( this ).val() ) );
								updateDateTime();
							};
							$( el ).css( { "width": "55px" } ).on( "input change keyup click", onEventChange );
						}
					);
				}
			);
		}

		//Para remover item del array
		$( ".mg-upc-array-item-remove" ).on(
			'click',
			function () {
				$( "[name^='" + $( this ).data( "item-remove" ) + "']" ).remove();
				$( document.getElementById( $( this ).data( "item-remove" ) + "[div]" ) ).remove();
			}
		);

		if ( $.fn.sortable ) {
			$( ".mg-upc-sortable-items-container" ).sortable(
				{
					handle: '.dashicons-sort', update: function () {
					}
				}
			);
		}

		//Para hacer valido o no el nuevo item
		$( ".mg-upc-add-array-toggle" ).change(
			function () {
				var fake  = $( this ).data( "from" );
				var real  = $( this ).data( "to" );
				var check = this;
				if ( $( check ).is( ':checked' ) ) {
					$( this ).parents( "div" ).first().find( ".mg-upc-array-new-item-slide" ).show();
				} else {
					$( this ).parents( "div" ).first().find( ".mg-upc-array-new-item-slide" ).hide();
				}
				$( this ).parents( "div" ).first().find( "input, textarea, select" ).each(
					function () {
						if ( $( this ).hasClass( "add-array-toggle" ) ) {
							return;
						}
						if ( $( check ).is( ':checked' ) ) {
							if ( $( this ).attr( "name" ).indexOf( fake ) > -1 ) {
								$( this ).attr( "name", $( this ).attr( "name" ).replace( fake, real ) );
							}
						} else {
							if ( $( this ).attr( "name" ).indexOf( fake ) < 0 ) {
								$( this ).attr( "name", $( this ).attr( "name" ).replace( real, fake ) );
							}
						}
					}
				);
			}
		);

		// Switches option sections
		$( '.group' ).hide();
		var activetab = '';
		if ( typeof (localStorage) != 'undefined' ) {
			activetab = localStorage.getItem( "activetab" );
		}

		//if url has section id as hash then set it as active or override the current local storage value
		if ( window.location.hash && $( window.location.hash ).hasClass( 'group' ) ) {
			activetab = window.location.hash;
			if ( typeof (localStorage) != 'undefined' ) {
				localStorage.setItem( "activetab", activetab );
			}
			$( window ).on(
				"load",
				function(){
					var notice = $( '.wrap .notice' );
					if ( notice.length ) {
						$( 'html,body' ).animate(
							{
								scrollTop: notice.first().offset().top - 40
							},
							'slow'
						);
					}
				}
			);
		}

		if ( activetab !== '' && $( activetab ).length ) {
			$( activetab ).fadeIn();
		} else {
			$( '.group:first' ).fadeIn();
		}
		$( '.group .collapsed' ).each(
			function () {
				$( this ).find( 'input:checked' ).parent().parent().parent().nextAll().each(
					function () {
						if ( $( this ).hasClass( 'last' ) ) {
							$( this ).removeClass( 'hidden' );
							return false;
						}
						$( this ).filter( '.hidden' ).removeClass( 'hidden' );
					}
				);
			}
		);

		if ( activetab !== '' && $( activetab + '-tab' ).length ) {
			$( activetab + '-tab' ).addClass( 'nav-tab-active' );
		} else {
			$( '.nav-tab-wrapper a:first' ).addClass( 'nav-tab-active' );
		}
		$( '.mg-upc-nav-tab-wrapper a' ).on(
			'click',
			function ( evt ) {
				$( '.nav-tab-wrapper a' ).removeClass( 'nav-tab-active' );
				$( this ).addClass( 'nav-tab-active' ).blur();
				var clicked_group = $( this ).attr( 'href' );
				if ( typeof (localStorage) != 'undefined' ) {
					localStorage.setItem( "activetab", $( this ).attr( 'href' ) );
				}
				$( '.group' ).hide();
				$( clicked_group ).fadeIn();
				evt.preventDefault();
			}
		);

		$( '.mg-upc-browse' ).on(
			'click',
			function ( event ) {
				event.preventDefault();

				var self = $( this );

				// Create the media frame.
				var file_frame = wp.media.frames.file_frame = wp.media(
					{
						title: self.data( 'uploader_title' ), button: {
							text: self.data( 'uploader_button_text' ),
						}, multiple: false
					}
				);

				file_frame.on(
					'select',
					function () {
						var attachment = file_frame.state().get( 'selection' ).first().toJSON();
						self.prev( '.wpsa-url' ).val( attachment.url ).change();
					}
				);

				// Finally, open the modal
				file_frame.open();
			}
		);
	}
);
