(function () {
	var aesEnabled = false;
	var iteration = 0;
	var encryptPassword = null;
	var www_url = location.href.replace(/admin\/.*$/, 'admin/');

	function loadAESlib()
	{
		if (aesEnabled)
		{
			return;
		}

		var s = document.createElement('script');
		s.type = 'text/javascript';
		s.src = www_url + 'static/gibberish-aes.min.js';

		document.head.appendChild(s);
		aesEnabled = true;
	}

	function formatContent(content)
	{
		// htmlspecialchars ENT_QUOTES
		content = content.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
			.replace(/'/g, '&#039;').replace(/"/g, '&quot');

		// Intertitres
		content = content.replace(/==([^\n]*)==/g, '<h2>$1</h2>');
		content = content.replace(/===([^\n]*)===/g, '<h3>$1</h3>');
		content = content.replace(/====([^\n]*)====/g, '<h4>$1</h4>');

		// Gras
		content = content.replace(/\*{2}([^\n]*)\*{2}/g, '<strong>$1</strong>');

		// Italique
		content = content.replace(/''([^\n]*)''/g, '<em>$1</em>');

		// Espaces typograhiques
		content = content.replace(/\h*([?!;:»])(\s+|$)/g, '&nbsp;$1$2');
		content = content.replace(/(^|\s+)([«])\h*/g, '$1$2&nbsp;');

		// Liens
		content = content.replace(/\[\[([^|]+)|([^\]]+)\]\]/g, '<a href="$2">$1</a>');
		content = content.replace(/\[\[([^\]]+)\]\]/g, '<a href="$1">$1</a>');

		// nl2br
		content = content.replace(/\r/g, '').replace(/\n/g, '<br />');

		return content;
	}

	window.wikiDecrypt = function (edit)
	{
		loadAESlib();

		encryptPassword = window.prompt('Mot de passe ?');

		if (!encryptPassword)
		{
			encryptPassword = null;

			if (edit)
			{
				if (window.confirm("Aucun mot de passe entré.\nDésactiver le chiffrement et effacer le contenu ?"))
				{
					document.getElementById('f_contenu').value = '';
					document.getElementById('f_chiffrement').checked = false;
					checkEncryption(document.getElementById('f_chiffrement'));
				}
				else
				{
					wikiDecrypt(true);
				}
			}

			return;
		}

		iteration = 0;
		decrypt(edit);
	};

	var decrypt = function (edit)
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

		var content = document.getElementById(edit ? 'f_contenu' : 'wikiEncryptedContent');
		var wikiContent = !edit ? (content.textContent ? content.textContent : content.innerText) : content.value;
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
				wikiDecrypt(true);
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
			checkEncryption(document.getElementById('f_chiffrement'));
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
				encryptPassword = window.prompt('Mot de passe à utiliser ?');
			}

			if (!encryptPassword)
			{
				elm.checked = false;
				encryptPassword = null;
				return;
			}

			loadAESlib();

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

			document.getElementById('f_form').onsubmit = function ()
			{
				if (typeof GibberishAES == 'undefined')
				{
					alert("Le chargement de la bibliothèque AES n'est pas terminé.\nLe chiffrement est impossible pour le moment, recommencez dans quelques instants ou désactivez le chiffrement.");
					return false;
				}

				var content = document.getElementById('f_contenu');
				content.value = GibberishAES.enc(content.value, encryptPassword);
				content.readOnly = true;
				return true;
			};
		}
		else
		{
			encryptPassword = null;
			var d = document.getElementById('encryptPasswordDisplay');
			d.innerHTML = 'désactivé';
			d.title = 'Chiffrement désactivé';
			d.onclick = null;
			document.getElementById('f_form').onsubmit = null;
		}
	};
} ());