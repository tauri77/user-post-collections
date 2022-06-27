import { h, Fragment } from 'preact';
import translate from "../helpers/translate";
import {useContext} from "preact/hooks";
import {AppContext} from "../contexts/app-context";
import {formatDate, setHashVar} from "../helpers/functions-admin";
import {prevent} from "../helpers/functions";

function ListOfListItem(props) {
	const { state, dispatch } = useContext( AppContext );

	function setAuthor(event) {
		prevent( event );
		setHashVar( 'author', props.list.author );
	}

	return (<li
		className="mg-upc-dg-item-list"
		onClick={props.onClick}
		onKeyPress={(e) => { e.keyCode === 13 && props.onClick(e) } }
		tabIndex="0">
				<i className={"mg-upc-icon mg-upc-dg-item-type mg-upc-dg-item-type-" + props.list.type}></i>
		<div className="mg-upc-dg-item-title">
			<span>{props.list.title}</span>{'my' !== state.mode && (<>
			<span>
				<a href="#" onClick={setAuthor}>{props.list.user_login}</a>
				<span className={"mg-upc-list-dates"}>
					<i> <b>Created:</b> {formatDate(props.list.created)}</i>
					<i> <b>Modified:</b> {formatDate(props.list.modified)}</i>
				</span>
			</span>
		</>)}</div>
				<span className="mg-upc-dg-item-count">{props.list.count}</span>
				<span className="mg-upc-dg-item-actions">
					{ props.onRemove && (
						<button aria-label={ translate( 'Remove List' ) } onClick={ (e) => { e.stopPropagation(); props.onRemove(props.list); } }>
							<span className={"mg-upc-icon upc-font-trash"}></span>
						</button>
					) }
				</span>
			</li>);
}

export default ListOfListItem;
