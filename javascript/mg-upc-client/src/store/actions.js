import {
	SET_ERROR,
	SET_MESSAGE,
	RESET_STATE,
	SET_EDITING,
	MOVE_LIST_ITEM,
	MOVE_LIST_ITEM_NEXT,
	MOVE_LIST_ITEM_PREV,
	REMOVE_LIST_ITEM,
	UPDATE_LIST_ITEM,
	SET_LIST,
	REMOVE_LIST,
	UPDATE_LIST,
	CREATE_LIST,
	SET_LIST_ITEMS,
	SET_LIST_OF_LIST,
	SET_PAGE,
	SET_TOTAL_PAGE,
	SET_LIST_PAGE,
	SET_LIST_TOTAL_PAGE,
	SET_ADDING_POST,
	ADD_LIST_ITEM,
	SET_MODE,
	ADD_LIST_TO_CART,
} from "./actionTypes";
import apiClient from "../apiClient";
import {createAsyncThunk} from "../contexts/app-context";
import {addListToCart} from "../helpers/functions";

export const resetState = () => ({
	type: RESET_STATE, payload: null
});

export const setMode = ( mode ) => ({
	type: SET_MODE, payload: mode
});

export const setError = ( error ) => ({
	type: SET_ERROR, payload: error
});

export const setMessage = ( msg ) => ({
	type: SET_MESSAGE, payload: msg
});

export const setAddingPost = ( post ) => ({
	type: SET_ADDING_POST, payload: post
});

export const setEditing = ( editing ) => ({
	type: SET_EDITING, payload: editing
});

export const cartList = createAsyncThunk(
	ADD_LIST_TO_CART,
	async function (data, thunkAPI) {
		return await addListToCart( data );
	}
);

export const setListOfList = createAsyncThunk(
	SET_LIST_OF_LIST,
	async function ( args, thunkAPI) {
		const addingPostID = args?.addingPost;
		if ( null === args ) {
			args = {};
		}
		if ( ! args.addingPost ) {
			args.adding = '';
			delete args.adding;
		} else {
			args.adding = args.addingPost;
		}
		return await apiClient.my( args ).then( ( response ) => {
			return listOfListResponse( response, thunkAPI, addingPostID );
		} );
	}
);

export const setListOfListDiscover = createAsyncThunk(
	SET_LIST_OF_LIST,
	async function ( args, thunkAPI) {
		if ( null === args ) {
			args = {};
		}
		return await apiClient.discover( args ).then( ( response ) => {
			return listOfListResponse( response, thunkAPI, false );
		} );
	}
);

function listOfListResponse( response, thunkAPI, addingPostID ) {
	if ( response.headers.get( "x-wp-page" ) ) {
		thunkAPI.dispatch( setPage( parseInt( response.headers.get( "x-wp-page" ), 10 ) ) );
		thunkAPI.dispatch( setTotalPages( parseInt( response.headers.get( "X-WP-TotalPages" ), 10 ) ) );
	}
	if ( addingPostID && response.headers.get( "X-WP-Post-Type" ) ) {
		const newAddingPost = {
			post_id: addingPostID,
		};

		const mapHeaders = {
			"X-WP-Post-Type": "type",
			"X-WP-Post-Title": "title",
			"X-WP-Post-Image": "image"
		};
		for ( const header in mapHeaders ) {
			const info = response.headers.get( header );
			if ( info ) {
				newAddingPost[ mapHeaders[ header ] ] = decodeURIComponent( info );
			}
		}
		thunkAPI.dispatch( setAddingPost( newAddingPost ) );
	}
	return response.data;
}

export const removeList = createAsyncThunk(
	REMOVE_LIST,
	async function (list_id, thunkAPI) {
		return await apiClient.delete( list_id ).then( ( response ) => {
			if ( thunkAPI.getState().listOfList.length === 1 ) {
				const page  = thunkAPI.getState().page;
				const total = thunkAPI.getState().totalPages;
				if ( page < total ) {
					thunkAPI.dispatch( setListOfList( { page: page } ) );
				} else if ( page > 1 && page === total ) {
					thunkAPI.dispatch( setListOfList( { page: page - 1 } ) );
				} else {
					return list_id;
				}
				return false;
			}
			return list_id;
		} );
	}
);

export const setPage = (page) => ({
	type: SET_PAGE, payload: page
});

export const setTotalPages = (totalPages) => ({
	type: SET_TOTAL_PAGE, payload: totalPages
});

export const setListPage = (page) => ({
	type: SET_LIST_PAGE, payload: page
});

export const setListTotalPages = (totalPages) => ({
	type: SET_LIST_TOTAL_PAGE, payload: totalPages
});

export const setList = createAsyncThunk(
	SET_LIST,
	async function (list, thunkAPI) {
		if ( false === list || true === list ) {
			//Reset or new list
			return list;
		}
		return await apiClient.get( typeof list === 'object' ? list.ID : list ).then( ( response ) => {
			updateListPageState( response, thunkAPI.dispatch );
			return response.data;
		} );
	}
);

export const updateList = createAsyncThunk(
	UPDATE_LIST,
	async function (data, thunkAPI) {
		return await apiClient.update( data ).then( ( response ) => {
			thunkAPI.dispatch( setEditing( false ) );
			updateListPageState( response, thunkAPI.dispatch );
			return response.data;
		} );
	}
);

