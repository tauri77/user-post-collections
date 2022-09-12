
import apiRequest from "./helpers/apiRequest";
import objectToGetParams from "./helpers/objectToGetParams";

class MgApiError extends Error {
	constructor(message, response) {
		super( message );
		this.name     = "MgApiError";
		this.code     = response?.data?.code;
		this.response = response;
	}
}

function checkResponseError(response){
	let status = response?.data?.data?.status;
	if ( ! status && response.status ) {
		status = response.status;
	}
	if (
		status === 400 ||
		status === 401 ||
		status === 403 ||
		status === 404 ||
		status === 409 ||
		status === 500
	) {
		throw new MgApiError( response?.data?.message , response );
	}
}

let apiClient = {
	my: function(args={}) {
		return apiRequest( 'GET', '/My' + objectToGetParams( args ), {} ).then(
			function(response) {
				checkResponseError( response );
				return response;
			}
		);
	},
	discover: function (args) {
		return apiRequest( 'GET', '/' + objectToGetParams( args ), {} ).then(
			function(response) {
				checkResponseError( response );
				return response;
			}
		);
	},
	get: function (id) {
		return apiRequest( 'GET', '/' + id /*+ '?_embed=items,author'*/, {} ).then(
			function(response) {
				checkResponseError( response );
				return response;
			}
		);
	},
	cart: function (id) {
		return apiRequest( 'POST', '/cart', { 'list': id }, 'mg-upc/v1' ).then(
			function(response) {
				checkResponseError( response );
				return response;
			}
		);
	},
	items: function (id, args= {}) {
		return apiRequest( 'GET', '/' + id + '/items' + objectToGetParams( args ), {} ).then(
			function(response) {
				checkResponseError( response );
				return response;
			}
		);
	},
	delete: function (id) {
		return apiRequest( 'DELETE', '/' + id, {} ).then(
			function(response) {
				checkResponseError( response );
				return response;
			}
		);
	},
	create: function (list) {
		return apiRequest( 'POST', '', list ).then(
			function(response) {
				checkResponseError( response );
				return response;
			}
		);
	},
	update: function (list) {
		let id = list.id;
		delete list.id;
		return apiRequest( 'PATCH', '/' + id, list ).then(
			function(response) {
				checkResponseError( response );
				return response;
			}
		);
	},
	add: function (listID, item, args={}) {
		if ( typeof item !== 'object' ) {
			item = {'post_id' : item};
		}
		return apiRequest( 'POST', '/' + listID + '/items' + objectToGetParams( args ), item ).then(
			function(response) {
				checkResponseError( response );
				return response;
			}
		);
	},
	quit: function (listID, itemID, args={}) {
		return apiRequest( 'DELETE', '/' + listID + '/items/' + itemID + objectToGetParams( args ), {} ).then(
			function(response) {
				checkResponseError( response );
				return response;
			}
		);
	},
	updateItem: function (listID, itemID, item) {
		return apiRequest( 'PATCH', '/' + listID + '/items/' + itemID, item ).then(
			function(response) {
				checkResponseError( response );
				return response;
			}
		);
	},
	vote: function (listID, itemID, args={}) {
		return apiRequest( 'POST', '/' + listID + '/items/' + itemID + '/vote', args ).then(
			function(response) {
				checkResponseError( response );
				return response;
			}
		);
	},
	move: function (listID, itemID, position) {
		return apiRequest( 'PATCH', '/' + listID + '/items/' + itemID, { 'position': position } ).then(
			function(response) {
				checkResponseError( response );
				return response;
			}
		);
	},
};

export default apiClient;
