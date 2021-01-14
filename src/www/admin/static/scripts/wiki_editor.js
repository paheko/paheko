(function () {
	var wiki_id = window.location.search.match(/id=(\d+)/)[1];

	g.onload(function () {
		if (location.hash == '#saved') {
			location.hash = '';

			let c = document.createElement('p');
			c.className = 'block confirm';
			c.id = 'confirm_saved';
			c.innerText = 'Enregistré';
			c.style.right = '-10em';

			document.querySelector('#f_content').parentNode.appendChild(c);

			window.setTimeout(() => {
				c.style.right = '';
			}, 200);

			window.setTimeout(() => {
				c.style.opacity = 0;
			}, 3000);
		}

		g.script('scripts/text_editor.min.js', function () {
			var t = new textEditor('f_content');
			t.parent = t.textarea.parentNode;

			var toolbar = document.createElement('nav');
			toolbar.className = 'te';

			var toggleFullscreen = function (e)
			{
				var classes = t.parent.className.split(' ');

				for (var i = 0; i < classes.length; i++)
				{
					if (classes[i] == 'fullscreen')
					{
						classes.splice(i, 1);
						t.parent.className = classes.join(' ');
						t.fullscreen = false;
						return true;
					}
				}

				classes.push('fullscreen');
				t.parent.className = classes.join(' ');
				t.fullscreen = true;
				return true;
			};

			var openPreview = function ()
			{
				openIFrame('');
				var form = document.createElement('form');
				form.appendChild(t.textarea.cloneNode(true));
				form.firstChild.value = t.textarea.value;
				form.target = 'editorFrame';
				form.action = g.admin_url + 'web/_preview.php?id=' + wiki_id;
				form.style.display = 'none';
				form.method = 'post';
				document.body.appendChild(form);
				form.submit();
				//document.body.removeChild(form);
				return true;
			};

			var openSyntaxHelp = function ()
			{
				openIFrame(g.admin_url + 'web/_syntaxe.html');
				return true;
			};

			var openFileInsert = function ()
			{
				openIFrame(g.admin_url + 'web/_attach.php?page=' + wiki_id);
				return true;
			};

			window.te_insertFile = function (file)
			{
				var tag = '<<fichier|'+file+'>>';

				t.insertAtPosition(t.getSelection().start, tag);

				closeIFrame();
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

				closeIFrame();
			};

			var EscapeEvent = function (e) {
				if (e.key == 'Escape') {
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
			};


			var appendButton = function (name, title, action, altTitle)
			{
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.title = altTitle ? altTitle : title;
				if (title.length == 1) {
					btn.dataset.icon = title;
				}
				else {
					btn.innerText = title;
				}
				btn.className = 'icn-btn ' +name;
				btn.onclick = function () { action.call(); return false; };

				toolbar.appendChild(btn);
				return btn;
			};

			var wrapTags = function (left, right)
			{
				t.wrapSelection(t.getSelection(), left, right);
				return true;
			};

			let insertURL = function () {
				if (url = window.prompt('Adresse URL ?'))
					wrapTags("[[", "|" + url + ']]');
				return true;
			};

			let save = function () {
				t.textarea.form.action = t.textarea.form.action.replace(/&pos=\d+/, '');
				t.textarea.form.action += '&pos=' + t.getSelection().start;
				t.textarea.form.querySelector('[name="save"]').click();
				return true;
			};

			appendButton('title', "== Titre", function () { wrapTags("== ", ""); } );
			appendButton('bold', '**gras**', function () { wrapTags('**', '**'); } );
			appendButton('italic', "''italique''", function () { wrapTags("''", "''"); } );
			appendButton('link', "[[lien|http://]]", insertURL);
			appendButton('file', "📎", openFileInsert, 'Insérer fichier / image');

			appendButton('ext preview', '👁', openPreview, 'Prévisualiser');

			appendButton('ext help', '❓', openSyntaxHelp, 'Aide sur la syntaxe');
			appendButton('ext fullscreen', 'Plein écran', toggleFullscreen, 'Plein écran');
			appendButton('ext close', 'Fermer', closeIFrame);

			t.parent.insertBefore(toolbar, t.parent.firstChild);

			t.shortcuts.push({key: 'F11', callback: toggleFullscreen});
			t.shortcuts.push({ctrl: true, key: 'b', callback: function () { return wrapTags('**', '**'); } });
			t.shortcuts.push({ctrl: true, key: 'g', callback: function () { return wrapTags('**', '**'); } });
			t.shortcuts.push({ctrl: true, key: 'i', callback: function () { return wrapTags("''", "''"); } });
			t.shortcuts.push({ctrl: true, key: 't', callback: function () { return wrapTags("\n== ", "\n"); } });
			t.shortcuts.push({ctrl: true, key: 'l', callback: insertURL});
			t.shortcuts.push({ctrl: true, key: 's', callback: save});
			t.shortcuts.push({ctrl: true, shift: true, key: 'i', callback: openFileInsert});
			t.shortcuts.push({ctrl: true, shift: true, key: 'p', callback: openPreview});
			t.shortcuts.push({key: 'F1', callback: openSyntaxHelp});

			if (m = window.location.search.match(/pos=(\d+)/))
			{
				t.textarea.focus();
				t.setSelection(m[1], m[1]);
				window.location.hash = '';
			}
		});
	});
}());