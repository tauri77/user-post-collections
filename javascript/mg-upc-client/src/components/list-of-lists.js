import { h, Fragment } from 'preact';
import ListOfListItem from "./list-of-lists-item";
import Skeleton from "./skeleton";
import Pagination from "./pagination";
import {setListOfList, setListPage} from "../store/actions";
import {useContext} from "preact/hooks";
import {AppContext} from "../contexts/app-context";

function ListOfList(props) {

	const { state, dispatch } = useContext( AppContext );

	return (<>
	<ul className="mg-upc-dg-list-of-lists-fake mg-upc-dg-on-loading">
			{ [0,1,2].map( ( item ) => {
				return (<li className="mg-upc-dg-item-list" >
					<div>
						<Skeleton width={"1.5em"} height={"1.5em"} />
					</div>
					<div className="mg-upc-dg-item-title">
						<Skeleton />
					</div>
					<div className="mg-upc-dg-item-count">
						<Skeleton />
					</div>
				</li>);
			} ) }
		</ul>
		<ul className="mg-upc-dg-list-of-lists">
		{ props.lists && props.lists.map( ( list ) => {
			return (<ListOfListItem
						list={list}
						onClick={ () => props.onSelect(list) }
						onRemove={ props.onRemove }
						key={ list.ID }
			/>);
		} ) }
	</ul>
	{( state.totalPages > 1) &&
	(<Pagination
		totalPages={state.totalPages}
		page={state.page}
		onPreview={props.loadPreview}
		onNext={props.loadNext}
	></Pagination>)}
	</>);
}

export default ListOfList;
