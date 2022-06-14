
function getMgUpcConfig() {
	return MgUserPostCollections;
}

function getUpcTypeConfig( type ) {
	const typesConfig = getMgUpcConfig()?.types;
	if ( typesConfig && typesConfig[type] ) {
		return typesConfig[type];
	}
	return false;
}

function getUpcStatuses( status ) {
	const statuses = getMgUpcConfig()?.statuses;
	if ( statuses && statuses[status] ) {
		return statuses[status];
	}
	return false;
}

function getStatusLabel( status ) {
	const statusConfig = getUpcStatuses( status );
	if ( statusConfig ) {
		return statusConfig.label;
	}
	return status;
}

function statusShowInList( status ) {
	const statusConfig = getUpcStatuses( status );
	return statusConfig && statusConfig.show_in_status_list;
}

function listSupport( list, feature ) {
	if ( list.type ) {
		return typeSupport( list.type, feature );
	}
	return false;
}

function getNotAlwaysExists() {
	const arr   = [];
	const types = getMgUpcConfig()?.types;
	for ( const type in types ) {
		if ( types.hasOwnProperty( type ) ) {
			if ( ! typeSupport( type, 'always_exists' ) ) {
				arr.push( types[type] );
			}
		}
	}
	return arr;
}

function listIsEditable( list ) {
	const type = list.type;
	return  typeSupport( type, 'editable_title') ||
		    typeSupport( type, 'editable_content' ) ||
			getUpcTypeConfig( type )?.available_statuses?.length > 1;
}

function typeSupport( type, feature ) {
	const typeConfig = getUpcTypeConfig( type );
	if ( typeConfig && typeConfig.supports) {
		return typeConfig.supports.includes( feature );
	}
	return false;
}

const noItemImage = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVQYV2NgYAAAAAMAAWgmWQ0AAAAASUVORK5CYII=';

export {
	getMgUpcConfig,
	getUpcTypeConfig,
	getNotAlwaysExists,
	listSupport,
	typeSupport,
	listIsEditable,
	getStatusLabel,
	statusShowInList,
	noItemImage
};
