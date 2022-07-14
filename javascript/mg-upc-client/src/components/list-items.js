import { h, Fragment } from 'preact';
import ListItem from "./list-item";
import {useEffect, useRef} from "preact/hooks";
import loadScript from "../helpers/load-script";
import Skeleton from "./skeleton";
import {getSortableUrl, listSupport} from "../helpers/functions";
import translate from "../helpers/translate";

function ListItems( props) {
	const ulRef = useRef(null);

	const moveRef = useRef(
		(evt) => {
			props.onMove( evt );
		}
	);
	useEffect(() => {
		moveRef.current = props.onMove;
	});

	useEffect(() => {
		let s = false;
		if ( listSupport( props.list, 'sortable' ) ) {
			const run = () => {
				s = Sortable.create( ulRef.current, {
					handle: '.mg-upc-dg-item-handle',
					group: 'shared',
					animation: 150,
					onUpdate: function ( evt ) {
						moveRef.current( evt );
					}
				} );
			};
			if ( typeof Sortable !== 'undefined' ) {
				run();
			} else {
				loadScript( getSortableUrl() ).then(()=> {
					run();
				});
			}
		}
		return () => {
			s && s.destroy();
		};
	});

	return (<>
		<ul className="mg-upc-dg-list-fake mg-upc-dg-on-loading">
			{ [0,1,2].map( ( item ) => {
				return (<li className="mg-upc-dg-item" >
					{  listSupport( props.list, 'sortable' ) && (<>
						<span className="mg-upc-dg-item-handle-skeleton"> &nbsp;<Skeleton width={"1.5em"} /> &nbsp;</span>
						<span className="mg-upc-dg-item-number-skeleton">&nbsp; <Skeleton width={"1em"} /> &nbsp;</span>
					</>) }
					{ listSupport( props.list, 'vote' ) && (<>
						<span className="mg-upc-dg-item-number-skeleton">&nbsp; <Skeleton width={"1em"} /> &nbsp;</span>
					</>) }
					<div className="mg-upc-dg-item-skeleton-image" >
						<Skeleton width={"5em"} height={"5em"}/>
					</div>
					<div className="mg-upc-dg-item-data">
						<Skeleton count={2}/>
					</div>
				</li>);
			} ) }
		</ul>
		<ul ref={ulRef} className="mg-upc-dg-list">
		{ props?.items?.length === 0 && (
			<span>There are no items in this list</span>
		) }
		{ props?.items?.length > 0 && props.items?.map && (props.items.map( ( item ) => {
			return (<ListItem
							list={props.list}
							item={item}
							editable={props.editable}
							onRemove={() => props.onRemove(props.list, item)}
							onSaveItemDescription={ (description) => props.onSaveItemDescription(props.list, item, description)}
							onSaveItemQuantity={ (quantity) => props.onSaveItemQuantity(props.list, item, quantity)}
							key={ item.ID + ':' + item.post_id }
			/>);
		} ) ) }
	</ul>
		{ listSupport( props.list, 'vote' ) && (
			<span className={"mg-upc-dg-total-votes"}> { translate( "Total votes:" ) } <span> {props.list.vote_counter}</span></span>
		)}
	</>);
}

export default ListItems;
