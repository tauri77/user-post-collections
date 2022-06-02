import { h, Fragment } from 'preact';
import ListOfListItem from "./list-of-lists-item";
import Skeleton from "./skeleton";

function ListOfList(props) {

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
	</ul></>);
}

export default ListOfList;
