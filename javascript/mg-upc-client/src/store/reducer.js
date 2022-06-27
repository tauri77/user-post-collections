import {
	SET_MODE,
	SET_ERROR,
	SET_LIST_OF_LIST,
	SET_LIST,
	SET_ADDING_POST,
	UPDATE_LIST,
	CREATE_LIST,
	UPDATE_LIST_ITEM,
	SET_LIST_TOTAL_PAGE,
	SET_LIST_PAGE,
	SET_LIST_ITEMS,
	REMOVE_LIST_ITEM,
	MOVE_LIST_ITEM,
	MOVE_LIST_ITEM_NEXT,
	MOVE_LIST_ITEM_PREV,
	ADD_LIST_ITEM,
	SET_EDITING,
	RESET_STATE,
	SET_PAGE,
	SET_TOTAL_PAGE
} from "./actionTypes";
import reduceList from './reduceList';
import reduceListOfList from './reduceListOfLists';
import { initialState } from '../contexts/app-context';
import { cloneObj } from "../helpers/functions";

export function reducer (state, action) {
	const { type, payload } = action;

	let newState = false;

	const setFailed = (action) => {

		newState = getCloned( { status: 'failed' } );
		if ( action.error ) {
			newState.error     =  action.error.message ? action.error.message : '';
			newState.errorCode = action.error.code ? action.error.code : '';
		}

		return newState;
	}

	const getCloned = ( override = false) => {
		if ( ! newState ) {
			newState = cloneObj( state );
		}
		if ( override ) {
			for ( const i in override ) {
				if ( override.hasOwnProperty( i ) ) {
					newState[ i ] = override[ i ];
				}
			}
		}

		return newState;
	}

	let list       = reduceList( state.list, action );
	let listOfList = reduceListOfList( state.listOfList, action );
	if ( state.list !== list || listOfList !== state.listOfList) {
		newState = getCloned( { listOfList: listOfList, list: list } );
		if ( ! state.addingPost ) {
			newState.title = newState.list ? newState.list.title : initialState.title;
		}
	}

	switch (type) {
		case SET_MODE:
			return getCloned( {mode: payload} );

		case RESET_STATE:
			return {...initialState, mode: state.mode};

		case SET_ERROR:
			if ( false === payload ) {
				return getCloned( {error: false, errorCode: false} );
			}
			return getCloned( {error: payload} );

		case SET_EDITING:
			return getCloned( {editing: payload} );

		case SET_ADDING_POST:
			newState            = getCloned();
			newState.addingPost = payload;
			if ( payload ) {
				newState.title = "Add to...";
			}
			return newState;

		case CREATE_LIST:
			newState                = getCloned();
			newState.title          = payload.title ? payload.title : initialState.title;
			newState.listTotalPages = 1;
			newState.listPage       = 1;
			newState.addingPost     = false;
			break;

		case ADD_LIST_ITEM:
			newState = getCloned();
			if ( payload?.list ) {
				const list          = payload.list;
				newState.title      = list.title ? list.title : initialState.title;

				const pages = list?.items_page;
				if ( pages ) {
					newState.listTotalPages = pages['X-WP-TotalPages'] ? pages['X-WP-TotalPages'] : 1;
					newState.listPage       = pages['X-WP-Page'] ? pages['X-WP-Page'] : 1;
				}
			}
			if ( payload.message ) {
				// If some message, something was wrong.. Ex: list no support description
				newState.error  = payload.message;
				newState.status = 'failed';
			}
			newState.addingPost = false;
			break;

		case SET_PAGE:
			return getCloned( {page: payload} );

		case SET_TOTAL_PAGE:
			return getCloned( {totalPages: payload} );

		case SET_LIST_PAGE:
			return getCloned( {listPage: payload} );

		case SET_LIST_TOTAL_PAGE:
			return getCloned( {listTotalPages: payload} );

		case SET_LIST + '/loading':
			newState            = getCloned();
			newState.status     = 'loading';
			newState.listOfList = false;
			if ( typeof payload === 'object' ) {
				newState.list = payload;
				if ( payload.title ) {
					newState.title = payload.title;
				}
			} else {
				newState.list = {ID : payload};
			}

			return newState;

		case SET_LIST_OF_LIST + '/loading':
		case SET_LIST_ITEMS + '/loading':
		case UPDATE_LIST_ITEM + '/loading':
		case REMOVE_LIST_ITEM + '/loading':
		case ADD_LIST_ITEM + '/loading':
		case MOVE_LIST_ITEM_NEXT + '/loading':
		case MOVE_LIST_ITEM_PREV + '/loading':
		case UPDATE_LIST + '/loading':
		case CREATE_LIST + '/loading':
			return getCloned( {status: 'loading'} );

		case ADD_LIST_ITEM + '/succeeded':
			newState            = getCloned();
			newState.addingPost = false;
			newState.status     = 'succeeded'
			newState.error      = false;
			newState.errorCode  = false;
			newState.title      = newState.list ? newState.list.title : initialState.title;
			return newState;

		case SET_LIST + '/succeeded':
			if ( state.list === false ) {
				break;
			}
			return getCloned( {status: 'succeeded', error: false, errorCode: false } );
		case SET_LIST_OF_LIST + '/succeeded':
		case SET_LIST_ITEMS + '/succeeded':
		case UPDATE_LIST_ITEM + '/succeeded':
		case REMOVE_LIST_ITEM + '/succeeded':
		case MOVE_LIST_ITEM + '/succeeded':
		case MOVE_LIST_ITEM_NEXT + '/succeeded':
		case MOVE_LIST_ITEM_PREV + '/succeeded':
		case UPDATE_LIST + '/succeeded':
		case CREATE_LIST + '/succeeded':
			return getCloned( {status: 'succeeded', error: false, errorCode: false } );

		case CREATE_LIST + '/failed':
			newState = getCloned( {status: 'failed'} );
			if ( action.error && action.error.message ) {
				newState.error = action.error.message;
			}
			return newState;

		case ADD_LIST_ITEM + '/failed':
			newState            = getCloned();
			newState.addingPost = false;
			newState.title      = newState.list ? newState.list.title : initialState.title;

			if ( payload?.list ) {
				const list          = payload.list;
				newState.title      = list.title ? list.title : initialState.title;

				const pages = list?.items_page;
				if ( pages ) {
					newState.listTotalPages = pages['X-WP-TotalPages'] ? pages['X-WP-TotalPages'] : 1;
					newState.listPage       = pages['X-WP-Page'] ? pages['X-WP-Page'] : 1;
				}
			}
			if ( payload.message ) {
				newState.error  = payload.message;
				newState.status = 'failed';
			}
			return setFailed( action );

		case SET_LIST_OF_LIST + '/failed':
		case SET_LIST_ITEMS + '/failed':
		case UPDATE_LIST_ITEM + '/failed':
		case REMOVE_LIST_ITEM + '/failed':
		case MOVE_LIST_ITEM + '/failed':
		case MOVE_LIST_ITEM_NEXT + '/failed':
		case MOVE_LIST_ITEM_PREV + '/failed':
		case UPDATE_LIST + '/failed':
		case SET_LIST + '/failed':
			return setFailed( action );
	}
	if ( newState !== false ) {
		return newState;
	}
	return state;
}
