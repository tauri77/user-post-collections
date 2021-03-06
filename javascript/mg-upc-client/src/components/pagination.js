import { h, Fragment } from 'preact';
import translate from "../helpers/translate";


function Pagination( props ) {
	return (
		<div className={'mg-upc-dg-pagination-div'}>
			<button
				className={ (props.page === 1) ? 'mg-upc-dg-hidden mg-upc-dg-pagination' : 'mg-upc-dg-pagination' }
				ref={props.prevRef}
				disabled={props.page === 1}
				aria-label={ translate( 'Previous page' ) }
				title={ translate( 'Previous page' ) }
				onClick={props.onPreview}>
				<span className={"mg-upc-icon upc-font-arrow_left"}></span>
			</button>
			<span
				className={ (props.totalPages > 1) ? 'mg-upc-dg-pagination-current' : 'mg-upc-dg-hidden mg-upc-dg-pagination-current' }>
							{props.page}
					</span>
			<button
				className={ (props.page >= props.totalPages) ? 'mg-upc-dg-hidden mg-upc-dg-pagination' : 'mg-upc-dg-pagination' }
				ref={props.nextRef}
				disabled={props.page >= props.totalPages}
				aria-label={ translate( 'Next page' ) }
				title={ translate( 'Next page' ) }
				onClick={props.onNext}>
				<span className={"mg-upc-icon upc-font-arrow_right"}></span>
			</button>
		</div>
	);
}

export default Pagination;
