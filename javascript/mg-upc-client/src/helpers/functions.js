
function getMgUpcConfig() {
	return MgUserPostCollections;
}

function getSortableUrl() {
	return getMgUpcConfig()?.sortable;
}

function getUpcTypeConfig( type ) {
	const typesConfig = getMgUpcConfig()?.types;
	if ( typesConfig && typesConfig[type] ) {
		return typesConfig[type];
	}
	return false;
}

function getUpcTypes() {
	return Object.values(getMgUpcConfig()?.types);
}

function getUpcStatuses() {
	return Object.values(getMgUpcConfig()?.statuses);
}

function getUpcStatus( status ) {
	const statuses = getMgUpcConfig()?.statuses;
	if ( statuses && statuses[status] ) {
		return statuses[status];
	}
	return false;
}

function getStatusLabel( status ) {
	const statusConfig = getUpcStatus( status );
	if ( statusConfig ) {
		return statusConfig.label;
	}
	return status;
}

function statusShowInList( status ) {
	const statusConfig = getUpcStatus( status );
	return statusConfig && statusConfig.show_in_status_list;
}

function listSupport( list, feature ) {
	if ( list.type ) {
		return typeSupport( list.type, feature );
	}
	return false;
}

function getNotAlwaysExists(addingPost) {
	const arr   = [];
	const types = getMgUpcConfig()?.types;
	for ( const type in types ) {
		if ( types.hasOwnProperty( type ) ) {
			if ( ! typeSupport( type, 'always_exists' ) ) {
				if ( addingPost?.type ) {
					if ( types[type].available_post_types.includes( addingPost.type ) ) {
						arr.push( types[type] );
					}
				} else {
					arr.push( types[type] );
				}
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

function cloneObj(obj){
	return JSON.parse( JSON.stringify( obj ) );
}

function str_nl2br (str) {
	if ( typeof str !== 'string' ) {
		return '';
	}
	return str.replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1<br>$2');
}

function prevent(e) {
	if (e.stopImmediatePropagation) e.stopImmediatePropagation();
	if (e.stopPropagation) e.stopPropagation();
	e.preventDefault();
	return false;
}

export {
	getMgUpcConfig,
	getUpcTypes,
	getSortableUrl,
	getUpcTypeConfig,
	getNotAlwaysExists,
	listSupport,
	typeSupport,
	listIsEditable,
	getUpcStatuses,
	getStatusLabel,
	statusShowInList,
	noItemImage,
	cloneObj,
	str_nl2br,
	prevent
};
