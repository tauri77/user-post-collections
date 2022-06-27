import { h, Fragment } from 'preact';
import ListEdit from "./list-edit";
import {getSortableUrl, listIsEditable, listSupport, str_nl2br} from "../helpers/functions";
import {
	createList, loadListItems,
	moveItem,
	moveItemNextPage,
	moveItemPrevPage,
	removeItem,
	resetState,
	setEditing,
	setList,
	setListOfList,
	updateItem,
	updateList
} from "../store/actions";
import translate from "../helpers/translate";
import ShareLink from "./share-link";
import Skeleton from "./skeleton";
import ListItems from "./list-items";
import {useContext, useEffect, useRef, useState} from "preact/hooks";
import {AppContext} from "../contexts/app-context";
import Pagination from "./pagination";
import loadScript from "../helpers/load-script";

function List( props ) {

	const { state, dispatch } = useContext( AppContext );

	const [ sharing, setSharing ] = useState( false );

	const nextRef = useRef( false );
	const prevRef = useRef( false );

	useEffect(
		() => {
			const list = state.list;
			let s1     = false;
			let s2     = false;
			if ( list && listSupport( list, 'sortable' ) ) {
				const run = () => {
					if ( state.listPage < state.listTotalPages ) {
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
					if ( state.listPage > 1 ) {
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
		[ state.list, state.listPage, state.listTotalPages ]
	);

	useEffect(
		() => {
			setSharing( false );
		},
		[ state.editing, state.list, state.addingPost  ]
	);

	function handleItemUpdateDescription(list, item, description) {
		dispatch( updateItem( item.post_id, {description} ) );
	}

	function handleMoveItem(evt) {
		dispatch( moveItem( evt.oldIndex, state.list, evt.newIndex ) );
	}

	function handleRemoveItem(list, item) {
		dispatch( removeItem( item.post_id ) );
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
			dispatch( resetState() );
			dispatch( setListOfList() );
		}
	}

	function loadNext() {
		loadPage( state.listPage + 1 );
	}

	function loadPreview() {
		loadPage( state.listPage - 1 );
	}

	function loadPage(newPage) {
		if ( newPage < 1 || newPage > state.listTotalPages || state.status === 'loading' ) {
			return;
		}
		dispatch( loadListItems( { page: newPage } ) );
	}

	return (<>
		{ state.editing && (<ListEdit
			list={state.list}
			addingPost={state.addingPost}
			onSave={ onSave }
			onCancel={ handleEditCancel }
		></ListEdit>) }
		{ ! state.editing && (<>
			<div className={"mg-upc-dg-top-action"}>
				{ listIsEditable( state.list ) && (
					<button className={"mg-upg-edit"} onClick={ () => dispatch( setEditing( true ) ) }>
						<span className={"mg-upc-icon upc-font-edit"}></span><span>{ translate( 'Edit' ) }</span>
					</button>
				)}
				{ state.list.link && (
					<button className={"mg-upg-share"} onClick={ () => setSharing( ! sharing ) }>
						<span className={"mg-upc-icon upc-font-share"}></span><span>{ translate( 'Share' ) }</span>
					</button>
				)}
			</div>
			{ sharing && state.list.link && (
				<ShareLink link={state.list.link} title={state.list.title} />
			) }
			{ state.list.content && (
				<p className={"mg-upc-dg-list-desc"}
				   dangerouslySetInnerHTML={ { __html: str_nl2br( state.list.content ) } }></p>
			) }
			<Skeleton count={3}/>
			<ListItems
				list={state.list}
				items={state.list?.items || []}
				onMove={handleMoveItem}
				onRemove={handleRemoveItem}
				onSaveItemDescription={handleItemUpdateDescription}
				editable={props.editable}/>
		</>) }
		{(( ! state.editing || ! state.list) && state.listTotalPages > 1) &&
		(<Pagination
			totalPages={state.listTotalPages}
			page={state.listPage}
			onPreview={loadPreview}
			onNext={loadNext}
			prevRef={prevRef}
			nextRef={nextRef}
		></Pagination>)}
	</>);
}

export default List;
