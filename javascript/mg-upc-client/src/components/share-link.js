import { h, Fragment } from 'preact';
import {useEffect, useRef, useState} from "preact/hooks";
import ListItems from "./list-items";
import shareButton from "../helpers/share-button";
import translate from "../helpers/translate";
import {getMgUpcConfig} from "../helpers/functions";


function ShareLink(props) {

	const linkRef = useRef( null );
	const copyRef = useRef( null );

	const defaultText = translate( 'Copy' );

	const [ buttonText,  setButtonText ] = useState( defaultText );

	useEffect( () => {
		let c = null;
		if ( buttonText !== defaultText ) {
			c = setTimeout( () => {
				setButtonText( defaultText );
				clearTimeout(c);
			}, 2000 );
		}
	}, [ buttonText ] );

	function handleInputClick() {
		linkRef.current.setSelectionRange( 0, linkRef.current.value.length );
	}

	function handleCopyLink(btn) {
		if ( copyInput( linkRef.current ) ) {
			setButtonText( translate( 'Copied!' ) );
		} else {
			setButtonText( 'Error!' );
		}
	}

	function copyInput(target) {
		target.focus();
		target.setSelectionRange( 0, target.value.length );
		if ( document.execCommand ) {
			return document.execCommand( "copy" );
		}
		return false;
	}

	const link  = encodeURIComponent(props.link);
	const title = encodeURIComponent(props.title);

	let buttons = [
		{
			name: 'Twitter',
			url: "https://twitter.com/share?url=" + link + "&text=" + title,
		},
		{
			name: 'Facebook',
			url: "https://www.facebook.com/sharer/sharer.php?u=" + link + "&quote=" + title,
		},
		{
			name: 'Pinterest',
			url: "https://pinterest.com/pin/create/button/?url=" + link + "&description=" + title,
		},
		{
			name: 'Whatsapp',
			url: "whatsapp://send?text=" + link,
		},
		{
			name: 'Telegram',
			url: "https://t.me/share/url?url=" + link + "&text=" + title,
		},
		{
			name: 'LiNE',
			url: "https://social-plugins.line.me/lineit/share?url=" + link + "&text=" + title,
		},
		{
			slug: 'email',
			name: translate( 'Email' ),
			url: 'mailto:?subject=' + title + '&body=' + link,
		}
	];

	if ( typeof getMgUpcConfig().shareButtons != 'undefined' ) {
		buttons = buttons.filter( ( config ) => getMgUpcConfig().shareButtons.includes( config.slug || config.name.toLowerCase() ) );
	}

	return (<div className={"mg-upc-dg-share-link"}>
		<input ref={linkRef} value={props.link} onClick={handleInputClick} readOnly/>
		<button ref={copyRef} onClick={handleCopyLink}>
			<span className={"mg-upc-icon upc-font-copy"}></span><span>{buttonText}</span>
		</button>
		{ buttons.map(
			function (configButton){
				return shareButton( configButton );
			}
		) }
	</div>);
}


export default ShareLink;
