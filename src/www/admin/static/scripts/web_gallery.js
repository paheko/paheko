(function () {
	var div, fig;

	document.addEventListener('DOMContentLoaded', enableGallery, false);

	function enableGallery()
	{
		if (!document.querySelectorAll) {
			return false;
		}

		var items = document.querySelectorAll('a.internal-image');

		for (var i = 0; i < items.length; i++)
		{
			var a = items[i];
			a.setAttribute('data-pos', i);
			a.onclick = function (e) {
				e.preventDefault();
				openImageBrowser(items, this.getAttribute('data-pos'));
				return false;
			};
		}

		document.querySelectorAll('div.slideshow').forEach(e => enableSlideshow(e));
	};

	window.enableImageGallery = enableGallery;

	function openImageBrowser(items, pos)
	{
		div = document.createElement('div');
		div.className = 'imageBrowser';

		var fig = document.createElement('figure');

		div.onclick = function (e) {
			div.style.opacity = 0;
			window.setTimeout(function() { div.parentNode.removeChild(div); }, 500);
		};

		var img = document.createElement('img');
		img.title = 'Cliquer sur l\'image pour aller à la suivante, ou à côté pour fermer';
		img.pos = pos || 0;

		img.onload = function () {
			fig.classList.remove('loading');
			img.style.width = 'initial';
			img.style.height = 'initial';
		};

		img.onclick = function (e) {
			e.stopPropagation();
			img.pos++;
			openImage(img, items);
		};

		fig.appendChild(img);
		div.appendChild(fig);
		document.body.appendChild(div);

		openImage(img, items, div);
	}

	function openImage(img, items)
	{
		// Pour animation
		var fig = img.parentNode;
		fig.classList.add('loading');

		var pos = img.pos;

		if (pos >= items.length)
		{
			var div = img.parentNode.parentNode;
			div.style.opacity = 0;
			window.setTimeout(function() { div.parentNode.removeChild(div); }, 500);
			return;
		}

		img.style.width = 0;
		img.style.height = 0;
		img.src = items[pos].href;
		img.pos = pos;
	}

	function enableSlideshow(gallery)
	{
		var images = gallery.getElementsByTagName('figure');
		var count = images.length;

		var div = document.createElement('div');
		div.className = 'index';

		for (var i = 0; i < count; i++) {
			var btn = document.createElement('button');

			if (i == 0) {
				btn.className = 'current';
			}

			btn.onclick = (e) => {
				var btn = e.target;
				var i = parseInt(btn.innerText, 10)-1;
				gallery.firstChild.scrollTop = i*450;
				gallery.querySelector('.current').classList.remove('current');
				btn.classList.add('current');
			};

			btn.innerText = i + 1;

			div.appendChild(btn);
		}

		gallery.appendChild(div);

		var nav = document.createElement('div');
		nav.className = 'nav';

		var get_current_idx = () => parseInt(gallery.querySelector('.current').innerText, 10)-1;

		var btn = document.createElement('button');
		btn.className = 'prev';
		btn.onclick = () => {
			var i = get_current_idx() - 1;
			var buttons = gallery.querySelectorAll('.index button');

			if (i < 0) {
				i = buttons.length - 1;
			}

			buttons[i].click();
		};
		btn.innerHTML = '◀';
		nav.appendChild(btn);

		var btn = document.createElement('button');
		btn.className = 'prev';
		btn.onclick = () => {
			var i = get_current_idx()+1;
			var buttons = gallery.querySelectorAll('.index button');

			if (i >= buttons.length) {
				i = 0;
			}

			buttons[i].click();
		};
		btn.innerHTML = '▶'
		nav.appendChild(btn);

		gallery.appendChild(nav);
	}
}());