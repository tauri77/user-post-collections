import '../css/styles.scss';

import { h, render, Fragment } from 'preact';
import {useEffect, useState, useContext, useRef, useMemo} from "preact/hooks";
import { ContextProvider, AppContext } from './contexts/app-context';

//import { A11yDialog } from 'react-a11y-dialog';
//reducing 8kb..
import { A11yDialog } from './components/react-ally-dialog';

import UserApp from "./components/user-app";

import mgUpcApiClient from "./apiClient";

import {
	addListToCart, getMgUpcConfig, getNotAlwaysExists
} from "./helpers/functions";

import "./polls";
import "./products";
import {SimpleContainer} from "./components/simple-container";
import translate from "./helpers/translate";

//Load dinamically on require..
//import Sortable from 'sortablejs/modular/sortable.core.esm.js';

function App(props) {

	const { state, dispatch } = useContext( AppContext );

	const dialog = useRef( false );
	const userAppRef = useRef( null );
	const [canBack, setCanBack] = useState(false);

	const classNames = {
		container: 'mg-upc-dg-container',
		overlay: 'mg-upc-dg-overlay',
		dialog: 'mg-upc-dg-content' + ( state.errorCode ? ' mg-upc-err-' + state.errorCode : '' ),
		title: 'mg-upc-dg-title',
		closeButton: 'mg-upc-dg-close'
	};

	useEffect(
		() => {
			window.showMyLists = function () {
				userAppRef.current.showMy();
				dialog.current.show();
			};
			window.mgUpcShowList = function ( list_id, title= '' ) {
				userAppRef.current.showList( list_id, title );
				dialog.current.show();
			};
			window.addItemToList = function ( post_id, list_id = false, after = 'view' ) {
				userAppRef.current.addItemToList( post_id, list_id, after );
				dialog.current.show();
			};
			window.removeItemFromList = function( post_id, list_id, after = 'view' ) {
				userAppRef.current.removeItemFromList( post_id, list_id, after );
				dialog.current.show();
			};
			window.mgUpcAddListToCart = addListToCart;
		},
		[ dialog.current, dispatch ]
	);

	useEffect(() => {
		if (userAppRef.current) {
			setCanBack(userAppRef.current.canBack);
		}
	}, [userAppRef.current?.canBack]);

	function dialogRefSet(instance) {
		dialog.current = instance;
	}

	function userAppRefSet(instance) {
		userAppRef.current = instance;
		setCanBack(instance.canBack);
	}

	return (<A11yDialog
		id='mg-upc-dg-dialog'
		dialogRef={dialogRefSet}
		title={state.title === null ? ( typeof props.title === 'string' ? props.title : translate( 'My Lists' )) : state.title}
		classNames={classNames}
		onBack={canBack ? userAppRef.current.back : false}
	>
		<UserApp refSet={userAppRefSet}/>
	</A11yDialog>);
}

render( ( <ContextProvider><App/> </ContextProvider> ), document.querySelector( 'body' ) );



function UpcApp(props) {

	const { state, dispatch } = useContext( AppContext );

	const userAppRef = useRef( null );
	const [canBack, setCanBack] = useState(false);

	const classNames = {
		container: 'mg-upc-app-container' + ( state.errorCode ? ' mg-upc-err-' + state.errorCode : '' ),
		title: 'mg-upc-app-title',
	};

	useEffect(
		() => {
			userAppRef.current.showMy();
		},
		[props]
	);

	useEffect(() => {
		if (userAppRef.current) {
			setCanBack(userAppRef.current.canBack);
		}
	}, [userAppRef.current?.canBack]);

	function userAppRefSet(instance) {
		userAppRef.current = instance;
		setCanBack(instance.canBack);
	}

	return (<SimpleContainer
		id='mg-upc-dg-dialog'
		title={state.title === null ? ( typeof props.title === 'string' ? props.title : translate( 'My Lists' )) : state.title}
		classNames={classNames}
		onBack={canBack ? userAppRef.current.back : false}
	>
		<UserApp refSet={userAppRefSet}/>
	</SimpleContainer>);
}

window.showUpcApp = function(container) {
	render(
		( <ContextProvider><UpcApp title=""/> </ContextProvider> ),
		container
	);
};

document.addEventListener('DOMContentLoaded', function() {
	if (document.getElementById('upc-my-lists-widget')) {
		window.showUpcApp( document.getElementById('upc-my-lists-widget'));
	}
});

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


//******************************
//****    Themes Helpers    ****
//******************************/

window.mgUpcListeners = function() {
	jQuery( '.mg-upc-post-add' ).on(
		'click',
		function () {
			if ( jQuery( this ).data( 'post-id' ) > 0 ) {
				window.addItemToList(
					jQuery( this ).data( 'post-id' ),
					( jQuery( this ).data( 'upc-list' ) + '' ).length > 0 ? jQuery( this ).data( 'upc-list' ) : false
				);
			}
			return false;
		}
	);

	jQuery( '.mg-upc-post-remove' ).on(
		'click',
		function () {
			if ( jQuery( this ).data( 'post-id' ) > 0 && typeof jQuery( this ).data( 'upc-list' ) !== 'undefined' ) {
				window.removeItemFromList(
					jQuery( this ).data( 'post-id' ),
					( jQuery( this ).data( 'upc-list' ) + '' ).length > 0 ? jQuery( this ).data( 'upc-list' ) : false
				);
			}
			return false;
		}
	);

	jQuery( '.mg-upc-show-list' ).on(
		'click',
		function () {
			if ( typeof jQuery( this ).data( 'upc-list' ) !== 'undefined' ) {
				window.mgUpcShowList(
					jQuery( this ).data( 'upc-list' ),
					( jQuery( this ).data( 'upc-title' ) + '' ).length > 0 ? jQuery( this ).data( 'upc-title' ) : false
				);
			}
			return false;
		}
	);
}

window.mgUpcListeners();