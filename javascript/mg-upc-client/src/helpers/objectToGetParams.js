import {getMgUpcConfig} from "./functions";

export default function objectToGetParams( object ) {
	const params = Object.entries(object)
		.filter(([, value]) => value !== undefined && value !== null)
		.map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(String(value))}`);
	const sep = getMgUpcConfig().root.indexOf( '?' ) !== -1 ? '&' : '?';
	return params.length > 0 ? `${sep}${params.join('&')}` : '';
}