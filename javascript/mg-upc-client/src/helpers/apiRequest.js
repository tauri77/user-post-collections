
import { getMgUpcConfig } from './functions';

async function apiRequest( type, path = '', data = {}, basePath = 'mg-upc/v1/lists') {
	if ( typeof getMgUpcConfig().nonce === 'undefined' ) {
		const formData = new FormData();
		formData.append( 'action', 'mg_upc_user' );
		const uConfig      = {
			method: 'POST',
			credentials: 'same-origin',
			referrerPolicy: 'no-referrer',
			body: formData
		};
		const userResponse = await fetch( getMgUpcConfig().ajaxUrl, uConfig );
		const user_data    = await userResponse.json();
		if ( user_data.nonce ) {
			getMgUpcConfig().nonce = user_data.nonce;
		}
		if ( user_data.user_id ) {
			getMgUpcConfig().user_id = user_data.user_id;
		}
	}
	const config = {
		method: type, // *GET, POST, PUT, DELETE, etc.
		//mode: 'cors', // no-cors, *cors, same-origin
		//cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
		credentials: 'same-origin', // include, *same-origin, omit
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': getMgUpcConfig().nonce
			// 'Content-Type': 'application/x-www-form-urlencoded',
		},
		//redirect: 'follow', // manual, *follow, error
		referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
	};
	if ( 'GET' !== type && data) {
		config.body = JSON.stringify( data );
	}
	const response = await fetch( getMgUpcConfig().root + basePath + path, config );
	if (response.headers.get( "x-wp-nonce" )) {
		getMgUpcConfig().nonce = response.headers.get( "x-wp-nonce" );
	}
	const json = await response.json(); // parses JSON response into native JavaScript objects
	return {data: json, headers: response.headers, status: response.status};
}

export default apiRequest;
