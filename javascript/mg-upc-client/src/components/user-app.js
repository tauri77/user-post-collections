import {useContext, useMemo, useRef, useEffect} from "preact/hooks";
import {AppContext} from "../contexts/app-context";
import {getMgUpcConfig, getNotAlwaysExists} from "../helpers/functions";
import {
    addItem,
    removeItem, removeList,
    resetState,
    setAddingPost,
    setEditing, setError,
    setList,
    setListOfList, setMessage,
    setPage
} from "../store/actions";
import ListItemAdding from "./list-item-adding";
import translate from "../helpers/translate";
import ListOfList from "./list-of-lists";
import List from "./list";
import { h, Fragment } from 'preact';

function isEditable(list) {
    return parseInt( list.author, 10 ) === parseInt( getMgUpcConfig().user_id, 10 );
}

const UserApp = props => {

    const {state, dispatch} = useContext(AppContext);
    const instance = useRef(null);
    const {refSet} = props;

    let actualView = 'listOfList';
    if (state.addingPost) {
        actualView = !state.editing ? 'adding' : 'addingToNew';
    } else if (state.editing) {
        actualView = state.list?.ID !== -1 ? 'edit' : 'new';
    } else {
        actualView = state.list ? 'list' : 'listOfList';
    }
    const canBack = ( actualView === 'list' || actualView === 'new' || actualView === 'edit' || actualView === 'addingToNew' );

    const typesForCreate = useMemo( () => {
        return getNotAlwaysExists( state.addingPost );
    }, [ state.addingPost ] );

    useEffect(() => {
            instance.current = {};
            instance.current.showMy = () => {
                    dispatch( resetState() );
                    dispatch( setListOfList() );
            };
            instance.current.showList = (list_id, title = '') => {
                    dispatch( resetState() );
                    dispatch( setList( { ID: list_id, title: ( title ? title : '') } ) );
            };
            instance.current.addItemToList = (post_id, list_id = false, after = 'view') => {
                    dispatch( resetState() );
                    if ( ! list_id ) {
                        showForAdd( post_id );
                    } else {
                        dispatch( addItem( list_id, post_id, after ) );
                    }
            };
            instance.current.removeItemFromList = (post_id, list_id, after = 'view') => {
                    dispatch( resetState() );
                    dispatch( removeItem( post_id, list_id, after ) );
            };
            instance.current.back = () => {
                    back();
            };
            instance.current.canBack = canBack;
            refSet( instance.current );
    }, [instance, refSet, canBack]);

    useEffect(() => {
       if(instance && instance.current) {
           instance.current.canBack = canBack;
       }
    }, [canBack, instance]);

    const showMy = () => {
        dispatch( resetState() );
        dispatch( setListOfList() );
    };

    const showForAdd = ( post_id ) => {
        dispatch( setAddingPost( { post_id: post_id } ) );
        dispatch( setListOfList( { addingPost: post_id } ) );
    };

    function handleSelectList(list) {
        dispatch( setEditing( false ) );
        if ( state.addingPost ) {
            dispatch( addItem( list.ID, state.addingPost, 'view' ) );
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

    function back() {
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
                if ( state.list.ID === true ) {
                    dispatch( setList( false ) );
                    dispatch( setEditing( false ) );
                    showMy();
                } else {
                    dispatch( setEditing( false ) );
                }
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

    return (<>
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
                            { ( typesForCreate.length > 0 ) && ! state.error && (<button
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
    </>);
};

export default UserApp;