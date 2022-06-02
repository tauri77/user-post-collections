import { h, Fragment } from 'preact';
import {useEffect, useRef, useState} from "preact/hooks";
import {noItemImage} from "../helpers/functions";

function ListItemAdding(props) {

	const [ editingDesc, setEditingDescription ] = useState( false )
	const [ description, setDescription ]        = useState( '' );

	const inputDescRef = useRef({} );

	useEffect( () => {
		setDescription( props.item.description );
	}, [ props.item ] );

	useEffect( () => {
		if ( editingDesc ) {
			inputDescRef.current.focus();
		}
	}, [ editingDesc ] );

	function handleDesc(event) {
		setDescription( event.target.value );
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

	return (<>
		<span><br />Adding item:</span>
		<div className="mg-upc-dg-item mg-upc-dg-item-adding" data-post_id={props.item.post_id} >
			<span className={"mg-upc-icon upc-font-add"}></span><span> </span>
			<img className="mg-upc-dg-item-image" src={props.item.image ? props.item.image : noItemImage} />
			<div className="mg-upc-dg-item-data">
				<a href={props.item?.link}>{props.item?.title}</a>
				{ ! editingDesc && (<p>{ props.item?.description }</p>) }
				{ ! editingDesc && (<button onClick={ switchToEditing }>
					{ description === '' && (<span>Add Comment</span>) }
					{ description !== '' && (<span>Edit Comment</span>) }
				</button>) }
				<input
					ref={ inputDescRef }
					className={ editingDesc ? 'mg-upc-dg-btn-item-desc' : 'mg-upc-dg-dn' }
					type="text"
					value={ description }
					onChange={ handleDesc }
					maxLength={400}
				/>
				{ editingDesc && (<button className={"mg-upc-dg-btn-item-desc-cancel"} onClick={ onCancel }>
					<span className={"mg-upc-icon upc-font-close"}></span><span>Cancel</span>
				</button>) }
				{ editingDesc && (<button className={"mg-upc-dg-btn-item-desc-save"} onClick={ handleSave }>
					<span className={"mg-upc-icon upc-font-save"}></span><span>Save</span>
				</button>) }
			</div>
	</div>
	<span>Select where the item will be added:</span>
	</>);
}

export default ListItemAdding;
