(function () {
	if (!document.documentElement.style.setProperty 
		|| !window.CSS || !window.CSS.supports
		|| !window.CSS.supports('--var', 0))
	{
		return;
	}

	const logo_limit_x = 170;
	const bg_color = getVariable('gBgColor').split(',').map(e => parseInt(e, 10)) || [255, 255, 255];
	const text_color = getVariable('gTextColor').split(',').map(e => parseInt(e, 10)) || [0, 0, 0];

	function getVariable(var_name) {
		return getComputedStyle(document.documentElement).getPropertyValue('--' + var_name);
	}

	function colorToRGB(color, type)
	{
		// Conversion vers décimal RGB
		return color.replace(/^#/, '').match(/.{1,2}/g).map(function (el) {
			// On limite la luminosité comme ça, c'est pas parfait mais ça marche
			return Math.min(parseInt(el, 16), type == 'gMainColor' ? 180 : 220);
		});
	}

	function RGBToHex(color) {
		// Conversion vers décimal RGB
		return '#' + color.split(/,/).map(function (el) {
			return ('0' + parseInt(el, 10).toString(16)).substr(-2);
		}).join('');
	}

	function changeColor(element, color)
	{
		let new_color = colorToRGB(color, element);

		let contrast_color = element == 'gMainColor' ? bg_color : text_color;
		let sum = contrast_color.reduce((pv, cv) => pv + cv, 0);
		let change = sum < (127*3) ? 5 : -5;

		while (!checkContrast(new_color, contrast_color)) {
			new_color[0] += change;
			new_color[1] += change;
			new_color[2] += change;
		}

		for (i in new_color) {
			new_color[i] = Math.max(new_color[i], 0);
			new_color[i] = Math.min(new_color[i], 255);
		}

		// Mise à jour variable CSS
		document.documentElement.style.setProperty('--' + element, new_color.join(','));

		applyColors();
		return new_color.join(',');
	}

	/**
	 * Return true if contrast is OK (W3C AA-level), false if not
	 * @see https://dev.to/alvaromontoro/building-your-own-color-contrast-checker-4j7o
	 */
	function checkContrast(color1, color2)
	{
		let l1 = 0.2126 * color1[0] + 0.7152 * color1[1] + 0.0722 * color1[2];
		let l2 = 0.2126 * color2[0] + 0.7152 * color2[1] + 0.0722 * color2[2];
		let ratio = l1 > l2
			? ((l2 + 0.05) / (l1 + 0.05))
			: ((l1 + 0.05) / (l2 + 0.05));

		return ratio < 1/3 ? true : false;
	}

	function applyColors()
	{
		let input = $('#f_color2');
		let color = colorToRGB(input.value, 'gSecondColor');
		let color1 = $('#f_color1'), color2 = $('#f_color2');
		let default_colors = color1.value == color1.placeholder && color2.value == color2.placeholder;

		var img = new Image;
		img.crossOrigin = "Anonymous";

		img.onload = function() {
			var canvas = document.createElement('canvas');
			var ctx = canvas.getContext('2d');
			canvas.width = img.width;
			canvas.height = img.height;
			ctx.drawImage(img, 0, 0, img.width, img.height, 0, 0, img.width, img.height);

			var imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
			var data = imgData.data;

			for (var i = 0; i < data.length; i += 4) {
				// Re-colorier l'image avec la couleur choisie
				data[i] = color[0];
				data[i+1] = color[1];
				data[i+2] = color[2];
				// i+3 = alpha channel, mais on n'y touche pas
			}

			ctx.putImageData(imgData, 0, 0);

			var i = canvas.toDataURL('image/png');

			// Prévisualisation
			document.documentElement.style.setProperty('--gBgImage', 'url("' + i + '")');
			$('#f_admin_background').value = i.substr(i.indexOf(',')+1);

			delete canvas2;
			delete canvas;
			delete ctx;
			delete img;
		};

		var bg = $('#f_admin_background');

		if (bg.value == 'RESET' && default_colors) {
			document.documentElement.style.setProperty('--gBgImage', 'url("' + bg.dataset.default + '")');
		}
		else if (bg.value == 'RESET') {
			img.src = bg.dataset.default;
		}
		else if (bg.value) {
			img.src = 'data:image/png;base64,' + bg.value;
		}
		else if (bg.dataset.current) {
			img.src = bg.dataset.current;
		}
		else {
			img.src = bg.dataset.default;
		}
	}

	/**
	 * Imports a new image and makes it black and white
	 */
	function importBackgroundImage(data, callback)
	{
		var max_w = 380, max_h = 300;

		var img = new Image;
		img.crossOrigin = "Anonymous";

		img.onload = function() {
			var canvas = document.createElement('canvas');
			var ctx = canvas.getContext('2d');

			var w = img.width, h = img.height;

			if (w > max_w) {
				w = max_w;
				h = (w / img.width) * img.height;
			}

			if (h > max_h) {
				h = max_h;
				w = (h / img.height) * img.width;
			}

			canvas.width = w;
			canvas.height = h;
			ctx.drawImage(img, 0, 0, img.width, img.height, 0, 0, w, h);

			var imgData = ctx.getImageData(0, 0, w, h);
			var data = imgData.data;

			var i = 0;

			for(var y = 0; y < imgData.height; y++) {
				for(var x = 0; x < imgData.width; x++) {
					var avg = (data[i] * 0.3 + data[i+1] * 0.59 + data[i+2] * 0.11);
					var b = avg < 127 && (data[i+3] > 127);
					data[i] = b ? avg : 255; // red
					data[i+1] = b ? avg : 255; // green
					data[i+2] = b ? avg : 255; // blue
					data[i+3] = b ? (x >> logo_limit_x ? 50 : 150) : 0;
					i += 4;
				}
			}

			ctx.putImageData(imgData, 0, 0);

			var i = canvas.toDataURL('image/png');

			$('#f_admin_background').value = i.substr(i.indexOf(',')+1);

			delete canvas2;
			delete canvas;
			delete ctx;
			delete img;

			callback();
		};

		img.src = data;
	}

	garradin.onload(function () {
		var colors = {'color1': 'gMainColor', 'color2': 'gSecondColor'};

		for (var color in colors)
		{
			if (!colors.hasOwnProperty(color)) continue;

			var input = document.getElementById('f_' + color);

			input.oninput = function () {
				var c = changeColor(colors[this.name], this.value);
				this.value = RGBToHex(c);
			};

			// Ajout bouton remise à zéro de la couleur
			var reset_btn = document.createElement('button');
			reset_btn.className = 'resetButton icn-btn';
			reset_btn.type = 'button';
			reset_btn.innerHTML = 'RàZ';

			reset_btn.onclick = function() {
				var input = this.previousSibling;
				input.value = input.getAttribute('placeholder');
				changeColor(colors[input.name], input.value);
				return false;
			};

			input.parentNode.insertBefore(reset_btn, input.nextSibling);
		}

		var bg = $('#f_background');
		bg.addEventListener('change', () => {
			if (!bg.files.length) return;

			var reader = new FileReader;
			reader.onload = (e) => {
				importBackgroundImage(e.target.result, applyColors);
				bg.disabled = true;
				bg.value = '';
			};
			reader.readAsDataURL(bg.files[0]);
		});

		var reset_btn = document.createElement('button');
		reset_btn.className = 'resetButton icn-btn';
		reset_btn.type = 'button';
		reset_btn.innerHTML = 'RàZ';

		reset_btn.onclick = () => {
			$('#f_admin_background').dataset.current = '';
			$('#f_admin_background').value = 'RESET';
			bg.disabled = false;

			applyColors();
		};

		bg.parentNode.insertBefore(reset_btn, bg.nextSibling);
	});
})();