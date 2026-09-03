(async function () {
	let edit = document.getElementById('f_content') ? true : false;
	var new_content;
	var encryptPassword = null;
	var init = false;
	var gibberish_url = g.static_url + 'scripts/lib/gibberish-aes.min.js';

	function GibberishDecrypt (content, password) {
		return new Promise((resolve) => {
			if (!gibberish_url) {
				content = GibberishAES.dec(content, password);
				resolve(content);
				return;
			}

			var script = document.createElement('script');
			script.type = 'text/javascript';
			script.src = gibberish_url;
			script.onload = () => {
				gibberish_url = null;
				content = GibberishAES.dec(content, password);
				resolve(content);
			};
			document.head.appendChild(script);
		});
	};

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

	let enableEncryption = async (form, do_decrypt) => {
		document.getElementById('f_content').disabled = true;

		// This is nessary to apply disabled styles...
		await new Promise(r => setTimeout(r, 50));

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

		form.addEventListener('beforesubmit', (e) => {
			if (!encryptPassword) {
				return;
			}

			e.preventDefault();

			encryptData(content.value, encryptPassword).then(c => {
				content.disabled = true;
				content.value = c;
				console.log(content.value, c, encryptPassword);
				console.log('encrypted');
				//form.submit();
			});

			return false;
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
	};

	// Used in _file_render_encrypted.tpl
	window.pleaseDecrypt = () => {
		askPassword();
		decrypt();
	};

	var decrypt = async function ()	{
		if (!encryptPassword) {
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
			// Legacy encryption
			if (content.substr(0, 4) !== '{wc}') {
				content = await GibberishDecrypt(content, encryptPassword);
			}
			else {
				content = await decryptData(content, encryptPassword);
			}
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

	window.addEventListener('load', () => {
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

	// Helper to derive a strong AES-GCM key using PBKDF2
	async function deriveKey(password, salt) {
		const enc = new TextEncoder();
		const keyMaterial = await crypto.subtle.importKey(
			"raw", enc.encode(password), { name: "PBKDF2" }, false, ["deriveKey"]
		);
		return await crypto.subtle.deriveKey(
			{ name: "PBKDF2", salt: salt, iterations: 210000, hash: "SHA-256" },
			keyMaterial, { name: "AES-GCM", length: 256 }, false, ["encrypt", "decrypt"]
		);
	}

	async function encryptData(text, password) {
		const salt = crypto.getRandomValues(new Uint8Array(16));
		const iv = crypto.getRandomValues(new Uint8Array(12));
		const key = await deriveKey(password, salt);
		const encoded = new TextEncoder().encode(text);

		const ciphertext = await crypto.subtle.encrypt({ name: "AES-GCM", iv: iv }, key, encoded);

		// Combine salt, iv, and ciphertext into a single buffer for Base64 storage
		const payload = new Uint8Array(salt.length + iv.length + ciphertext.byteLength);
		payload.set(salt, 0);
		payload.set(iv, salt.length);
		payload.set(new Uint8Array(ciphertext), salt.length + iv.length);

		return '{wc}' + btoa(String.fromCharCode(...payload));
	}

	async function decryptData(base64Payload, password) {
		const raw = Uint8Array.from(atob(base64Payload.substr(5)), c => c.charCodeAt(0));
		const salt = raw.slice(0, 16);
		const iv = raw.slice(16, 28);
		const data = raw.slice(28);

		const key = await deriveKey(password, salt);
		const decrypted = await crypto.subtle.decrypt({ name: "AES-GCM", iv: iv }, key, data);

		return new TextDecoder().decode(decrypted);
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
} ());