export const createList = createAsyncThunk(
	CREATE_LIST,
	async function (data, thunkAPI) {
		if ( null === data ) {
			data = {};
		}
		if ( data.adding && data.adding !== thunkAPI.getState().addingPost ) {
			thunkAPI.dispatch( setAddingPost( { id: data.addingPost } ) );
		}
		return await apiClient.create( data ).then( ( response ) => {
			thunkAPI.dispatch( setEditing( false ) );
			updateListPageState( response, thunkAPI.dispatch );
			return response.data;
		} );
	}
);

export const loadListItems = createAsyncThunk(
	SET_LIST_ITEMS,
	async function (args, thunkAPI) {
		return await apiClient.items( thunkAPI.getState().list.ID, args ).then( ( response ) => {
			updateListPageState( response, thunkAPI.dispatch );
			return response.data;
		} );
	}
);

export const removeItem = createAsyncThunk(
	REMOVE_LIST_ITEM,
	async function (post_id, thunkAPI) {
		const state   = thunkAPI.getState();
		var list_id = thunkAPI.extra.length > 0 ? thunkAPI.extra[0] : state.list.ID;
		const context = thunkAPI.extra.length > 1 ? thunkAPI.extra[1] : 'view';
		return await apiClient.quit(
			list_id,
			post_id,
			{ context: context }
		).then(
			( response ) => {
				if ( response.data && response.data.list_id ) {
					list_id = list_id; //For named lists
				}
				if ( ! state.list || ! state.list.ID ) {
					thunkAPI.dispatch( setList( { ID: list_id } ) );
				} else if ( 1 === state.list.items.length ) {
					thunkAPI.dispatch( loadListItems( { page: page } ) );
					if ( state.list && context === 'view' ) {
						const page  = state.listPage;
						const total = state.listTotalPages;
						if ( page < total ) {
							thunkAPI.dispatch( loadListItems( { page: page } ) );
						} else if ( page === total ) {
							thunkAPI.dispatch( loadListItems( { page: Math.max( 1, page - 1 ) } ) );
						}
					}
					return false;
				}
				return post_id;
			}
		);
	}
);

export const addItem = createAsyncThunk(
	ADD_LIST_ITEM,
	async function (list_id, thunkAPI) {
		let item = thunkAPI.extra[0];
		let ret  = false;
		try {
			await apiClient.add(
				list_id,
				item,
				{ context: thunkAPI.extra.length > 1 ? thunkAPI.extra[1] : 'view' }
			).then( ( response ) => {
				ret = response.data;
			} );
		} catch ( reason ) {
			const data = reason?.response?.data;
			ret        = thunkAPI.rejectWithValue( data );
		}

		return ret;
	}
);

export const updateItem = createAsyncThunk(
	UPDATE_LIST_ITEM,
	async function (post_id, thunkAPI) {
		const data = thunkAPI.extra[0];
		return await apiClient.updateItem( thunkAPI.getState().list.ID, post_id, data ).then( ( response ) => {
			return { ...data, post_id: post_id, item: response?.data?.item };
		} );
	}
);

export const moveItem = createAsyncThunk(
	MOVE_LIST_ITEM,
	async function (oldIndex, thunkAPI) {
		const list     =  thunkAPI.extra[0]; //thunkAPI.getState().list;
		const newIndex = thunkAPI.extra[1];

		const movingIt  = list.items[oldIndex];
		const newNumber = movingIt.position - oldIndex + newIndex;

		return await apiClient.move( list.ID, movingIt.post_id, newNumber ).then( ( response ) => {
			return {
				oldIndex,
				newIndex
			};
		} );
	}
);


export const moveItemNextPage = createAsyncThunk(
	MOVE_LIST_ITEM_NEXT,
	async function (oldIndex, thunkAPI) {
		const list = thunkAPI.getState().list;

		const lastNumber = parseInt( list.items[list.items.length - 1].position, 10 );
		if ( isNaN( lastNumber ) ) {
			alert( "positions error!" );
			throw "positions error!";
		}
		const itemToNext = list.items[oldIndex];

		await apiClient.move( list.ID, itemToNext.post_id, lastNumber + 1 );
		await thunkAPI.dispatch( loadListItems( { page: thunkAPI.getState().listPage } ) );
		return oldIndex;
	}
);

export const moveItemPrevPage = createAsyncThunk(
	MOVE_LIST_ITEM_PREV,
	async function (oldIndex, thunkAPI) {
		const list = thunkAPI.getState().list;

		const positionOne = parseInt( list.items[0].position, 10 );
		if ( isNaN( positionOne ) ) {
			alert( "positions error!" );
			throw "positions error!";
		}
		const itemToPrev = list.items[oldIndex];

		await apiClient.move( list.ID, itemToPrev.post_id, positionOne - 1 );
		await thunkAPI.dispatch( loadListItems( { page: thunkAPI.getState().listPage } ) );
		return oldIndex;
	}
);


function updateListPageState(response, dispatch) {
	if ( ! response.data.items_page && response.headers.get( "x-wp-page" ) ) {
		dispatch( setListPage( parseInt( response.headers.get( "x-wp-page" ), 10 ) ) );
		dispatch( setListTotalPages( parseInt( response.headers.get( "X-WP-TotalPages" ), 10 ) ) );
	} else if ( response.data.items_page ) {
		dispatch( setListPage( parseInt( response.data.items_page['X-WP-Page'], 10 ) ) );
		dispatch( setListTotalPages( parseInt( response.data.items_page["X-WP-TotalPages"], 10 ) ) );
	}
}
