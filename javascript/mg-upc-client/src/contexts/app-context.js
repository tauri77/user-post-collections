import { h } from 'preact';
import { useReducer } from "preact/hooks";
import { reducer } from '../store/reducer';
import { createContext } from "preact";
import translate from "../helpers/translate";



const isPromise = obj => {
	return (
		! ! obj &&
		(typeof obj === "object" || typeof obj === "function") &&
		typeof obj.then === "function"
	);
}

const isAsyncThunk = obj => {
	return (
		! ! obj &&
		typeof obj === "object" &&
		obj.asyncThunk === true
	);
}

export const createAsyncThunk = ( type, payloadCreator ) => {
	return function ( arg= null, ...extra ) {
		//requestId = nanoid();
		return { asyncThunk: true, payload: payloadCreator, type, arg, extra };
	};
};


class MgUpcRejectWithValue extends Error {
	constructor(message, value) {
		super( message );
		this.name  = "MgUpcRejectWithValue";
		this.value = value;
	}
}

const asyncDispatchs = ( dispatch, getState ) => {
	return action => {
		let thePromise;
		if ( isAsyncThunk( action ) ) {
			let thunkAPI = {
				dispatch: asyncDispatchs( dispatch, getState ),
				getState,
				extra: action.extra,
				rejectWithValue: ( value ) => { return new MgUpcRejectWithValue( action.type + ': rejectWithValue', value ); }
			};
			dispatch( { type: action.type + '/loading', payload: action.arg } );
			thePromise = action.payload( action.arg, thunkAPI );
		} else if ( isPromise( action.payload ) ) {
			dispatch( { type: action.type + '/loading' } );
			thePromise = action.payload;
		} else {
			dispatch( action );
			return;
		}
		thePromise.then(
			v => {
				if ( v instanceof MgUpcRejectWithValue ) {
					dispatch( { type: action.type + '/failed', payload: v.value } );
					return;
				}
				dispatch( { type: action.type, payload: v } );
				dispatch( { type: action.type + '/succeeded' } );
			}
		).catch(
			reason => {
				if ( reason instanceof MgUpcRejectWithValue ) {
					dispatch( { type: action.type + '/failed', payload: reason.value } );
					return;
				}
				dispatch( { type: action.type + '/failed', error: reason } );
			}
		);
	};
}

export const  initialState = {
	list: false,
	listOfList: false,
	addingPost: null,
	status: 'idle', //'idle' | 'loading' | 'succeeded' | 'failed'
	error: null,
	errorCode: null,
	editing: false,
	title: translate( 'My Lists' ),
	actualAction: 'init', //MY, MY_ADD, LIST, EDIT, CREATE
	page: 1,
	totalPages: 1,
	listPage: 1,
	listTotalPages: 1,
	mode: 'my'
};

export const  AppContext = createContext( {} );

export const  ContextProvider = props => {

	const [state, dispatch] = useReducer( reducer, initialState );

	return (
		<AppContext.Provider value={{ state, dispatch : asyncDispatchs( dispatch, () => state ) }} >
			{props.children}
		</AppContext.Provider>
	);
};
