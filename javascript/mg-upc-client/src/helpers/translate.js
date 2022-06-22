

function translate( text, args = false ) {

	if ( MgUpcTexts && MgUpcTexts[ text ] ) {
		if ( args ) {
			return args.reduce(
				function( p ,c ) {
					return p.replace( /%s/, c );
				},
				MgUpcTexts[ text ]
			);
		}
		return MgUpcTexts[ text ];
	}

	return text;
}

export default translate;
