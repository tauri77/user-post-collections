import { h } from 'preact';

function ListOfListItem(props) {
	return (<li
		className="mg-upc-dg-item-list"
		onClick={props.onClick}
		onKeyPress={(e) => { e.keyCode === 13 && props.onClick(e) } }
		tabIndex="0">
				<i className={"mg-upc-icon mg-upc-dg-item-type mg-upc-dg-item-type-" + props.list.type}></i>
				<div className="mg-upc-dg-item-title">{props.list.title}</div>
				<span className="mg-upc-dg-item-count">{props.list.count}</span>
				<span className="mg-upc-dg-item-actions">
					{ props.onRemove && (
						<button onClick={ (e) => { e.stopPropagation(); props.onRemove(props.list); } }>
							<span className={"mg-upc-icon upc-font-trash"}></span>
						</button>
					)}
				</span>
			</li>);
}

export default ListOfListItem;
