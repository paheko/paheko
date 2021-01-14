(function () {
	if (!document.documentElement.style.setProperty 
		|| !window.CSS || !window.CSS.supports
		|| !window.CSS.supports('--var', 0))
	{
		return;
	}

	var logo_limit_x = 170;

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
		var new_color = colorToRGB(color, element).join(',');

		// Mise à jour variable CSS
		document.documentElement.style.setProperty('--' + element, new_color);

		applyColors();
		return new_color;
	}

	function applyColors()
	{
		let input = $('#f_couleur2');
		var color = colorToRGB(input.value, 'gSecondColor');

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
			$('#f_image_fond').value = i.substr(i.indexOf(',')+1);

			delete canvas2;
			delete canvas;
			delete ctx;
			delete img;
		};

		var bg = $('#f_image_fond');

		if (bg.value) {
			img.src = 'data:image/png;base64,' + bg.value;
		}
		else if (bg.dataset.source) {
			img.src = 'data:image/png;base64,' + bg.dataset.source;
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
					data[i+3] = b ? (x > 170 ? 50 : 150) : 0;
					i += 4;
				}
			}

			ctx.putImageData(imgData, 0, 0);

			var i = canvas.toDataURL('image/png');

			$('#f_image_fond').value = i.substr(i.indexOf(',')+1);

			delete canvas2;
			delete canvas;
			delete ctx;
			delete img;

			callback();
		};

		img.src = data;
	}

	garradin.onload(function () {
		var couleurs = {'couleur1': 'gMainColor', 'couleur2': 'gSecondColor'};

		for (var couleur in couleurs)
		{
			if (!couleurs.hasOwnProperty(couleur)) continue;

			var input = document.getElementById('f_' + couleur);

			input.oninput = function () {
				var c = changeColor(couleurs[this.name], this.value);
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
				changeColor(couleurs[input.name], input.value);
				return false;
			};

			input.parentNode.insertBefore(reset_btn, input.nextSibling);
		}

		var bg = $('#f_background');
		bg.onchange = () => {
			if (!bg.files.length) return;

			var reader = new FileReader;
			reader.onload = (e) => {
				importBackgroundImage(e.target.result, applyColors);
				bg.disabled = true;
				bg.value = '';
			};
			reader.readAsDataURL(bg.files[0]);
		};

		var reset_btn = document.createElement('button');
		reset_btn.className = 'resetButton icn-btn';
		reset_btn.type = 'button';
		reset_btn.innerHTML = 'RàZ';

		reset_btn.onclick = () => {
			$('#f_image_fond').dataset.source = '';
			$('#f_image_fond').value = '';
			bg.disabled = false;

			applyColors();
		};

		bg.parentNode.insertBefore(reset_btn, bg.nextSibling);
	});
})();