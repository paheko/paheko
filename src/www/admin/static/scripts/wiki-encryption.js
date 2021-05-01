(function () {
	var aes_loaded = false;
	var iteration = 0;
	var self_path_match = /static\/scripts\/wiki-encryption\.js/;
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

		var url = www_url + 'admin/static/scripts/gibberish-aes.min.js';
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
				// Local wiki link
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

	window.wikiDecrypt = function ()
	{
		load_aes();

		encryptPassword = window.prompt('Mot de passe ?');

		if (!encryptPassword)
		{
			encryptPassword = null;

			if (document.getElementById('f_content'))
			{
				if (window.confirm("Aucun mot de passe entré.\nDésactiver le chiffrement et effacer le contenu ?"))
				{
					document.getElementById('f_content').value = '';
					document.getElementById('f_encryption').checked = false;
					checkEncryption(document.getElementById('f_encryption'));
				}
				else
				{
					wikiDecrypt();
				}
			}

			return;
		}

		iteration = 0;
		decrypt();
	};

	var decrypt = function ()
	{
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

		var content = document.getElementById('f_content');
		var edit = true;

		if (!content) {
		 	content = document.getElementById('wikiEncryptedContent');
		 	edit = false;
		}

		var wikiContent = content.value || content.innerText;
		wikiContent = wikiContent.replace(/\s+/g, '');

		try {
			wikiContent = GibberishAES.dec(wikiContent, encryptPassword);
		}
		catch (e)
		{
			encryptPassword = null;
			window.alert('Impossible de déchiffrer. Mauvais mot de passe ?');

			if (edit)
			{
				// Redemander le mot de passe
				wikiDecrypt();
			}
			return false;
		}

		if (!edit)
		{
			content.style.display = 'block';
			document.getElementById('wikiEncryptedMessage').style.display = 'none';
			content.innerHTML = formatContent(wikiContent);
		}
		else
		{
			content.value = wikiContent;
			checkEncryption(document.getElementById('f_encryption'));
		}
	};

	window.checkEncryption = function(elm)
	{
		String.prototype.repeat = function(num)
		{
			return new Array(num + 1).join(this);
		};

		if (elm.checked)
		{
			if (!encryptPassword)
			{
				wikiDecrypt();
			}

			if (!encryptPassword)
			{
				elm.checked = false;
				encryptPassword = null;
				return;
			}

			load_aes(function () {
				var hidden = true;
				var d = document.getElementById('encryptPasswordDisplay');
				d.innerHTML = '&bull;'.repeat(encryptPassword.length);
				d.title = 'Cliquer pour voir le mot de passe';
				d.onclick = function () {
					if (hidden)
					{
						this.innerHTML = encryptPassword;
						this.title = 'Cliquer pour cacher le mot de passe.';
					}
					else
					{
						this.innerHTML = '&bull;'.repeat(encryptPassword.length);
						this.title = 'Cliquer pour voir le mot de passe';
					}
					hidden = !hidden;
				};

				elm.form.onsubmit = function ()
				{
					if (typeof GibberishAES == 'undefined')
					{
						alert("Le chargement de la bibliothèque AES n'est pas terminé.\nLe chiffrement est impossible pour le moment, recommencez dans quelques instants ou désactivez le chiffrement.");
						return false;
					}

					var content = document.getElementById('f_content');
					content.value = GibberishAES.enc(content.value, encryptPassword);
					content.readOnly = true;
					return true;
				};
			});
		}
		else
		{
			encryptPassword = null;
			var d = document.getElementById('encryptPasswordDisplay');
			d.innerHTML = 'désactivé';
			d.title = 'Chiffrement désactivé';
			d.onclick = null;
			elm.form.onsubmit = null;
		}
	};

	document.addEventListener('DOMContentLoaded', () => {
		if (e = document.getElementById('f_encryption')) {
			checkEncryption(e);
		}
	});
} ());