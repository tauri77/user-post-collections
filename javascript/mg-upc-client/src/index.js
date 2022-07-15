import '../css/styles.scss';

import { h, render, Fragment } from 'preact';
import {useEffect, useState, useContext, useRef, useMemo} from "preact/hooks";
import ListOfList from "./components/list-of-lists";
import ListItemAdding from "./components/list-item-adding";
import {
	setListOfList,
	setAddingPost,
	setEditing,
	removeList,
	setList,
	addItem,
	resetState,
	setError,
	setListPage,
	setPage, setMessage
} from "./store/actions";
import { ContextProvider, AppContext } from './contexts/app-context';
import translate from "./helpers/translate";

//import { A11yDialog } from 'react-a11y-dialog';
//reducing 8kb..
import { A11yDialog } from './components/react-ally-dialog';

import mgUpcApiClient from "./apiClient";

import {
	addListToCart, getMgUpcConfig, getNotAlwaysExists
} from "./helpers/functions";

import "./polls";
import "./products";
import List from "./components/list";

//Load dinamically on require..
//import Sortable from 'sortablejs/modular/sortable.core.esm.js';


function isEditable(list) {
	return parseInt( list.author, 10 ) === parseInt( getMgUpcConfig().user_id, 10 );
}

function App() {

	const { state, dispatch } = useContext( AppContext );

	const typesForCreate = useMemo( () => {
		return getNotAlwaysExists( state.addingPost );
	}, [ state.addingPost ] );

	const dialog = useRef( false );

	let actualView = 'listOfList';
	if ( state.addingPost ) {
		actualView = ! state.editing ? 'adding' : 'addingToNew';
	} else if ( state.editing ) {
		actualView = state.list?.ID !== -1 ? 'edit' : 'new';
	} else {
		actualView = state.list ? 'list' : 'listOfList';
	}

	const classNames = {
		container: 'mg-upc-dg-container',
		overlay: 'mg-upc-dg-overlay',
		dialog: 'mg-upc-dg-content' + ( state.errorCode ? ' mg-upc-err-' + state.errorCode : '' ),
		title: 'mg-upc-dg-title',
		closeButton: 'mg-upc-dg-close'
	};

	useEffect(
		() => {
			window.showMyLists   = function () {
				showMy();
			};
			window.addItemToList = function ( post_id, list_id = false ) {
				dispatch( resetState() );
				if ( ! list_id ) {
					showForAdd( post_id );
				}
			};
			window.mgUpcAddListToCart = addListToCart;
		},
		[ dialog.current, dispatch ]
	);

	function dialogRefSet(instance) {
		dialog.current = instance;
	}

	const showMy = () => {
		dispatch( resetState() );
		dispatch( setListOfList() );
		dialog.current.show();
	}

	const showForAdd = ( post_id ) => {
		dispatch( setAddingPost( { post_id: post_id } ) );
		dispatch( setListOfList( { addingPost: post_id } ) );
		dialog.current.show();
	}

	function handleSelectList(list) {
		dispatch( setEditing( false ) );
		if ( state.addingPost ) {
			dispatch( addItem( list.ID, state.addingPost ) );
			return;
		}
		dispatch( setList( list ) );
		dialog.current.show();
	}

	function handleNewList(e) {
		dispatch( setEditing( true ) );
		dispatch( setList( true ) );
		dialog.current.show();
	}

	function loadNext() {
		loadPage( state.page + 1 );
	}

	function loadPreview() {
		loadPage( state.page - 1 );
	}

	function loadPage(newPage) {
		if ( newPage < 1 || newPage > state.totalPages || state.status === 'loading' ) {
			return;
		}
		dispatch( setPage( newPage ) );
	}

	function onBack() {
		switch ( actualView ) {
			case 'list':
				showMy();
				break;

			case 'new':
				dispatch( setList( false ) );
				dispatch( setEditing( false ) );
				showMy();
				break;

			case 'edit':
				dispatch( setEditing( false ) );
				break;

			case 'addingToNew':
				dispatch( setList( false ) );
				dispatch( setEditing( false ) );
				dispatch( setListOfList( { addingPost: state.addingPost.post_id } ) );
				break;

			default:
				showMy();
		}
	}

	function handleRemoveList(list) {
		dispatch( removeList( list.ID ) );
	}

	function handleAddingEdit(description) {
		dispatch( setAddingPost( {...state.addingPost, description: description} ) );
	}

	const comeBackJack = ( actualView === 'list' || actualView === 'new' || actualView === 'edit' || actualView === 'addingToNew' );

	return (<A11yDialog
		id='mg-upc-dg-dialog'
		dialogRef={dialogRefSet}
		title={state.title}
		classNames={classNames}
		onBack={ comeBackJack ? onBack : false }
	>
		<div className={ 'mg-upc-dg-content-wrapper mg-upc-dg-status-' + state.status + ' mg-upc-dg-view-' + actualView }>
			<div className="mg-upc-dg-wait"></div>
			{ state.message && (<div className="mg-upc-dg-msg">
				{state.message}
				<a href="#"
				   className={"mg-upc-dg-alert-close"}
				   aria-label={"Hide alert"}
				   onClick={ (evt) => { evt.preventDefault(); dispatch( setMessage( null ) ); } }
				><span className="mg-upc-icon upc-font-close"></span></a>
			</div>) }
			{ state.error && (<div className="mg-upc-dg-error">
				{state.error}
				<a href="#"
				   className={"mg-upc-dg-alert-close"}
				   aria-label={"Hide alert"}
				   onClick={ (evt) => { evt.preventDefault(); dispatch( setError( null ) ); } }
				><span className="mg-upc-icon upc-font-close"></span></a>
			</div>) }
			<div className="mg-upc-dg-body">
				{ !state.error && state.addingPost && (<ListItemAdding
					item={state.addingPost}
					onSaveItemDescription={handleAddingEdit}
				/>)}
				{ (actualView === 'listOfList' || actualView === 'adding') && (
					<>
						<div className={"mg-upc-dg-top-action"}>
							{ ( typesForCreate.length > 0 ) && (<button
								className="mg-list-new"
								onClick={handleNewList}>
								<span className={"mg-upc-icon upc-font-add"}></span><span>{ translate( 'Create List' ) }</span>
							</button>) }
						</div>
						<ListOfList
							lists={state.listOfList}
							onSelect={handleSelectList}
							onRemove={ state.addingPost ? false : handleRemoveList }
							loadPreview={loadPreview}
							loadNext={loadNext}
						/>
					</>
				) }
				{ state.list && (
					<List editable={isEditable( state.list )} />
				) }
			</div>
		</div>
	</A11yDialog>);
}

render( ( <ContextProvider><App/> </ContextProvider> ), document.querySelector( 'body' ) );

function clearHash() {
	if ("replaceState" in history) {
		history.replaceState( '', document.title, location.pathname );
		history.go( -1 );
	} else {
		location.hash = '';
	}
}

if ( location.hash === '#my-lists' ) {
	clearHash();
}
window.addEventListener(
	'hashchange',
	function() {
		if ( location.hash === '#my-lists' ) {
			window.showMyLists();
			clearHash();
		}
	},
	false
);
window.mgUpcApiClient = mgUpcApiClient; //public api for thirty party plugins/themes
