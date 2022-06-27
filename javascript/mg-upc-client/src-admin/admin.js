import '../css/styles.scss';

import { h, render, Fragment } from 'preact';
import {useEffect, useState, useContext, useRef, useMemo} from "preact/hooks";
import ListOfList from "../src/components/list-of-lists";
import ListItemAdding from "../src/components/list-item-adding";
import {
	setListOfList,
	setAddingPost,
	setEditing,
	removeList,
	setList,
	addItem,
	resetState,
	setError,
	setListOfListDiscover,
	setMode,
	setListPage, setPage
} from "../src/store/actions";
import {ContextProvider, AppContext, initialState} from '../src/contexts/app-context';
import translate from "../src/helpers/translate";

//import { A11yDialog } from 'react-a11y-dialog';
//reducing 8kb..
import { A11yDialog } from '../src/components/react-ally-dialog';

import {
	getMgUpcConfig,
	getNotAlwaysExists,
	getStatusLabel,
	getUpcTypeConfig,
	getUpcStatuses,
	statusShowInList,
	getUpcTypes, prevent
} from "../src/helpers/functions";

import List from "../src/components/list";
import {getHashVar} from "../src/helpers/functions-admin";

//Load dinamically on require..
//import Sortable from 'sortablejs/modular/sortable.core.esm.js';


function isEditable(list) {
	return true;
}

