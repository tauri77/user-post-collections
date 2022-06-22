import { h, Fragment } from 'preact';
import {useEffect, useMemo, useState} from "preact/hooks";
import {
	getNotAlwaysExists,
	getStatusLabel,
	getUpcTypeConfig,
	typeSupport,
	statusShowInList
} from "../helpers/functions";
import translate from "../helpers/translate";

function ListEdit( props ) {
	const [ title, setTitle ]     = useState( '' );
	const [ content, setContent ] = useState( '' );
	const [ type, setType ]       = useState( '' );
	const [ status, setStatus ]   = useState( '' );

	const optionsType = useMemo( () => {
			return getNotAlwaysExists( props.addingPost );
	}, [ props.addingPost ] );

	if ( '' === type && 1 === optionsType.length ) {
		handleType( optionsType[0] );
	}

	useEffect(
		() => {
			setTitle( props.list.title );
			setContent( props.list.content );
			setType( props.list.type );
			setStatus( props.list.status );
		},
		[ props.list ]
	);

	useEffect(
		() => {
			if (
			getUpcTypeConfig( type )?.available_statuses &&
			-1 === getUpcTypeConfig( type ).available_statuses.indexOf( status )
		) {
			setStatus( getUpcTypeConfig( type ).available_statuses[0] );
			}
		},
		[ type ]
	);

	function handleTitle(event) {
		setTitle( event.target.value );
	}

	function handleType(typeOpt) {
		if ( typeOpt.default_title ) {
			setTitle( typeOpt.default_title );
		}
		if ( typeOpt.default_status ) {
			setStatus( typeOpt.default_status );
		}
		setType( typeOpt.name );
	}

	function handleContent(event) {
		setContent( event.target.value );
	}

	function handleStatus(event) {
		setStatus( event.target.value );
	}

	return (<div className="mg-list-edit">
		{ props.list.ID === -1 && type === '' &&   (<>
			<label>
				{ translate( 'Select a list type:' ) }
			</label>
			<ul
				id={`type-${props.list.ID}`}
			>
				{ optionsType.map( (option, optionIndex) => {
					return (<li
						className="mg-upc-dg-item-list-type"
						key={option.name}
						onClick={ () => handleType( option ) }
						onKeyPress={(e) => { e.keyCode === 13 && handleType( option ) } }
						tabIndex="0">
						<i className={"mg-upc-icon mg-upc-dg-item-type mg-upc-dg-item-type-" +option.name}></i>
						<div className="mg-upc-dg-item-title">
							<strong>{option.label}</strong>
							<div className="mg-upc-dg-item-desc">{option.description}</div>
						</div>
					</li>
					);
				})}
			</ul>
		</>) }
		{ type !== '' && typeSupport( type, 'editable_title') && (<>
			<label htmlFor={`title-${props.list.ID}`}>
				{ translate( 'Title' ) }
			</label>
			<input
				id={`title-${props.list.ID}`}
				type="text"
				value={ title }
				onChange={ handleTitle }
				maxLength={100}
			/>
		</>) }
		{ type !== '' && typeSupport( type, 'editable_content' ) && (<>
			<label htmlFor={`content-${props.list.ID}`}>
				{ translate( 'Description' ) }
			</label>
			<textarea
				id={`content-${props.list.ID}`}
				value={ content }
				onChange={ handleContent }
				maxLength={500}
			></textarea>
			<span className={"mg-upc-dg-list-desc-edit-count"}><i>{content?.length}</i>/500</span>
		</>) }
		{ type !== '' && ! getUpcTypeConfig( type ) && (
			<span>{ translate( 'Unknown List Type...' ) }</span>
		)}
		{ type !== '' && getUpcTypeConfig( type )?.available_statuses &&
		  getUpcTypeConfig( type ).available_statuses.length > 1 && (<>
			<label htmlFor={`status-${props.list.ID}`}>
				{ translate( 'Status' ) }
			</label>
			<select
				id={`status-${props.list.ID}`}
				value={ status }
				onChange={ handleStatus }
			>
				{ getUpcTypeConfig(type).available_statuses.map( (option, optionIndex) => {
					if ( statusShowInList( option ) ) {
						return (<option value={option}>{getStatusLabel(option)}</option>);
					}
				})}
			</select>
		</>) }
		{ type !== '' && getUpcTypeConfig( type ) && (<div className={"mg-upc-dg-edit-actions"}>
			<button onClick={ () => props.onSave( { title, content, type, status } ) }>
				<span className={"mg-upc-icon upc-font-save"}></span><span>{ translate( 'Save' ) }</span>
			</button>
			<button onClick={ () => props.onCancel() }>
				<span className={"mg-upc-icon upc-font-close"}></span><span>{ translate( 'Cancel' ) }</span>
			</button>
		</div>)}
	</div>);
}

export default ListEdit;
