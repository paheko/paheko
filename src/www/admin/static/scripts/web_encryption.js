(function () {
	var aes_loaded = false;
	var iteration = 0;
	var self_path_match = /static\/scripts\/web_encryption\.js/;
	var www_url;
	var encryptPassword = null;
	var base_url;
	var init = false;

	var scripts = document.getElementsByTagName('script');

	for (var i = 0; i < scripts.length; i++) {
		if (scripts[i].src.match(self_path_match)) {
			www_url = scripts[i].src.replace(/\/admin\/.*$/, '/');
			break;
		}
	}

	function load_aes(callback)
	{
		if (aes_loaded) {
			if (callback) {
				callback();
			}
			return;
		}

		var url = www_url + 'admin/static/scripts/lib/gibberish-aes.min.js';
		var s = document.createElement('script');
		s.src = url;
		s.type = 'text/javascript';
		s.onload = function () {
			aes_loaded = true;
			if (callback) {
				callback();
			}
		};

		document.head.appendChild(s);
	}

	function formatContent(content)
	{
		// htmlspecialchars ENT_QUOTES
		content = content.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
			.replace(/'/g, '&#039;').replace(/"/g, '&quot');

		// Titles
		content = content.replace(/^(=+)\s*([^\n=]*)\s*(\1\s*)*/gm, function (match, h, content) {
			h = h.length;
			return '<h'+h+'>'+content+'</h'+h+'>';
		});

		content = content.replace(/^(#+)\s*([^\n]+)/gm, function (match, h, content) {
			h = h.length;
			return '<h'+h+'>'+content+'</h'+h+'>';
		});

		// Horizontal line
		content = content.replace(/^(--+|==+)$/gm, '<hr />');

		// Strikethrough
		content = content.replace(/(--|~~)([^\n]+?)\1/g, '<s>$2</s>');

		// Bold
		content = content.replace(/\*{2}([^\n]*)\*{2}/g, '<strong>$1</strong>');

		// Italic
		content = content.replace(/''([^\n]*)''/g, '<em>$1</em>');
		content = content.replace(/\*([^\n]*)\*/g, '<em>$1</em>');

		// Typo spaces in French
		//content = content.replace(/\h*([?!;:»])(\s+|$)/g, '&nbsp;$1$2');
		//content = content.replace(/(^|\s+)([«])\h*/g, '$1$2&nbsp;');

		function linkTag(match, url, label) {
			if (url.match(/^https?:/))
			{
			}
			else if (url.match(/@/) && !url.match(/^mailto:/))
			{
				url = 'mailto:' + url;
			}
			else
			{
				// Local page link
				url = '?' + url;
			}

			return '<a href="' + url + '">' + label + '</a>';
		}

		// Links
		content = content.replace(/\[{2}([^\|\]\n]+?)\|([^\]\n]+?)\]{2}/g, linkTag);
		content = content.replace(/\[{2}(([^\]]+?))\]{2}/g, linkTag);
		content = content.replace(/<(((?:https?|mailto):[^>]+?))>/g, linkTag);
		content = content.replace(/\[([^\]]+?)\]\(([^\)]+?)\)/g, linkTag);

		// Extensions
		content = content.replace(/&lt;&lt;(\w+)([\| ]([^&]+))?&gt;&gt;/g, (match, name, separator, params) => {
			params = params.split('|');
			if (name == 'image') {
				var src = params[0];
				var align = params[1] || 'center';
				var caption = 2 in params ? '<figcaption>' + params[2] + '</figcaption>' : '';
				var size = align == 'center' ? '750px' : '250px';

				return `<figure class="image img-${align}"><a href="${base_url + src}" class="internal-image" target="_image"><img src="${base_url + src}?${size}" alt="" /></a>${caption}</figure>`;
			}
			else if (name == 'file') {
				var src = params[0];
				var ext = (a = src.lastIndexOf('.')) && a > 0 ? src.substr(a+1).toUpperCase() : '';
				var caption = params[1] || src.replace(/\.[^\.]+$/, '');
				return `<aside class="file" data-type="${ext}"><a href="${base_url + src}" class="internal-file"><b>${caption}</b> <small>${ext}</small></a></aside>`;
			}
			else {
				return match;
			}
		});

		// nl2br
		content = content.replace(/\r/g, '').replace(/\n/g, '<br />');

		return content;
	}

	let edit = document.getElementById('f_content') ? true : false;
	var new_content;

	let disableEncryption = (reset) => {
		var c = document.getElementById('f_content');

		if (reset) {
			c.value = '';
			document.getElementById('f_format').value = 'markdown';
		}

		delete c.form.onbeforesubmit;
		c.disabled = false;

		if (new_content) {
			c.name = new_content.name;
			new_content.remove();
		}

		encryptPassword = null;
	};

	let enableEncryption = (form, do_decrypt) => {
		document.getElementById('f_content').disabled = true;

		load_aes(function () {
			askPassword(!do_decrypt);
			document.getElementById('f_content').disabled = false;

			if (do_decrypt) {
				decrypt();
			}

			var content = document.getElementById('f_content');

			new_content = document.createElement('input');
			new_content.type = 'hidden';
			new_content.name = content.name;
			content.name = null;
			content.parentNode.appendChild(new_content);

			form.onbeforesubmit = function () {
				if (typeof GibberishAES == 'undefined')
				{
					alert("Le chargement de la bibliothèque AES n'est pas terminé.\nLe chiffrement est impossible pour le moment, recommencez dans quelques instants ou désactivez le chiffrement.");
					return false;
				}

				if (!encryptPassword) {
					return;
				}

				new_content.value = GibberishAES.enc(content.value, encryptPassword);
				content.disabled = true;
				console.log('encrypted');
			};
		});
	};

	let askPassword = (first) => {
		encryptPassword = window.prompt(first ? "Le mot de passe n'est ni transmis ni enregistré.\n"
			+ "Il n'est pas possible de retrouver le contenu si vous perdez le mot de passe.\n"
			+ "Merci d'indiquer ici le mot de passe :" : "Mot de passe :");

		if (!encryptPassword)
		{
			encryptPassword = null;

			if (edit)
			{
				if (window.confirm("Aucun mot de passe entré.\nDésactiver le chiffrement et effacer le contenu ?"))
				{
					disableEncryption(true);
					return;
				}

				askPassword(first);
			}

			return;
		}

		iteration = 0;
	};

	// Used in _file_render_encrypted.tpl
	window.pleaseDecrypt = () => {
		load_aes(() => {
			askPassword();
			decrypt();
		});
	};

	var decrypt = function ()
	{
		if (!encryptPassword) {
			return;
		}

		if (typeof GibberishAES == 'undefined')
		{
			if (iteration >= 5)
			{
				iteration = 0;
				encryptPassword = null;
				window.alert("Impossible de charger la bibliothèque AES, empêchant le déchiffrement de la page.\nAttendez quelques instants avant de recommencer ou rechargez la page.");
				return;
			}

			iteration++;
			window.setTimeout(decrypt, 500);
			return;
		}

		if (edit) {
			var elm = document.getElementById('f_content');
		}
		else {
		 	var elm = document.getElementById('web_encrypted_content');
		}

		var content = elm.value || elm.innerText;
		content = content.replace(/\s+/g, '');

		try {
			content = GibberishAES.dec(content, encryptPassword);
		}
		catch (e)
		{
			encryptPassword = null;
			window.alert('Impossible de déchiffrer. Mauvais mot de passe ?');

			if (edit)
			{
				// Redemander le mot de passe
				askPassword();
				decrypt();
			}
			return false;
		}

		if (!edit)
		{
			elm.style.display = 'block';
			document.getElementById('web_encrypted_message').style.display = 'none';
			base_url = elm.dataset.url.replace(/\/$/, '') + '/';
			content = formatContent(content);
			elm.innerHTML = content;

			if (content.match(/<img/) && typeof window.enableImageGallery != 'undefined') {
				enableImageGallery();
			}
		}
		else
		{
			elm.value = content;
		}
	};

	document.addEventListener('DOMContentLoaded', () => {
		if (init) return;
		init = true;

		if (e = document.getElementById('f_format')) {
			edit = true;

			e.addEventListener('change', () => {
				if (e.value == 'encrypted') {
					enableEncryption(e.form);
				}
				else if (encryptPassword) {
					disableEncryption(false);
				}
			});

			if (e.value == "encrypted") {
				enableEncryption(e.form, true);
			}
		}
	});
} ());