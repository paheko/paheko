(function () {
	g.style('scripts/web_editor.css');

	const msg_restore = "Il semble que les dernières modifications n'aient pas été enregistrées.\nUne sauvegarde locale a été trouvée.\nFaut-il restaurer la sauvegarde locale ?";

	function showSaved() {
		let c = document.createElement('p');
		c.className = 'block confirm';
		c.id = 'confirm_saved';
		c.innerText = 'Enregistré';
		c.style.opacity = 0;

		document.querySelector('#f_content').parentNode.appendChild(c);

		window.setTimeout(() => {
			c.style.opacity = 1;
		}, 100);

		window.setTimeout(() => {
			c.style.opacity = 0;
		}, 3000);

		window.setTimeout(() => {
			c.remove();
		}, 3500);
	}

	function init() {
		var t = new textEditor('f_content');
		t.parent = t.textarea.parentNode;

		var config = {
			fullscreen: t.textarea.getAttribute('data-fullscreen') == 1,
			attachments: t.textarea.getAttribute('data-attachments') == 1,
			savebtn: t.textarea.getAttribute('data-savebtn'),
			preview_url: t.textarea.getAttribute('data-preview-url'),
			format: t.textarea.getAttribute('data-format'),
			page_id: t.textarea.getAttribute('data-id')
		};

		// Use localStorage backup, per path
		var backup_key = 'backup_' + location.search;

		var preventClose = (e) => {
			if (t.textarea.value.trim() == t.textarea.defaultValue.trim()) {
				return;
			}

			e.preventDefault();
			e.returnValue = '';
			return true;
		};

		// Warn before closing window if content was changed
		window.addEventListener('beforeunload', preventClose, { capture: true });

		var submitted = false;

		t.textarea.form.addEventListener('submit', (e) => {
			window.removeEventListener('beforeunload', preventClose, {capture: true});

			if (!submitted) {
				// Just in case fetch() fails, then save() will trigger a regular form submit
				submitted = true;

				save((data) => { location.href = data.redirect; });
				e.preventDefault();
				return false;
			}

		});

		// Cancel Escape to close.value
		if (window.parent && window.parent.g.dialog) {
			// Always fullscreen in dialogs
			config.fullscreen = true;

			window.parent.g.dialog.preventClose = () => {
				if (t.textarea.value.trim() == t.textarea.defaultValue.trim()) {
					return false;
				}

				if (window.confirm('Sauvegarder avant de fermer ?')) {
					quicksave();
				}

				return false;
			};
		}

		var toolbar = document.createElement('nav');
		toolbar.className = 'te';

		var toggleFullscreen = function (e)
		{
			t.parent.classList.toggle('fullscreen');
			t.fullscreen = true;
			return true;
		};

		if (config.fullscreen) {
			toggleFullscreen();
		}

		var openPreview = function ()
		{
			var pos = t.textarea.scrollTop / t.textarea.scrollHeight;

			openIFrame('');

			t.iframe.style.opacity = .2;

			// Sync scrolling
			t.iframe.onload = () => {
				t.iframe.style.opacity = 1;
				var scroll = pos * t.iframe.contentWindow.document.body.scrollHeight;
				t.iframe.contentWindow.scrollTo(0, scroll);
			};

			var form = document.createElement('form');
			form.appendChild(t.textarea.cloneNode(true));
			form.firstChild.value = t.textarea.value;
			let f = document.createElement('input');
			f.type = 'hidden';
			f.name = 'format';
			f.value = config.format;
			form.appendChild(f);
			form.target = 'editorFrame';
			form.action = config.preview_url;
			form.style.display = 'none';
			form.method = 'post';
			document.body.appendChild(form);
			form.submit();

			return true;
		};

		var openSyntaxHelp = function (new_window)
		{
			let url = config.format != 'skriv' ? 'markdown.html' : 'skriv.html';
			url = g.admin_url + 'static/doc/' + url;

			if (new_window) {
				window.open(url);
				return true;
			}

			g.openFrameDialog(url);
			return true;
		};

		var openFileInsert = function (callback)
		{
			g.openFrameDialog(g.admin_url + 'web/_attach.php?files&_dialog&id=' + config.page_id, {callback});
			return true;
		};

		var openImageInsert = function (callback)
		{
			g.openFrameDialog(g.admin_url + 'web/_attach.php?images&_dialog&id=' + config.page_id, {callback});
			return true;
		};

		window.te_insertFile = function (file)
		{
			var tag = '<<file|'+file+'>>';

			t.insertAtPosition(t.getSelection().start, tag);

			g.closeDialog();
			t.textarea.focus();
		};

		window.te_insertImage = function (file, position, caption)
		{
			var tag = '<<image|' + file;

			if (position)
				tag += '|' + position;

			if (caption)
				tag += '|' + caption;

			tag += '>>';

			t.insertAtPosition(t.getSelection().start, tag);

			g.closeDialog();
			t.textarea.focus();
		};

		var EscapeEvent = function (e) {
			if (e.ctrlKey && e.key.toLowerCase() == 'p') {
				closeIFrame();
				e.preventDefault();
				return false;
			}
		};

		var openIFrame = function(url)
		{
			if (t.iframe && t.iframe.src == t.base_url + url)
			{
				t.iframe.className = '';
				t.parent.className += ' iframe';
				return true;
			}
			else if (t.iframe)
			{
				t.parent.removeChild(t.iframe);
				t.iframe = null;
			}

			var w = t.textarea.offsetWidth,
				h = t.textarea.offsetHeight;

			var iframe = document.createElement('iframe');
			iframe.width = w;
			iframe.height = h;
			iframe.src = url;
			iframe.name = 'editorFrame';
			iframe.frameborder = '0';
			iframe.scrolling = 'yes';

			t.parent.appendChild(iframe);
			t.parent.className += ' iframe';
			t.iframe = iframe;

			document.addEventListener('keydown', EscapeEvent);
		};

		var closeIFrame = function ()
		{
			document.removeEventListener('keydown', EscapeEvent);

			if (!t.iframe) {
				return true;
			}
			t.parent.className = t.parent.className.replace(/ iframe$/, '');
			t.iframe.className = 'hidden';
			t.textarea.focus();
		};


		var appendButton = function (name, title, action, altTitle)
		{
			var btn = document.createElement('button');
			btn.type = 'button';
			btn.title = altTitle ? altTitle : title;

			if (typeof title == 'object') {
				btn.dataset.icon = title.icon;
				btn.innerText = title.label;
			}
			else if ([...title].length == 1) {
				btn.dataset.icon = title;
			}
			else {
				btn.innerText = title;
			}

			btn.className = 'icn-btn ' + name;
			btn.onclick = function () { action.call(); return false; };
			btn.onauxclick = function () { action(true); return false; };

			toolbar.appendChild(btn);
			return btn;
		};

		let applyHeader = () => {
			return wrapTags(config.format != 'skriv' ? '## ' : '== ', '');
		};

		let applyBold = () => {
			return wrapTags('**', '**');
		};

		let applyItalic = () => {
			if (config.format != 'skriv') {
				return wrapTags("_", "_");
			}
			else {
				return wrapTags("''", "''");
			}
		};

		var wrapTags = function (left, right)
		{
			t.wrapSelection(t.getSelection(), left, right);
			return true;
		};

		let insertURL = function () {
			let url = window.prompt('Adresse URL ?');

			if (!url) {
				return true;
			}

			if (config.format != 'skriv') {
				return wrapTags("[", "](" + url + ')');
			}
			else {
				return wrapTags("[[", "|" + url + ']]');
			}
		};

		let findTableCells = function (line) {
			line = line.split('|');
			var out = [];
			var start = 0;

			for (var i = 0; i < line.length; i++) {
				out.push({'start': start, 'end': start + line[i].length + 1});
				start += line[i].length + 1;
			}

			// remove first and last empty items
			return out.slice(1, -1);
		};

		let handleTableNavigation = function (e) {
			var reverse = e.shiftKey;
			var caret = t.getSelection().end;

			if (navigateInTable(t.getSelection().end, e.shiftKey ? -1 : 1)) {
				e.preventDefault();
				return false;
			}

			return true;
		};

		let insertNewRowInTable = function (position, columns_count) {
			t.insertAtPosition(position, "\n" + "|  ".repeat(columns_count + 1).trim(), position + 3);
		};

		let navigateInTable = function (caret, move, insert) {
			var line_start = caret;
			var value = t.textarea.value;

			if (line_start < 0) {
				return false;
			}

			while (line_start > 0 && value.charAt(line_start) !== "\n") {
				line_start--;
			}

			line_start = line_start ? line_start + 1 : 0;
			var line_end = value.indexOf("\n", line_start);

			if (line_end === -1) {
				line_end = value.length;
			}

			var line = value.substring(line_start, line_end);

			if (!line.trim().match(/^\|/) || !line.trim().match(/\|$/)) {
				if (insert) {
					insertNewRowInTable(line_start - 2, insert);
				}

				return false;
			}

			var cells = findTableCells(line);

			// Not a table
			if (!cells.length) {
				return false;
			}

			for (var i = 0; i < cells.length; i++) {
				if (cells[i].start + line_start >= caret) {
					break;
				}
			}

			i = i - 1 + move;

			// previous line
			if (i < 0) {
				line_start -= 3;

				if (line_start <= 0) {
					return false;
				}

				return navigateInTable(line_start, 0);
			}
			// next line
			else if (i === cells.length) {
				if (insert) {
					insertNewRowInTable(line_end, insert);
					return false;
				}

				return navigateInTable(line_end + 1, 1, cells.length);
			}

			var sel_start = line_start + cells[i].start;
			var sel_end = line_start + cells[i].end - 1;

			if (value.charAt(sel_start) === ' ') {
				sel_start++;
			}

			if (value.charAt(sel_end - 1) === ' ' && sel_end > sel_start) {
				sel_end--;
			}

			t.setSelection(sel_start, sel_end);
			return true;
		};

		let pasteHTML = function (html) {
			var dom = (new DOMParser).parseFromString(html, 'text/html');
			var body = dom.documentElement.querySelector('body');
			var text = HTMLToMarkdown(body);

			text = text.replace(/^\h+|\h+$/mg, '', text);
			text = text.replace(/\n\n+/g, "\n\n", text);

			if (text === '') {
				return;
			}

			t.insert(text.trim());
		};

		let HTMLToMarkdown = function(node, parent) {
			if (node.nodeType === node.TEXT_NODE) {
				return node.nodeValue.replace(/\r|\n/g, '').trim();
			}

			var name = node.nodeName.toLowerCase();
			var parent_name = parent ? parent.nodeName.toLowerCase() : null;
			var start = '';
			var end = '';

			if (name === 'strong' || name === 'b') {
				start = ' **';
				end = '** ';
			}
			else if (name === 'em' || name === 'i') {
				start = ' _';
				end = '_ ';
			}
			else if (name === 'td' || name === 'th') {
				start = '| ';
				end = ' ';
			}
			else if (name === 'tr') {
				end = "|\n";
			}
			else if (name === 'br' && parent_name === 'body') {
				end = "\n";
			}
			else if (name === 'p' && parent_name === 'body') {
				end = "\n\n";
			}
			else if (name === 'ol' || name === 'ul') {
				end = "\n";
			}
			else if (name.match(/h\d+/)) {
				start = '#'.repeat(name.substr(1)) + ' ';
			}
			else if (name === 'a') {
				start = ' [';
				end = '](' + node.getAttribute('href') + ') ';
			}
			else if (name === 'li' && parent_name === 'ul') {
				start = '* ';
				end = "\n";
			}
			else if (name === 'li' && parent_name === 'ol') {
				start = '1. ';
				end = "\n";
			}

			var text = start;

			for (var i = 0; i < node.childNodes.length; i++) {
				text += HTMLToMarkdown(node.childNodes[i], node);
			}

			if (name === 'tr') {
				text = text.replace(/\n+/g, " ", text);
				text = text.replace(/\s{2,}/g, ' ', text);
			}
			else if (name === 'table') {
				var rows = text.split("\n");
				text = rows[0] + "\n";
				var columns = (rows[0].match(/\|/g) || []).length || 1;
				text += '| --- '.repeat(columns - 1) + "|\n";
				text += rows.slice(1).join("\n");
			}

			return text + end;
		};

		let save = async function (callback) {
			const data = new URLSearchParams();

			// For encryption
			if (typeof t.textarea.form.onbeforesubmit !== 'undefined') {
				t.textarea.form.onbeforesubmit();
			}

			for (const pair of new FormData(t.textarea.form)) {
				data.append(pair[0], pair[1]);
			}

			data.append('save', 1);

			var r = await fetch(t.textarea.form.action, {
				'method': 'post',
				'body': data,
				'headers': {
					'Accept': 'application/json'
				}
			});

			if (r.ok) {
				// Remove backup text
				localStorage.removeItem(backup_key);
			}

			if (r.status === 204) {
				callback(null);
				return true;
			}

			try {
				const received = await r.json();

				if (!r.ok && !received) {
					throw Error(r.status);
				}

				if (received.message) {
					alert(received.message);
					throw Error(received.message);
				}
				else {
					callback(received);
				}
			}
			catch (e) {
				console.error(e);
				t.textarea.form.querySelector('[type=submit]').click();
			}

			return true;
		};

		const quicksave = () => {
			save((data) => {
				showSaved();
				t.textarea.defaultValue = t.textarea.value;

				if (!data) {
					return;
				}

				let e = t.textarea.form.querySelector('input[name=editing_started]');

				if (e) {
					e.value = data.modified;
				}
			});
			return true;
		};

		let createToolbar = () => {
			appendButton('title', "Titre", applyHeader );
			appendButton('bold', 'Gras', applyBold );
			appendButton('italic', "Italique", applyItalic );
			appendButton('link', "Lien", insertURL);

			if (config.attachments) {
				appendButton('image', "🖻", openImageInsert, 'Insérer image');
				appendButton('file', "📎", openFileInsert, 'Insérer fichier');
				t.shortcuts.push({ctrl: true, shift: true, key: 'i', callback: openImageInsert});
				t.shortcuts.push({ctrl: true, shift: true, key: 'f', callback: openFileInsert});
			}


			if (config.savebtn == 1) {
				appendButton('ext save save-label', 'Enregistrer', quicksave, 'Enregistrer');
			}
			else if (config.savebtn == 2) {
				appendButton('ext save', '⇑', quicksave, 'Enregistrer sans fermer');
			}

			appendButton('ext preview', '👁', openPreview, 'Prévisualiser');
			appendButton('ext help', '❓', openSyntaxHelp, 'Aide sur la syntaxe');

			if (!config.fullscreen) {
				appendButton('ext fullscreen', 'Plein écran', toggleFullscreen, 'Plein écran');
			}


			appendButton('ext close', {icon: '←', label: 'Retour à l\'édition'}, closeIFrame);

			t.parent.insertBefore(toolbar, t.parent.firstChild);
		}

		let toggleFormat = (format) => {
			config.format = format;
		};

		if (config.format.substr(0, 1) == '#') {
			let s = document.querySelector(config.format);
			s.onchange = () => {
				toggleFormat(s.value);
			};
			toggleFormat(s.value);
		}
		else {
			toggleFormat(config.format);
		}

		createToolbar();

		t.shortcuts.push({key: 'F11', callback: toggleFullscreen});
		t.shortcuts.push({ctrl: true, key: 'b', callback: applyBold });
		t.shortcuts.push({ctrl: true, key: 'g', callback: applyBold });
		t.shortcuts.push({ctrl: true, key: 'i', callback: applyItalic });
		t.shortcuts.push({ctrl: true, key: 't', callback: applyHeader });
		t.shortcuts.push({ctrl: true, key: 'l', callback: insertURL});
		t.shortcuts.push({ctrl: true, key: 's', callback: quicksave});
		t.shortcuts.push({ctrl: true, key: 'p', callback: openPreview});
		t.shortcuts.push({key: 'F1', callback: openSyntaxHelp});
		t.shortcuts.push({key: 'Tab', callback: handleTableNavigation});
		t.shortcuts.push({shift: true, key: 'Tab', callback: handleTableNavigation});

		g.setParentDialogHeight('90%');

		const uploadFiles = (files) => {
			var image = false;
			var insert = null;
			const IMAGE_MIME_REGEX = /^image\/(p?jpeg|gif|png)$/i;

			for (var i = 0; i < files.length; i++) {
				if (files[i].type.match(IMAGE_MIME_REGEX)) {
					image = true;
					break;
				}
			}

			var callback = () => {
				var frame = g.dialog.querySelector('iframe').contentWindow;

				if (files === null) {
					if (insert) {
						var thumb = null;

						if (image) {
							frame.document.querySelectorAll('a[data-thumb]').forEach((a) => {
								if (a.dataset.name == insert) {
									thumb = a.dataset.thumb;
								}
							});
						}

						frame.insertHelper({name: insert, image, thumb});
						insert = null;
					}

					return;
				}

				// Add items to upload
				var input = frame.document.querySelector('input[type=file]');
				for (var i = 0; i < files.length; i++) {
					input.addItem(files[i]);
				}

				// Only one file? Just upload directly and then insert
				if (files.length == 1) {
					insert = files[0].name;
					input.form.querySelector('[type=submit]').click();
				}

				files = null;
			};

			if (image) {
				openImageInsert(callback);
			}
			else {
				openFileInsert(callback);
			}
		};

		if (config.attachments) {
			// Paste images
			t.textarea.addEventListener('paste', (e) => {
				if (e.clipboardData.types.includes('text/html')) {
					pasteHTML(e.clipboardData.getData('text/html'));
					e.preventDefault();
					return false;
				}

				let items = e.clipboardData.items;
				let files = [];

				for (var i = 0; i < items.length; i++) {
					var item = items[i];

					if (item.kind !== 'file') {
						continue;
					}

					let f = items[i].getAsFile();
					let name = f.name == 'image.png' ? f.name.replace(/\./, '-' + (+(new Date)) + '.') : f.name;

					files.push(new File([f], name, {type: f.type}));
				}

				if (!files.length) {
					return true;
				}

				e.preventDefault();
				uploadFiles(files);
				return false;
			});

			// Drag and drop images
			t.textarea.form.addEventListener('drop', (e) => {
				const files = [...e.dataTransfer.items].filter(item => item.kind == 'file').map(item => item.getAsFile());

				if (!files.length) return;

				e.preventDefault();
				e.stopPropagation();

				uploadFiles(files);
			});
		}

		window.setTimeout(() => {
			if ((v = localStorage.getItem(backup_key)) && v.trim() !== t.textarea.value.trim() && window.confirm(msg_restore)) {
				t.textarea.value = v;
			}
			else {
				localStorage.removeItem(backup_key);
			}
		}, 50);

		window.setInterval(() => {
			if (t.textarea.value.trim() === t.textarea.defaultValue.trim()) {
				return;
			}

			var v = localStorage.getItem(backup_key);

			if (v && v.trim() === t.textarea.value.trim()) {
				return;
			}

			localStorage.setItem(backup_key, t.textarea.value);
		}, 10000);

	}

	g.onload(() => {
		g.script('scripts/lib/text_editor.js', init);
	});
}());