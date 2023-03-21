(function () {
	var aes_loaded = false;
	var iteration = 0;
	var self_path_match = /static\/scripts\/web_encryption\.js/;
	var www_url;
	var encryptPassword = null;

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

		// Intertitres
		content = content.replace(/(=+)\s*([^\n=]*)\s*(\1\s*)*/g, function (match, h, content) {
			h = h.length;
			return '<h'+h+'>'+content+'</h'+h+'>';
		});

		// Gras
		content = content.replace(/\*{2}([^\n]*)\*{2}/g, '<strong>$1</strong>');

		// Italique
		content = content.replace(/''([^\n]*)''/g, '<em>$1</em>');

		// Espaces typograhiques
		content = content.replace(/\h*([?!;:»])(\s+|$)/g, '&nbsp;$1$2');
		content = content.replace(/(^|\s+)([«])\h*/g, '$1$2&nbsp;');

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

		// Liens
		content = content.replace(/\[{2}([^\|\]\n]+?)\|([^\]\n]+?)\]{2}/g, linkTag);
		content = content.replace(/\[{2}(([^\]]+?))\]{2}/g, linkTag);

		// nl2br
		content = content.replace(/\r/g, '').replace(/\n/g, '<br />');

		return content;
	}

	let edit = document.getElementById('f_content') ? true : false;

	let disableEncryption = (reset) => {
		if (reset) {
			document.getElementById('f_content').value = '';
			document.getElementById('f_format').selectedIndex = 0;
		}

		document.getElementById('f_content').disabled = false;
		encryptPassword = null;
	};

	let enableEncryption = (form, do_decrypt) => {
		document.getElementById('f_content').disabled = true;

		String.prototype.repeat = function(num)
		{
			return new Array(num + 1).join(this);
		};

		load_aes(function () {
			askPassword(true);
			document.getElementById('f_content').disabled = false;

			if (do_decrypt) {
				decrypt();
			}

			form.onsubmit = function ()
			{
				if (typeof GibberishAES == 'undefined')
				{
					alert("Le chargement de la bibliothèque AES n'est pas terminé.\nLe chiffrement est impossible pour le moment, recommencez dans quelques instants ou désactivez le chiffrement.");
					return false;
				}

				if (!encryptPassword) {
					return;
				}

				var content = document.getElementById('f_content');
				content.value = GibberishAES.enc(content.value, encryptPassword);
				content.readOnly = true;
				return true;
			};
		});
	};

	let askPassword = (first) => {
		load_aes();

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
				}
			}

			return;
		}

		iteration = 0;
	};

	window.pleaseDecrypt = () => {
		askPassword();
		decrypt();
	};

	var decrypt = function ()
	{
		if (!encryptPassword) {
			return;
		}

		if (typeof GibberishAES == 'undefined')
		{
			if (iteration >= 10)
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
			content.style.display = 'block';
			document.getElementById('web_encrypted_message').style.display = 'none';
			content.innerHTML = formatContent(content);
		}
		else
		{
			content.value = content;
		}
	};

	document.addEventListener('DOMContentLoaded', () => {
		if (e = document.getElementById('f_format')) {
			edit = true;

			if (e.value == "skriv/encrypted") {
				enableEncryption(e.form, true);
			}

			e.addEventListener('change', () => {
				if (e.value == 'skriv/encrypted') {
					enableEncryption(e.form);
				}
				else if (encryptPassword) {
					disableEncryption(false);
				}
			})
		}
	});
} ());