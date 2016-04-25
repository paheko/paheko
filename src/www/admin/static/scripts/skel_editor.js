(function (){
	g.style('scripts/skel_editor.css');
	g.script('scripts/code_editor.min.js').onload = function ()
	{
		var save_btn = document.querySelector('input[name=save]');
		save_btn.type = 'hidden';

		var code = new codeEditor('f_content');

		code.params.lang = {
			search: "Texte à chercher ?\n(expression régulière autorisée, pour cela commencer par un slash '/')",
			replace: "Texte pour le remplacement ?\n(utiliser $1, $2... pour les captures d'expression régulière)",
			search_selection: "Texte à chercher dans la sélection ?\n(expression régulière autorisée, pour cela commencer par un slash '/')",
			replace_result: "%d occurences trouvées et remplacées.",
			goto: "Aller à la ligne :",
			no_search_result: "Aucun résultat trouvé."
		};

		code.origValue = code.textarea.value;
		code.saved = true;

		code.onlinechange = function () {
			if ((p = this.parent.querySelector('nav p')) && this.origValue != code.textarea.value)
			{
				toolbar.removeChild(p);
			}

			var line = this.getLine(this.current_line);
			var doc = [];

			if (match = line.match(/<BOUCLE(\d+|_[a-zA-Z0-9_-]+)\(([A-Z]+)\)(.*?)>/))
			{
				doc.push({link: 'Boucles', title: 'BOUCLE'});
				doc.push({link: 'Boucle-'+match[2], title: match[2]});

				if (match[3])
				{
					if (match[3].match(/\{".*"\}/))
						doc.push({link: 'Critere-inter', title: 'Critère inter-résultat {"..."}'});
					if (match[3].match(/\{\d+(,\d+)?\}/))
						doc.push({link: 'Critere-de-nombre', title: 'Critère de nombre {X,Y}'});
					if (match[3].match(/\{par\s+.*\}/))
						doc.push({link: 'Critere-d-ordre', title: 'Critère d\'ordre {par champ}'});
					if (match[3].match(/\{inverse\}/))
						doc.push({link: 'Critere-inverse', title: 'Critère {inverse}'});
				}
			}

			if (match = line.match(/<INCLURE\{(.*?)\}>/))
			{
				doc.push({link: 'Inclure', title: 'Inclusion du fichier ' + match[1]});
			}

			if (match = line.match(/#[A-Z0-9_]+(\*?(\|.*?)?\).*?\])?/g))
			{
				for (var i = 0; i < match.length; i++)
				{
					var tag = match[i].match(/(#[A-Z0-9_]+)(\*?(\|(.*?))?\).*?\])?/);
					doc.push({title: 'Balise ' + tag[1]});
					
					if (typeof tag[4] != 'undefined')
					{
						var tag = tag[4].split('|');
						for (var j = 0; j < tag.length; j++)
						{
							var end = tag[j].indexOf('{');
							end = (end == -1) ? tag[j].length : end;
							var f = tag[j].substr(0, end);
							doc.push({link: 'Filtre-'+f, title: 'Filtre '+f});
						}
					}
				}
			}

			help.innerHTML = '';

			for (var i = 0; i < doc.length; i++)
			{
				help.innerHTML += ' | ';

				if (doc[i].link)
					help.innerHTML += '<a href="' + doc_url + '#' + doc[i].link + '" onclick="return !window.open(this.href);">' + doc[i].title + '</a>';
				else if (doc[i].tag)
					help.innerHTML += '<' + tag + '>' + doc[i].title + '</' + tag + '>';
				else
					help.innerHTML += doc[i].title;
			}		return false;

		};

		code.saveFile = function (e)
		{
			if (this.fullscreen)
				this.textarea.form.action += '&fullscreen';

			this.textarea.form.submit();
		};

		code.loadFile = function (e)
		{
			var file = e.target.value;

			if (file == skel_current) return;

			if (code.textarea.value != code.origValue &&
				!window.confirm("Le fichier a été modifié, abandonner les modifications ?"))
			{
				for (var i = 0; i < e.target.options.length; i++)
				{
					e.target.options[i].selected = false;

					if (e.target.options[i].value == skel_current)
					{
						e.target.options[i].selected = true;
					}
				}

				return false;
			}

			var url = garradin.admin_url + 'config/site.php?edit=' + encodeURIComponent(file);

			window.location.href = url + (code.fullscreen ? '#fullscreen' : '');

			return true;
		};

		code.resetFile = function (e)
		{
			if (this.textarea.value == this.origValue) return;
			if (!window.confirm("Le fichier a été modifié, abandonner les modifications ?")) return;
			this.textarea.form.reset();
		};

		var help = document.createElement('div');
		help.className = 'sk_help';

		code.parent.appendChild(help);

		var toolbar = document.createElement('nav');
		toolbar.className = 'sk_toolbar';

		var appendButton = function (name, title, action)
		{
			var btn = document.createElement('input');
			btn.type = 'button';
			btn.value = btn.title = title;
			btn.className = name;
			btn.onclick = function () { action.call(code); return false; };

			toolbar.appendChild(btn);
		};

		appendButton('save', 'Enregistrer les modifications', code.saveFile);
		appendButton('reset', 'Recharger le fichier (effacer les modifications)', code.resetFile);

		appendButton('search', 'Chercher', code.search);
		appendButton('search_replace', 'Chercher et remplacer', code.searchAndReplace);
		appendButton('gotoline', 'Aller à la ligne', code.goToLine);
		appendButton('fullscreen', 'Plein écran', code.toggleFullscreen);
		
		var sel = document.createElement('select');
		sel.title = 'Charger un autre fichier';
		sel.onchange = code.loadFile;

		for (var i in skel_list)
		{
			if (!skel_list.hasOwnProperty(i))
				continue;

			var skel = i;
			var opt = document.createElement('option');
			opt.value = skel;
			opt.innerHTML = skel;
			opt.selected = (skel == skel_current) ? true : false;
			sel.appendChild(opt);
		}

		toolbar.appendChild(sel);

		code.parent.insertBefore(toolbar, code.parent.firstChild);

		if (window.location.hash.match(/fullscreen/))
		{
			code.toggleFullscreen();

			if (msg = document.querySelector('p.error, p.confirm'))
			{
				var m = document.createElement('p');
				m.innerHTML = msg.innerHTML;
				m.className = msg.className;
				toolbar.appendChild(m);
				msg.parentNode.removeChild(msg);
			}

			window.location.hash = '';
		}
	};
}());
