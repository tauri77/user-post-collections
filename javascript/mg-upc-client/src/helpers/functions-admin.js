
function getHashVar(name) {
	const params = new URLSearchParams( document.location.hash.substring( 1 ) );
	return params.get( name );
}

function setHashVar(name, value) {
	const params = new URLSearchParams( document.location.hash.substring( 1 ) );
	params.set( name, value );
	let hash = params.toString();
	if ( '' !== hash ) {
		hash = '#' + hash;
	}
	window.location.hash = hash;
}

function formatDate( string ) {
	return ( new Date( string ) ).toLocaleDateString();
}

export {
	getHashVar,
	setHashVar,
	formatDate
};


