import { h, Fragment } from 'preact';
import {useEffect, useRef, useState} from "preact/hooks";
import {listSupport, noItemImage} from "../helpers/functions";
import translate from "../helpers/translate";

function ListItem(props) {

	const [ editingDesc, setEditingDescription ] = useState( false )
	const [ description, setDescription ]        = useState( '' );
	const [ quantity, setQuantity ]              = useState( props.item?.quantity );

	const inputDescRef = useRef({} );

	useEffect( () => {
		setDescription( props.item.description );
	}, [ props.item ] );

	useEffect( () => {
		if ( editingDesc ) {
			inputDescRef.current.focus();
		}
	}, [ editingDesc ] );

	const timerUpdate = useRef( false );
	useEffect(
		() => {
			if ( props.item.quantity === quantity ) {
				return;
			}
			clearTimeout( timerUpdate.current );
			timerUpdate.current = setTimeout( function () {
				props.onSaveItemQuantity( quantity );
			}, 600 );
		},
		[ quantity ]
	);

	function handleDesc(event) {
		setDescription( event.target.value );
	}

	function handleQuantity() {
		setQuantity( event.target.value );
	}

	const switchToEditing = () => {
		setEditingDescription( true );
	};

	const onCancel = () => {
		setEditingDescription( false );
		setDescription( props.item.description );
	};

	const handleSave = () => {
		setEditingDescription( false );
		props.onSaveItemDescription( description );
	};

	const handleKeyDown = (event) => {
		if (event.key === 'Enter') {
			setEditingDescription( false );
			props.onSaveItemDescription( description );
		}
	}

	const getPercent = () => {

		const total = parseInt( props.list.vote_counter, 10 );

		if ( listSupport( props.list, 'vote' ) && total > 0 ) {
			return Math.round( parseInt( props.item.votes, 10 ) * 100 / total ) + "%";
		}

		return 0 + "%";
	}

	return (<li className="mg-upc-dg-item" data-post_id={props.item.post_id} >
			{ listSupport( props.list, 'sortable' ) && (<>
				<span className="mg-upc-dg-item-handle" aria-draggable>::</span>
				<span className="mg-upc-dg-item-number">{props.item.position}</span>
			</>) }
			{ listSupport( props.list, 'vote' ) && (<>
				<span className="mg-upc-dg-item-number">{getPercent()}</span>
			</>) }
			<a href={props.item.link}>
				<img className="mg-upc-dg-item-image" src={props.item.image ? props.item.image : noItemImage} />
			</a>
			<div className="mg-upc-dg-item-data">
				<a href={props.item.link}>{props.item.title}</a>
				{ props.item.price_html && (
					<span className={"mg-upc-dg-price"} dangerouslySetInnerHTML={ { __html: props.item.price_html } }></span>
				)}
				{ props.item.stock_html && (
					<span className={"mg-upc-dg-stock"} dangerouslySetInnerHTML={ { __html: props.item.stock_html } }></span>
				)}
				{ props.editable && ! editingDesc && (
					<p>{ props.item.description }</p>
				) }
				{ props.editable && ! editingDesc && listSupport( props.list, 'editable_item_description' )  && (
					<button onClick={ switchToEditing }>
					<span className={"mg-upc-icon upc-font-edit"}></span>
					{ description === '' && (<span>{ translate( 'Add Comment' ) }</span>) }
					{ description !== '' && (<span>{ translate( 'Edit Comment' ) }</span>) }
					</button>
				) }
				<input
					ref={ inputDescRef }
					className={ editingDesc ? 'mg-upc-dg-btn-item-desc' : 'mg-upc-dg-dn' }
					type="text"
					value={ description }
					onChange={ handleDesc }
					onKeyDown={ handleKeyDown }
					maxLength={400}
				/>
				{ props.editable && editingDesc && (
					<button className={"mg-upc-dg-btn-item-desc-cancel"} onClick={ onCancel }>
						<span className={"mg-upc-icon upc-font-close"}></span><span>{ translate( 'Cancel' ) }</span>
					</button>
				) }
				{ props.editable && editingDesc && (
					<button className={"mg-upc-dg-btn-item-desc-save"} onClick={ handleSave }>
						<span className={"mg-upc-icon upc-font-save"}></span><span>{ translate( 'Save' ) }</span>
					</button>
				) }
			</div>
			{ props.editable && listSupport( props.list, 'quantity' )  && (
				<div
					className={ 'mg-upc-dg-quantity' }>
					<small>{ translate( 'Quantity' ) }</small>
					<input
						aria-label={ translate( 'Quantity' ) }
						type="number"
						value={ quantity }
						onChange={ handleQuantity }
					/>
				</div>
			) }
			{ props.editable &&  ! editingDesc && (<div>
				<button aria-label={"Remove item"} onClick={props.onRemove}><span className={"mg-upc-icon upc-font-trash"}></span></button>
			</div>) }
	</li>);
}

export default ListItem;
