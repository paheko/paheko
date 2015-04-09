(function () {
	var wiki_id = window.location.search.match(/id=(\d+)/)[1];

	g.style('scripts/wiki_editor.css');

	g.script('scripts/text_editor.min.js').onload = function () {
		var t = new textEditor('f_contenu');
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
			form.action = g.admin_url + 'wiki/_preview.php?id=' + wiki_id;
			form.style.display = 'none';
			form.method = 'post';
			document.body.appendChild(form);
			form.submit();
			//document.body.removeChild(form);
		};

		var openSyntaxHelp = function ()
		{
			openIFrame(g.admin_url + 'wiki/_syntaxe.html');
		};

		var openFileInsert = function ()
		{
			openIFrame(g.admin_url + 'wiki/_fichiers.php?page=' + wiki_id);
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
		};

		var closeIFrame = function ()
		{
			t.parent.className = t.parent.className.replace(/ iframe$/, '');
			t.iframe.className = 'hidden';
		};


		var appendButton = function (name, title, action, altTitle)
		{
			var btn = document.createElement('input');
			btn.type = 'button';
			btn.title = altTitle ? altTitle : title;
			btn.value = title;
			btn.className = name;
			btn.onclick = function () { action.call(); return false; };

			toolbar.appendChild(btn);
			return btn;
		};

		var wrapTags = function (left, right)
		{
			t.wrapSelection(t.getSelection(), left, right);
			return true;
		};

		appendButton('title', "== Titre", function () { wrapTags("== ", ""); } );
		appendButton('bold', '**gras**', function () { wrapTags('**', '**'); } );
		appendButton('italic', "''italique''", function () { wrapTags("''", "''"); } );
		appendButton('link', "[[lien|http://]]", function () { 
			if (url = window.prompt('Adresse URL ?')) 
				wrapTags("[[", "|" + url + ']]'); 
		} );
		appendButton('icnl file', "📎", openFileInsert, 'Insérer fichier / image');

		appendButton('ext icnl preview', '⎙', openPreview, 'Prévisualiser');

		appendButton('ext icnl help', '❓', openSyntaxHelp, 'Aide sur la syntaxe');
		appendButton('ext fullscreen', 'Plein écran', toggleFullscreen, 'Plein écran');
		appendButton('ext close', 'Fermer', closeIFrame);
		
		t.parent.insertBefore(toolbar, t.parent.firstChild);

		t.shortcuts.push({key: 'F11', callback: toggleFullscreen});
		t.shortcuts.push({ctrl: true, key: 'b', callback: function () { return wrapTags('**', '**'); } });
		t.shortcuts.push({ctrl: true, key: 'g', callback: function () { return wrapTags('**', '**'); } });
		t.shortcuts.push({ctrl: true, key: 'i', callback: function () { return wrapTags("''", "''"); } });

		if (window.location.hash.match(/fullscreen/))
		{
			t.toggleFullscreen();
			window.location.hash = '';
		}
	};
}());