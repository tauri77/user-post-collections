
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

function listSupport( list, feature ) {
	if ( list.type ) {
		return typeSupport( list.type, feature );
	}
	return false;
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

export { getMgUpcConfig, getUpcTypeConfig, listSupport, typeSupport, listIsEditable, noItemImage };
