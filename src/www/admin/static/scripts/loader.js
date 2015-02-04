(function () {
	var points = new Array;
	points.push('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA0AAAAQBAMAAAA/jegKAAAAMFBMVEWcThVVIgBVIgBUIQBWHwBVIgBVIgBVIgBVIgBVIQBVIgBPJABVIgBWIwAAAABVIgCCdzN5AAAAD3RSTlMAlcl4FOT2ptfdvRhENwF6tE0BAAAAY0lEQVQI12NgYDFtdGAAAo///+eC6PX///8ECvD8B4ICBoanIPoPA8N9EP2JgUEeRP8/wKAPor4JMMSD+QsY8sF0AEM/mJ4AVbeA4RqYNmBgBFG/HjBwgySkgfbwVCbJMjAAAAYDSpeMp7/QAAAAAElFTkSuQmCC');
	points.push('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA4AAAALBAMAAAC9q6FRAAAAMFBMVEWcThWAMwCAMwCAMwCAMwCBMwCAMwCAMwCAMwCDLACAMwCAMwCANACAMgD/AACAMwAX2X54AAAAD3RSTlMAV83hfTmx++sLk+egJgFrG5vVAAAASklEQVQI12NgmJjckTKBgYFX////74UMDNf/g4ADw3owXcrQD6b/MJwH078Z5MH0ZwYuMP2JgW85iP7IwMAKNOD/AQYgw8k4kQEAgOI2ASlUEn0AAAAASUVORK5CYII=');
	points.push('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKBAMAAAB/HNKOAAAAMFBMVEWcThXIcTfIcTfIcTfIcTfIcTfIcTfHcTfIcjbIcTfHcTeqVVXIcTfIczf/AADIcTeCBB0DAAAAD3RSTlMAsOfT2/XGYxKMcwNYKgH3X2xUAAAAPUlEQVQI12PgEPK8wMCw8f//GAaG9f///z/AIA8kvRnsgeR3Bn8g+ZlBFEh+ZbgKZvPo////i4GhPc14HQDfoiJRD3ymmQAAAABJRU5ErkJggg==');
	points.push('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA4AAAALBAMAAAC9q6FRAAAAMFBMVEWcThX/mVX/mFX/mFX/mVX/mVT/mFX/m1X/mVX/mVX/mFX/mlT/pFr/mVX/mVX/mVVPZKOjAAAAD3RSTlMA96lr8Ie9JNfcQBgH4vnEmqxYAAAATUlEQVQI12NgYGDX8NRmYGDgCXz3/6MBA8PK/0AgzsAlD6I/Mex9D6J/MsSDqP9fGPLB9DeG+WD6MYM9mFZg2AaiRA8wMCxr9EwuYAAAYbQ00qywMe4AAAAASUVORK5CYII=');
	points.push('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAsAAAALBAMAAABbgmoVAAAAMFBMVEWcThXIcTfIcTfHcTfJcjXIcTfIcTfIbzbHcTfIcTfJcDbHcTfIcTfIcTf/AADIcTd/hisqAAAAD3RSTlMAXvKHDNbtK9LgF5vHyQH5nLrmAAAAS0lEQVQI12NgcA5tfMDAwJj///9xBoZl/////+nAsB9I/Tdg6AdRBxj0QZQCw30QFcBgD6I2MHgCyU8LGNjn//+fyMDAsPhQ9AIGALkqLN+K3VggAAAAAElFTkSuQmC');
	
	function getRandomInt (min, max) {
	    return Math.floor(Math.random() * (max - min + 1)) + min;
	}

	var anim = null;

	window.animatedLoader = function(elm, estimated_time) {
		var max = 500;
		var nb = 0;
		var prev = null;
		var i = (estimated_time * 1000) / max;

		anim = window.setInterval(function () {
			if (nb++ >= max)
			{
				window.clearInterval(anim);
			}
			
			if (prev)
			{
				prev.style.opacity = getRandomInt(25, 100) / 100;
			}

			var max_w = Math.min(elm.offsetWidth, elm.offsetWidth * ((nb / max)+0.1));
			var min_w = Math.max(0, max_w - (elm.offsetWidth / 10));

			var img = document.createElement('img');
			img.src = points[getRandomInt(0, points.length-1)];
			img.alt = '';
			img.style.left = getRandomInt(Math.abs(Math.floor(min_w)), Math.abs(Math.floor(max_w))) + 'px';
			img.style.top = getRandomInt(0, elm.offsetHeight) + 'px';
			elm.appendChild(img);
			prev = img;
		}, i);
	};

	window.stopAnimatedLoader = function() {
		window.clearInterval(anim);
	};
})();
