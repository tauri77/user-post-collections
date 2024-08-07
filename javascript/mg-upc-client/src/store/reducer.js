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
	SET_TOTAL_PAGE,
	SET_MESSAGE, ADD_LIST_TO_CART
} from "./actionTypes";
import reduceList from './reduceList';
import reduceListOfList from './reduceListOfLists';
import { initialState } from '../contexts/app-context';
import { cloneObj } from "../helpers/functions";
import translate from "../helpers/translate";

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

	const getCloned = ( override = null ) => {
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

	const setLoadingCount = ( state, operator = 0, endStatus ='succeeded' ) => {
		if ( operator !== 0 ) {
			state.loadingCount = state.loadingCount + operator;
		}
		if ( state.loadingCount < 1 ) {
			state.loadingCount = 0;
			state.status       = endStatus;
		} else {
			state.status = 'loading';
		}
		return state;
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

		case SET_MESSAGE:
			if ( false === payload ) {
				return getCloned( {message: false, errorCode: false} );
			}
			return getCloned( {message: payload} );

		case ADD_LIST_TO_CART:
			const $carted_state = getCloned();
			if ( payload.msg ) {
				$carted_state.message = payload.msg;
			}
			if ( payload.err ) {
				$carted_state.error = payload.err;
			}
			return $carted_state;

		case SET_EDITING:
			return getCloned( {editing: payload} );

		case SET_ADDING_POST:
			newState            = getCloned();
			newState.addingPost = payload;
			if ( payload ) {
				newState.title = translate( "Add to..." );
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
				newState 		= setLoadingCount( newState, -1, 'failed' );
				newState.error  = payload.message;
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
			newState 			= setLoadingCount( getCloned(), 1 );
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

		case REMOVE_LIST_ITEM + '/loading':
			newState = setLoadingCount( getCloned(), 1 );
			if ( typeof payload === 'object' && payload.list_id ) {
				newState.list = { ID : payload.list_id };
			}
			return newState;

		case SET_LIST_OF_LIST + '/loading':
		case SET_LIST_ITEMS + '/loading':
		case UPDATE_LIST_ITEM + '/loading':
		case ADD_LIST_ITEM + '/loading':
		case MOVE_LIST_ITEM_NEXT + '/loading':
		case MOVE_LIST_ITEM_PREV + '/loading':
		case UPDATE_LIST + '/loading':
		case CREATE_LIST + '/loading':
		case ADD_LIST_TO_CART + '/loading':
			return setLoadingCount( getCloned(), 1 );

		case ADD_LIST_ITEM + '/succeeded':
			newState            = setLoadingCount( getCloned(), -1 );
			newState.addingPost = false;
			newState.status     = 'succeeded'
			newState.error      = false;
			newState.errorCode  = false;
			newState.title      = newState.list ? newState.list.title : initialState.title;
			return newState;

		case ADD_LIST_TO_CART + '/succeeded':
			return setLoadingCount( getCloned( { errorCode: false } ), -1 );

		case SET_LIST + '/succeeded':
			var errorClear = { error: false, errorCode: false };
			if ( state.list === false ) {
				errorClear = {};
			}
			return setLoadingCount( getCloned( errorClear ), -1 );
		case SET_LIST_OF_LIST + '/succeeded':
		case SET_LIST_ITEMS + '/succeeded':
		case UPDATE_LIST_ITEM + '/succeeded':
		case REMOVE_LIST_ITEM + '/succeeded':
		case MOVE_LIST_ITEM + '/succeeded':
		case MOVE_LIST_ITEM_NEXT + '/succeeded':
		case MOVE_LIST_ITEM_PREV + '/succeeded':
		case UPDATE_LIST + '/succeeded':
		case CREATE_LIST + '/succeeded':
			return setLoadingCount( getCloned( { error: false, errorCode: false } ), -1 );

		case CREATE_LIST + '/failed':
			newState = setLoadingCount( getCloned(), -1 , 'failed' );
			if ( action.error && action.error.message ) {
				newState.error = action.error.message;
			}
			return newState;

		case ADD_LIST_ITEM + '/failed':
			newState            = setLoadingCount( getCloned(), -1 );
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
		case ADD_LIST_TO_CART + '/failed':
			return setLoadingCount( setFailed( action ), -1 );
	}
	if ( newState !== false ) {
		return newState;
	}
	return state;
}
