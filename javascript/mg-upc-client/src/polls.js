/*globals jQuery:false*/

(function ($){

	$( ".mg-upc-item-vote" ).on(
		'click',
		function() {
			const voteData = $( this ).data( 'vote' ).split( ',' );
			if ( 2 === voteData.length ) {
				let $item      = $( this ).parents( ".mg-upc-item" )
				let $list      = $item.parent()
				const $buttons = $list.find( '.mg-upc-item-vote' ).attr( 'disabled', true );
				mgUpcApiClient.vote(
					voteData[0],
					voteData[1],
					{
						'context': 'web',
						'posts': getShowingPost( $list )
					}
				).then(
					function ( response ) {
						setVotesFromResponse( response, $list );
					}
				).catch(
					function ( reason ) {
						$buttons.attr( 'disabled', false );
						if ( reason.response.data.message ) {
							$item.append( get_alert( reason.response.data.message ) );
						}
					}
				);
			}
		}
	);
	$(
		function() {
			$( '.mg-upc-vote' ).each(
				function () {
					const $content = $( this );
					const $list    = $content.find( '.mg-upc-items-container' );
					const list_id  = $content.data( "id" );
					mgUpcApiClient.vote(
						list_id,
						0,
						{
							'context': 'web',
							'posts': getShowingPost( $list )
						}
					).then(
						function ( response ) {
							setVotesFromResponse( response, $list );
						}
					).catch(
						function ( reason ) {
							if ( reason.response.data.message ) {
								$list.before( get_alert( reason.response.data.message ) );
							}
						}
					);
				}
			);
		}
	);

	function getShowingPost( $list ) {
		return [ ...$list.children().map(
			function(){
				return $( this ).data( 'pid' );
			}
		) ].join( ',' );
	}

	function setVotesFromResponse(response, $list) {
		if ( ! response.data ) {
			return;
		}
		const total = parseInt( response.data.vote_counter, 10 );
		const $btns = $list.find( '.mg-upc-item-vote' );
		$list.data( 'votes', total );
		if ( response.data.can_vote ) {
			$btns.show();
		} else {
			$btns.animate(
				{
					width: 0,
					padding: 0,
					opacity: 0
				},
				200,
				function() {
					$btns.remove();
				}
			);
		}
		response.data.posts.forEach(
			function(item) {
				const $this   = $list.find( '.mg-upc-item[data-pid=' + item.post_id + ']' ).find( '.mg-upc-votes' );
				const votes   = parseInt( item.votes, 10 );
				const percent = total > 0 ? Math.round( 1000 * votes / total ) / 10 : 0;
				$this.find( '.item-votes-number' ).html( votes );
				$this.find( '.item-percent' ).html( percent + '%' );
				$this.find( '.item-bar-progress' ).animate( {width: percent + '%' } );
				$this.show();
			}
		);
	}

	function get_alert( message, type="error" ) {
		const $container = $( '<div>' ).addClass( "mg-upc-alert mg-upc-alert-" + type );
		$container.append( $( '<p>' ).html( message ) );
		const $close = $( '<a class="mg-upc-alert-close" href="#"><span class="mg-upc-icon upc-font-close"></span></a>' )
			.on(
			'click',
			function () {
				$container.remove();
				return false;
			}
		);
		$container.append( $close );
		return $container;
	}

	return false;
})(jQuery);

