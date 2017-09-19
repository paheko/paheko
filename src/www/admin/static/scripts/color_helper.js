(function () {
	if (!document.documentElement.style.setProperty 
		|| !window.CSS || !window.CSS.supports
		|| !window.CSS.supports('--var', 0))
	{
		return;
	}

	var logo_limit_x = 170;

	function colorToRGB(color)
	{
		// Conversion vers décimal RGB
		return color.replace(/^#/, '').match(/.{1,2}/g).map(function (el) {
			// On limite la luminosité comme ça, c'est pas parfait mais ça marche
			return Math.min(parseInt(el, 16), 210);
		});
	}

	function changeColor(element, color)
	{
		var new_color = colorToRGB(color).join(', ');

		// Mise à jour variable CSS
		document.documentElement.style.setProperty('--' + element, new_color);

		applyLogoColors();
	}

	function applyLogoColors()
	{
		var color = colorToRGB(document.getElementById('f_couleur2').value);
		
		var img = new Image;
		img.src = g.static_url + 'gdin_bg.png';

		img.onload = function() {
			var canvas = document.createElement('canvas');
			var ctx = canvas.getContext('2d');
			canvas.width = img.width;
			canvas.height = img.height;
			ctx.drawImage(img, 0, 0, img.width, img.height, 0, 0, img.width, img.height);

			var imgData = ctx.getImageData(0, 0, img.width, img.height);
			var data = imgData.data;

			for (var i = 0, n = data.length; i < n; i += 4)
			{
				// Re-colorier l'image avec la couleur choisie
				data[i] = color[0]; // red
				data[i+1] = color[1]; // green
				data[i+2] = color[2]; // blue
				// i+3 = alpha channel, mais on n'y touche pas
			}

			ctx.putImageData(imgData, 0, 0);

			var i = canvas.toDataURL('image/png');

			// Prévisualisation
			document.querySelector('body').style.backgroundImage = 'url("' + i + '")';
			document.querySelector('.header .menu').style.backgroundImage = 'url("' + i + '")';

			document.getElementById('f_image_fond').value = i.substr(i.indexOf(',')+1);

			delete canvas2;
			delete canvas;
			delete ctx;
		};
	}

	garradin.onload(function () {
		var couleurs = {'couleur1': 'gMainColor', 'couleur2': 'gSecondColor'};

		for (var couleur in couleurs)
		{
			if (!couleurs.hasOwnProperty(couleur)) continue;

			var input = document.getElementById('f_' + couleur);

			input.oninput = function () {
				changeColor(couleurs[this.name], this.value);
			};

			// Ajout bouton remise à zéro de la couleur
			var reset_btn = document.createElement('input');
			reset_btn.className = 'resetButton';
			reset_btn.type = 'button';
			reset_btn.value = 'RàZ';

			reset_btn.onclick = function() {
				var input = this.previousSibling;
				input.value = input.getAttribute('placeholder');
				changeColor(couleurs[input.name], input.value);
				return false;
			};

			input.parentNode.insertBefore(reset_btn, input.nextSibling);
		}
	});
})();