import { h, Fragment } from 'preact';

export default function shareButton( iconConfig) {
	if ( ! iconConfig.slug ) {
		iconConfig.slug = iconConfig.name.toLowerCase();
	}
	return (
		<a href={iconConfig.url} title={"Share with " + iconConfig.name} className={"mg-upc-dg-share"} target='_blank' rel='noopener'>
			<div className={"mg-upc-share-btn-img mg-upc-share-" + iconConfig.slug}> </div>
		</a>
	);
}
