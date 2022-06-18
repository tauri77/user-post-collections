import {SET_LIST, REMOVE_LIST, SET_LIST_OF_LIST, ADD_LIST_ITEM} from "./actionTypes";
import { cloneObj } from "../helpers/functions";

export default function reduceListOfList (state, action) {
	const { type, payload } = action;

	switch (type) {
		case SET_LIST_OF_LIST:
			return payload;

		case ADD_LIST_ITEM:
		case SET_LIST:
			return false;

		case REMOVE_LIST:
			if ( false === payload ) {
				return state;
			}
			return cloneObj( state.filter( ( it ) => it.ID != payload ) );

		default:
			return state;
	}

};
