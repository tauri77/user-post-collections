import '../css/styles.scss';

import { h, render, Fragment } from 'preact';
import {useEffect, useState, useContext, useRef, useMemo} from "preact/hooks";
import ListOfList from "./components/list-of-lists";
import List from "./components/list";
import ListItemAdding from "./components/list-item-adding";
import ListEdit from "./components/list-edit";
import Skeleton from "./components/skeleton";
import ShareLink from "./components/share-link";
import {
	setListOfList,
	setAddingPost,
	setEditing,
	removeList,
	setList,
	createList,
	updateList,
	loadListItems,
	removeItem,
	updateItem,
	moveItem,
	moveItemNextPage,
	moveItemPrevPage,
	addItem,
	resetState,
	setError
} from "./store/actions";
import { ContextProvider, AppContext } from './contexts/app-context';
import loadScript from "./helpers/load-script";

//import { A11yDialog } from 'react-a11y-dialog';
//reducing 8kb..
import { A11yDialog } from './components/react-ally-dialog';

import mgUpcApiClient from "./apiClient";

import {getMgUpcConfig, getNotAlwaysExists, getSortableUrl, listIsEditable, listSupport} from "./helpers/functions";

import "./polls";
import "./products";
import Pagination from "./components/pagination";

//Load dinamically on require..
//import Sortable from 'sortablejs/modular/sortable.core.esm.js';


function isEditable(list) {
	return list.author === getMgUpcConfig().user_id;
}

function App() {

	const { state, dispatch } = useContext( AppContext );

	const [ sharing, setSharing ] = useState( false );

	const typesForCreate = useMemo( () => {
		return getNotAlwaysExists( state.addingPost );
	}, [ state.addingPost ] );

	const dialog = useRef( false );

	const nextRef = useRef( false );
	const prevRef = useRef( false );

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
			setSharing( false );
		},
		[ actualView ]
	);

	const classNames = {
		container: 'mg-upc-dg-container',
		overlay: 'mg-upc-dg-overlay',
		dialog: 'mg-upc-dg-content' + ( state.errorCode ? ' mg-upc-err-' + state.errorCode : '' ),
		title: 'mg-upc-dg-title',
		closeButton: 'mg-upc-dg-close'
	};

	useEffect(
		() => {
			const list = state.list;
			let s1     = false;
			let s2     = false;
			if ( list && listSupport( list, 'sortable' ) ) {
				const run = () => {
					if ( state.page < state.totalPages ) {
						s1 = Sortable.create(
						nextRef.current,
						{
							group: 'shared',
							onAdd: ( evt ) => {
								dispatch( moveItemNextPage( evt.oldIndex ) );
							}
						}
						);
					}
					if ( state.page > 1 ) {
						s2 = Sortable.create(
						prevRef.current,
						{
							group: 'shared',
							onAdd: ( evt ) => {
								dispatch( moveItemPrevPage( evt.oldIndex ) );
							}
						}
						);
					}
				};
				if ( typeof Sortable !== 'undefined' ) {
					run();
				} else {
					loadScript( getSortableUrl() ).then(
					() => {
						run();
						}
					);
				}
			}
			return () => {
                s1 && s1.destroy();
                s2 && s2.destroy();
			};
		},
		[ state.list, state.page, state.totalPages ]
	);

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
		if ( ! state.list ) {
			dispatch( setListOfList( { page: newPage } ) );
		} else {
			dispatch( loadListItems( { page: newPage } ) );
		}
	}

	function handleRemoveItem(list, item) {
		dispatch( removeItem( item.post_id ) );
	}

	function handleItemUpdateDescription(list, item, description) {
		dispatch( updateItem( item.post_id, {description} ) );
	}

	function handleRemoveList(list) {
		dispatch( removeList( list.ID ) );
	}

	const handleMoveItem = function (evt) {
		dispatch( moveItem( evt.oldIndex, state.list, evt.newIndex ) );
	}

	function handleNewList(e) {
		dispatch( setEditing( true ) );
		dispatch( setList( true ) );
		dialog.current.show();
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

	function onSave(data) {
		if (
			-1 !== state.list.ID &&
			data.title === state.list.title &&
			data.content === state.list.content &&
			data.status === state.list.status
		) {
			//no change
			return;
		}

		if ( -1 === state.list.ID ) {
			//create new list
			const save   = {};
			save.title   = data.title;
			save.content = data.content;
			save.type    = data.type;
			save.status  = data.status;
			if ( state.addingPost?.post_id ) {
				save.adding = state.addingPost.post_id;
			}
			dispatch( createList( save ) );
		} else {
			const save = { id: state.list.ID };
			if ( data.status !== state.list.status ) {
				save.status = data.status;
			}
			if ( data.title !== state.list.title ) {
				save.title = data.title;
			}
			if ( data.content !== state.list.content ) {
				save.content = data.content;
			}
			dispatch( updateList( save ) );
		}
	}

	function handleEditCancel() {
		dispatch( setEditing( false ) );
		if ( -1 === state.list.ID ) {
			dispatch( setList( false ) );
			showMy();
		}
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
								<span className={"mg-upc-icon upc-font-add"}></span><span>Create List</span>
							</button>) }
						</div>
						<ListOfList
							lists={state.listOfList}
							onSelect={handleSelectList}
							onRemove={ state.addingPost ? false : handleRemoveList }
						/>
					</>
				) }
				{ state.list && (<>
					{ state.editing && (<ListEdit
						list={state.list}
						addingPost={state.addingPost}
						onSave={onSave}
						onCancel={ handleEditCancel }
					></ListEdit>) }
					{ ! state.editing && (<>
						<div className={"mg-upc-dg-top-action"}>
							{ listIsEditable( state.list ) && (
								<button className={"mg-upg-edit"} onClick={ () => dispatch( setEditing( true ) ) }>
									<span className={"mg-upc-icon upc-font-edit"}></span><span>Edit</span>
								</button>
							)}
							{ state.list.link && (
								<button className={"mg-upg-share"} onClick={ () => setSharing( ! sharing ) }>
									<span className={"mg-upc-icon upc-font-share"}></span><span>Share</span>
								</button>
							)}
						</div>
						{ sharing && state.list.link && (
							<ShareLink link={state.list.link} title={state.list.title} />
						) }
						{ state.list.content && (
							<p className={"mg-upc-dg-list-desc"} dangerouslySetInnerHTML={ { __html: state.list.content } }></p>
						) }
						<Skeleton count={3}/>
						<List
							list={state.list}
							items={state.list?.items || []}
							onMove={handleMoveItem}
							onRemove={handleRemoveItem}
							onSaveItemDescription={handleItemUpdateDescription}
							editable={isEditable( state.list )}/>
					</>) }
				</>) }
				{(( ! state.editing || ! state.list) && state.totalPages > 1) &&
				(<Pagination
					totalPages={state.totalPages}
					page={state.page}
					onPreview={loadPreview}
					onNext={loadNext}
					prevRef={prevRef}
					nextRef={nextRef}
				></Pagination>)}
			</div>
		</div>
	</A11yDialog>);
}

render( ( <ContextProvider><App/> </ContextProvider> ), document.querySelector( 'body' ) );


if ( location.hash === '#my-lists' ) {
	location.hash = '';
}
window.addEventListener(
	'hashchange',
	function() {
		if ( location.hash === '#my-lists' ) {
			window.showMyLists();
			location.hash = '';
		}
	},
	false
);
window.mgUpcApiClient = mgUpcApiClient; //public api for thirty party plugins/themes
