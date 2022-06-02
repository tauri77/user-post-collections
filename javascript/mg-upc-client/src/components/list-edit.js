import { h, Fragment } from 'preact';
import {useEffect, useMemo, useState} from "preact/hooks";
import {getMgUpcConfig, getUpcTypeConfig, typeSupport} from "../helpers/functions";

function ListEdit( props ) {
	const [ title, setTitle ]     = useState( '' );
	const [ content, setContent ] = useState( '' );
	const [ type, setType ]       = useState( '' );
	const [ status, setStatus ]   = useState( '' );

	const optionsType = useMemo(
		() => {
			const arr   = [];
			const types = getMgUpcConfig().types;
			//New list
			for ( const type in types ) {
				if ( types.hasOwnProperty( type ) ) {
					if ( ! typeSupport( type, 'always_exists' ) ) {
						arr.push( types[type] );
					}
				}
			}
			return arr;
		}
	);

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

	function handleType(event) {
		setType( event.target.value );
	}

	function handleContent(event) {
		setContent( event.target.value );
	}

	function handleStatus(event) {
		setStatus( event.target.value );
	}

	return (<div className="mg-list-edit">
		{ props.list.ID === -1 && (<>
			<label htmlFor={`type-${props.list.ID}`}>
				List Type
			</label>
			<select
				id={`type-${props.list.ID}`}
				value={ type }
				onChange={ handleType }
			>
				{ optionsType.map( (option, optionIndex) => {
					return (<option key={option.name} value={option.name}>{option.label}</option>);
				})}
			</select>
		</>) }
		{ typeSupport( type, 'editable_title') && (<>
			<label htmlFor={`title-${props.list.ID}`}>
				Title
			</label>
			<input
				id={`title-${props.list.ID}`}
				type="text"
				value={ title }
				onChange={ handleTitle }
				maxLength={100}
			/>
		</>) }
		{ typeSupport( type, 'editable_content' ) && (<>
			<label htmlFor={`content-${props.list.ID}`}>
				Description
			</label>
			<textarea
				id={`content-${props.list.ID}`}
				value={ content }
				onChange={ handleContent }
				maxLength={500}
			></textarea>
			<span className={"mg-upc-dg-list-desc-edit-count"}><i>{content.length}</i>/500</span>
		</>) }
		{ ! getUpcTypeConfig( type ) && (
			<span>Unknown Type...</span>
		)}
		{ getUpcTypeConfig( type )?.available_statuses &&
		  getUpcTypeConfig( type ).available_statuses.length > 1 && (<>
			<label htmlFor={`status-${props.list.ID}`}>
				Status
			</label>
			<select
				id={`status-${props.list.ID}`}
				value={ status }
				onChange={ handleStatus }
			>
				{ getUpcTypeConfig(type).available_statuses.map( (option, optionIndex) => {
					return (<option value={option}>{option}</option>);
				})}
			</select>
		</>) }
		{ getUpcTypeConfig( type ) && (<div className={"mg-upc-dg-edit-actions"}>
			<button onClick={ () => props.onCancel() }>
				<span className={"mg-upc-icon upc-font-close"}></span><span>Cancel</span>
			</button>
			<button onClick={ () => props.onSave( { title, content, type, status } ) }>
				<span className={"mg-upc-icon upc-font-save"}></span><span>Save</span>
			</button>
		</div>)}
	</div>);
}

export default ListEdit;
