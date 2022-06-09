/*globals jQuery:false*/

(function ($){

	function voteItem(lisID, postID, $listNode, $itemNode) {
		const $list    = $listNode ? $listNode : $itemNode.parent();
		const $buttons = $list.find( '.mg-upc-item-vote' ).attr( 'disabled', true );
		mgUpcApiClient.vote(
			lisID,
			postID,
			{
				'context': 'web',
				'posts': getShowingPost( $list )
			}
		).then(
			function ( response ) {
				$( document.body ).trigger( 'mg_upc_vote_response', [ response, $list ] );
			}
		).catch(
			function ( reason ) {
				$buttons.attr( 'disabled', false );
				if ( reason.response?.data?.message ) {
					if ( $itemNode ) {
						$itemNode.append( get_alert( reason.response.data.message ) );
					} else {
						$list.before( get_alert( reason.response.data.message ) );
					}
				}
			}
		);
	}

	$( ".mg-upc-item-vote" ).on(
		'click',
		function() {
			const voteData = $( this ).data( 'vote' ).split( ',' );
			if ( 2 === voteData.length ) {
				voteItem( voteData[0], voteData[1], false, $( this ).closest( ".mg-upc-item" ) );
			}
		}
	);
	$(
		function() {
			$( '.mg-upc-vote' ).each(
				function () {
					const $content = $( this );
					voteItem(
						$content.data( "id" ),
						0,
						$content.find( '.mg-upc-items-container' ),
						false
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

	$( document.body ).on(
		'mg_upc_vote_response',
		function( event, response, $list ) {
			if ( ! response.data ) {
				return;
			}
			const total = parseInt( response.data.vote_counter, 10 );
			const $btns = $list.find( '.mg-upc-item-vote' );
			$list.data( 'votes', total );
			if ( response.data.can_vote ) {
				$btns.attr( 'disabled', false ).show();
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
					const $item = $list.find( '.mg-upc-item[data-pid=' + item.post_id + ']' );
					const votes = parseInt( item.votes, 10 );
					$( document.body ).trigger( 'mg_upc_item_vote_set', [ $item, votes, total ] );
				}
			);
		}
	);

	$( document.body ).on(
		'mg_upc_item_vote_set',
		function (ev, $item, votes, total) {
			const percent = total > 0 ? Math.round( 1000 * votes / total ) / 10 : 0;
			const $widget = $item.find( '.mg-upc-votes' );
			$widget.find( '.mg-upc-item-votes-number' ).html( votes );
			$widget.find( '.mg-upc-item-percent' ).html( percent + '%' );
			$widget.find( '.mg-upc-item-bar-progress' ).animate( {width: percent + '%' } );
			$widget.show();
		}
	);

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
})( jQuery );
