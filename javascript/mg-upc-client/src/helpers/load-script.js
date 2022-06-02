function loadScript(src) {
	return new Promise(function(resolve, reject) {
		const s = document.createElement('script');
		let r = false;
		s.type = 'text/javascript';
		s.src = src;
		s.async = true;
		s.onerror = function(err) {
			reject(err, s);
		};
		s.onload = s.onreadystatechange = function() {
			if (!r && (!this.readyState || this.readyState == 'complete')) {
				r = true;
				setTimeout( function(){ resolve() } ,100 );
			}
		};
		const parent = document.head || document.body || document.documentElement;
		parent.appendChild(s);
	});
}

export default loadScript;
