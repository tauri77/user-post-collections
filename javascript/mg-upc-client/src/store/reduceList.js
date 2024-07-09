import {
	SET_LIST_OF_LIST,
	SET_LIST,
	UPDATE_LIST,
	CREATE_LIST,
	SET_LIST_TOTAL_PAGE,
	SET_LIST_PAGE,
	SET_LIST_ITEMS,
	REMOVE_LIST_ITEM,
	UPDATE_LIST_ITEM,
	MOVE_LIST_ITEM,
	MOVE_LIST_ITEM_NEXT,
	MOVE_LIST_ITEM_PREV, ADD_LIST_ITEM
} from "./actionTypes";
import { typeSupport, cloneObj } from "../helpers/functions";

export default function reduceList (state, action) {
	const { type, payload } = action;

	/*function moveOutOfPage() {
		const positionBase = parseInt( state.items[0].position, 10 );

		newStateItems = state.items.slice();
		newStateItems.splice( payload, 1 );

		newStateItems.forEach(
			( item, idx) => {
				newStateItems[idx].position = positionBase + idx;
			}
		);

		return getCloned( {items: newStateItems } );
	}*/

	let newStateItems;
    let newState;

	const getCloned = (override = false) => {
		if ( ! newState ) {
			newState = ( false === state ) ? {} : cloneObj( state );
		}
		if (override) {
			for ( const i in override ) {
				newState[ i ] = override[ i ];
			}
		}
		return newState;
	}

	switch (type) {
		case SET_LIST:
			if ( true === payload ) {
				//create list
				return {
					ID: -1,
					title: '',
					content: '',
					status: '',
					type: ''
				};
			}
			return payload;

		case UPDATE_LIST:
			payload.items = cloneObj( state.items );
			return payload;

		case CREATE_LIST:
			return payload;

		case SET_LIST_ITEMS:
			return getCloned( { items: payload } );

		case ADD_LIST_ITEM + '/failed':
		case ADD_LIST_ITEM:
			if ( payload?.list ) {
				return getCloned( payload.list );
			}
			return state;

		case UPDATE_LIST_ITEM:
			const newItemReplacement = payload.item ? payload.item : false;
			newStateItems = getCloned().items.map(
				(it) => {
					return ( it.post_id === payload.post_id ) ? newItemReplacement || Object.assign({}, it, payload) : {...it};
				}
			);
			return getCloned( { items: newStateItems } );

		case REMOVE_LIST_ITEM:
			if ( ! state.items || 1 === state.items.length || false === payload ) {
				return state;
			}
			newState      = getCloned();
			newStateItems = newState.items.filter( (it) => it.post_id !== payload );
			if ( typeSupport( state.type, 'sortable' ) ) {
				const position0 = parseInt( state.items[0].position, 10 );

				newStateItems.forEach(
					( item, idx) => {
						newStateItems[idx].position = position0 + idx;
					}
				);
			}
			newState.count = newState.count - 1;
			if ( typeSupport( state.type, 'vote' ) ) {
				const removedItem = state.items.find( x => x.post_id == payload );
				if (removedItem) {
					newState.vote_counter = newState.vote_counter - removedItem.votes;
				}
			}
			return { ...newState , ...{ items: newStateItems } };

		/*case MOVE_LIST_ITEM_PREV:
			return moveOutOfPage( payload );

		case MOVE_LIST_ITEM_NEXT:
			return moveOutOfPage( payload );*/

		case MOVE_LIST_ITEM:
			const position0 = parseInt( state.items[0].position, 10 );

			newStateItems  = getCloned().items.slice();
			const movingIt = getCloned().items[payload.oldIndex];
			newStateItems.splice( payload.oldIndex, 1 );
			newStateItems.splice( payload.newIndex, 0, movingIt );

			if ( isNaN( position0 ) ) {
				alert( "positions error!" );
				return state;
			}

			newStateItems.forEach(
				( item, idx ) => {
					newStateItems[idx].position = position0 + idx;
				}
			);

			return getCloned( { items: newStateItems } );

		default:
			return state;
	}
};