function App() {

	const { state, dispatch }   = useContext( AppContext );
	const [ type, setType ]     = useState( 'any' );
	const [ status, setStatus ] = useState( 'any' );
	const [ author, setAuthor ] = useState( '' );
	const [ search, setSearch ] = useState( null );

	const timerSearch = useRef( false );

	const typesForCreate = useMemo( () => {
		return getNotAlwaysExists( state.addingPost );
	}, [ state.addingPost ] );

	const optionsType = useMemo( () => {
		return getUpcTypes();
	}, [] );

	let actualView = 'listOfList';
	if ( state.addingPost ) {
		actualView = ! state.editing ? 'adding' : 'addingToNew';
	} else if ( state.editing ) {
		actualView = state.list?.ID !== -1 ? 'edit' : 'new';
	} else {
		actualView = state.list ? 'list' : 'listOfList';
	}

	useEffect(
		() => {
			window.showMainLists = function () {
				dispatch( resetState() );
				showMain();
			};
			window.addItemToList = function ( post_id, list_id = false ) {
				dispatch( resetState() );
				if ( ! list_id ) {
					showForAdd( post_id );
				}
			};
		},
		[ dispatch ]
	);

	const showMain = () => {
		const args = {};
		if ( type ) {
			args.types = type;
		}
		if ( status ) {
			args.status = status;
		}
		if ( search ) {
			args.search = search;
		}
		if ( author ) {
			args.author = author;
		}
		if ( state.page > 1 ) {
			args.page = state.page;
		}
		dispatch( setListOfListDiscover( args ) );
	}

	useEffect(
		() => {
			initialState.title = '';
			dispatch( setMode( 'admin' ) );
			const hashHandle = function(e) {
				const auth = parseInt( getHashVar( 'author' ), 10 );
				if ( auth > 0 && auth !== author ) {
					if ( e ) {
						prevent( e );
					}
					dispatch( setPage( 1 ) );
					setAuthor( auth );
					location.hash = '';
				}
			};
			hashHandle( 0 );
			window.addEventListener(
				'hashchange',
				hashHandle,
				false
			);
			return () => {
				window.removeEventListener( 'hashchange', hashHandle );
			}
		},
		[]
	);

	useEffect(
		() => {
			if ( ! state.list ) {
				showMain();
			}
		},
		[ type, status, author, state.page ]
	);

	useEffect(
		() => {
			if ( null == search ) {
				return;
			}
			clearTimeout( timerSearch.current );
			timerSearch.current = setTimeout( function () {
				showMain()
			}, 300 );
		},
		[ search ]
	);

	function resetPage() {
		if ( state.page > 1 ) {
			dispatch( setPage( 1 ) );
		}
	}

	function handleType(type) {
		resetPage();
		setType( type );
	}

	function handleStatus(value) {
		resetPage();
		setStatus( value );
	}

	function handleSearch(event) {
		resetPage();
		setSearch( event.target.value );
	}

	function handleAuthor(event) {
		resetPage();
		setAuthor( event.target.value );
	}

	const showForAdd = ( post_id ) => {
		dispatch( setAddingPost( { post_id: post_id } ) );
		dispatch( setListOfList( { addingPost: post_id } ) );
	}

	function handleSelectList(list) {
		dispatch( setEditing( false ) );
		if ( state.addingPost ) {
			dispatch( addItem( list.ID, state.addingPost ) );
			return;
		}
		dispatch( setList( list ) );
	}

	function handleNewList(e) {
		dispatch( setEditing( true ) );
		dispatch( setList( true ) );
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
				dispatch( setList( false ) );
				showMain();
				break;

			case 'new':
				dispatch( setList( false ) );
				dispatch( setEditing( false ) );
				showMain();
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
				showMain();
		}
	}

	function handleRemoveList(list) {
		dispatch( removeList( list.ID ) );
	}

	function handleAddingEdit(description) {
		dispatch( setAddingPost( {...state.addingPost, description: description} ) );
	}

	const comeBackJack = ( actualView === 'list' || actualView === 'new' || actualView === 'edit' || actualView === 'addingToNew' );
	const title = (
		<h2 key='title'>
			{ comeBackJack && (
				<a aria-label={"Back"} className={"mg-upc-dg-back"} href="#" onClick={ (e) => {e.preventDefault(); onBack(e)} }>&larr;</a>
			)} {state.title}
		</h2>
	);
	return (<>
		{title}
		<div className={ 'mg-upc-dg-content-wrapper mg-upc-dg-status-' + state.status + ' mg-upc-dg-view-' + actualView }>
			<div className="mg-upc-dg-wait"></div>
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
						<ul className={"mg-upc-dg-filter"}>
							<li className={"mg-upc-dg-filter-label"}><strong>{ translate( "Types" ) }</strong></li>
							<li
								className={'any' == type ? "mg-upc-dg-item-list-type mg-upc-selected" :"mg-upc-dg-item-list-type"}
								onClick={ () => handleType( 'any' ) }
								onKeyPress={(e) => { e.keyCode === 13 && handleType( 'any' ) } }
								tabIndex="0" >
								<i className={"mg-upc-icon upc-font-close mg-upc-dg-item-type mg-upc-dg-item-type-none"}></i>
								<div className="mg-upc-dg-item-title">
									<strong>All</strong>
								</div>
							</li>
							{ optionsType.map( (option, optionIndex) => {
								return (<li
										className={ option.name == type ? "mg-upc-dg-item-list-type mg-upc-selected" :"mg-upc-dg-item-list-type"}
										key={option.name}
										onClick={ () => handleType( option.name ) }
										onKeyPress={(e) => { e.keyCode === 13 && handleType( option.name ) } }
										tabIndex="0">
										<i className={"mg-upc-icon mg-upc-dg-item-type mg-upc-dg-item-type-" +option.name}></i>
										<div className="mg-upc-dg-item-title">
											<strong>{option.label}</strong>
										</div>
									</li>
								);
							})}
						</ul>
						{ getUpcStatuses() && (<ul className={"mg-upc-dg-filter"}>
							<li className={"mg-upc-dg-filter-label"}><strong>{ translate( "Status" ) }</strong></li>
							<li
								className={'any' == status ? "mg-upc-selected" :""}
								onClick={ () => handleStatus( 'any' ) }
								onKeyPress={(e) => { e.keyCode === 13 && handleStatus( 'any' ) } }
								tabIndex="0" >
								<div className="mg-upc-dg-item-title">
									<strong>All</strong>
								</div>
							</li>
							{ getUpcStatuses().map( (option, optionIndex) => {
								return (<li
									className={ option.name == status ? "mg-upc-selected" : ""}
									key={option.name}
									onClick={ () => handleStatus( option.name ) }
									onKeyPress={(e) => { e.keyCode === 13 && handleStatus( option.name ) } }
									tabIndex="0">{option.label}</li>);
							})}
						</ul>)
						}
						<div className={"mg-upc-dg-df"}>
							<ul className={"mg-upc-dg-filter"}>
								<li className={"mg-upc-dg-filter-label"}><strong>{ translate( "Search" ) }</strong></li>
								<input onChange={handleSearch} value={search}/>
							</ul>
							<ul className={"mg-upc-dg-filter"}>
								<li className={"mg-upc-dg-filter-label"}><strong>{ translate( "Author (ID)" ) }</strong></li>
								<input type="number" onChange={handleAuthor} value={author}/>
							</ul>
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
		</div></>);
}

render( ( <ContextProvider><App/> </ContextProvider> ), document.getElementById( 'mg-upc-admin-app' ) );

setTimeout( window.showMainLists, 1000 );
