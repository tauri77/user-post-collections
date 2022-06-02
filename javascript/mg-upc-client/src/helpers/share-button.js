import { h, Fragment } from 'preact';

export default function shareButton( iconConfig) {

	const defaultProps = {
		bgStyle: {},
		borderRadius: 0,
		iconFillColor: 'white',
		size: 64,
	};

	iconConfig = Object.assign( defaultProps, iconConfig );

	return (
		<a href={iconConfig.url} title={"Share with " + iconConfig.name} className={"mg-upc-dg-share"} target='_blank' rel='noopener'>
			<svg viewBox="0 0 64 64" width={iconConfig.size} height={iconConfig.size}>
				{iconConfig.round ? (
					<circle cx="32" cy="32" r="31" fill={iconConfig.color} style={iconConfig.bgStyle} />
				) : (
					<rect
						width="64"
						height="64"
						rx={iconConfig.borderRadius}
						ry={iconConfig.borderRadius}
						fill={iconConfig.color}
						style={iconConfig.bgStyle}
					/>
				)}

				<path d={iconConfig.path} fill={iconConfig.iconFillColor} />
			</svg>
		</a>
	);
}
