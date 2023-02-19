(function (){
	g.style('scripts/code_editor.css');
	g.script('scripts/lib/text_editor.min.js', () => {
	g.script('scripts/lib/code_editor.min.js', function ()
	{
		const doc_url = 'https://fossil.kd2.org/paheko/wiki?name=Documentation/';
		var save_btn = document.querySelector('[name=save]');
		var code = new codeEditor('f_content');

		code.params.lang = {
			search: "Texte à chercher ?\n(expression régulière autorisée, pour cela commencer par un slash '/')",
			replace: "Texte pour le remplacement ?\n(utiliser $1, $2... pour les captures d'expression régulière)",
			search_selection: "Texte à chercher dans la sélection ?\n(expression régulière autorisée, pour cela commencer par un slash '/')",
			replace_result: "%d occurences trouvées et remplacées.",
			goto: "Aller à la ligne numéro :",
			no_search_result: "Aucun résultat trouvé."
		};

		code.origValue = code.textarea.value;
		code.saved = true;

		code.onlinechange = function () {
			if (!this.textarea.value.match(/\{\{/)) {
				return;
			}

			if ((p = this.parent.querySelector('nav p')) && this.origValue != code.textarea.value)
			{
				toolbar.removeChild(p);
			}

			var line = this.getLine(this.current_line);
			var doc = [{link: 'Brindille', title: 'Brindille'}];

			if (match = line.match(/\{\{:(\w+)/)) {
				doc.push({link: 'Brindille/Fonctions', title: 'Fonction'});
				doc.push({link: 'Brindille/Fonctions#'+match[1], title: match[1]});
			}
			else if (match = line.match(/\{\{#(\w+)/)) {
				doc.push({link: 'Brindille/Sections', title: 'Section'});
				doc.push({link: 'Brindille/Sections#'+match[1], title: match[1]});
			}
			else if (match = line.match(/\{\{(select)/)) {
				doc.push({link: 'Brindille/Sections', title: 'Section'});
				doc.push({link: 'Brindille/Sections#'+match[1], title: match[1]});
			}
			else if (match = line.match(/\|(\w+)/)) {
				doc.push({link: 'Brindille/Filtres', title: 'Filtre'});
				doc.push({link: 'Brindille/Filtres#'+match[1], title: match[1]});
			}

			help.innerHTML = 'Documentation';

			for (var i = 0; i < doc.length; i++)
			{
				help.innerHTML += ' &gt; ';

				if (doc[i].link)
					help.innerHTML += '<a href="' + doc_url + doc[i].link + '" onclick="return !window.open(this.href);">' + doc[i].title + '</a>';
				else if (doc[i].tag)
					help.innerHTML += '<' + tag + '>' + doc[i].title + '</' + tag + '>';
				else
					help.innerHTML += doc[i].title;
			}		return false;

		};

		code.saveFile = function ()
		{
			const data = new URLSearchParams();

			for (const pair of new FormData(this.textarea.form)) {
				data.append(pair[0], pair[1]);
			}

			data.append('save', 1);
			this.textarea.form.classList.add('progressing');

			fetch(this.textarea.form.action + '&js', {
				method: 'post',
				body: data,
			}).then((response) => response.json())
			.then(data => {
				this.textarea.defaultValue = this.textarea.value;

				// Show saved
				let c = document.createElement('p');
				c.className = 'block confirm';
				c.id = 'confirm_saved';
				c.innerText = 'Enregistré';
				c.style.left = '-100%';
				c.style.opacity = '1';
				c.onclick = () => c.remove();

				document.querySelector('.codeEditor').appendChild(c);

				window.setTimeout(() => {
					c.style.left = '';
					this.textarea.form.classList.remove('progressing');
				}, 200);

				window.setTimeout(() => {
					c.style.opacity = 0;
				}, 3000);

				window.setTimeout(() => {
					c.remove();
				}, 5000);
			}).catch(e => { console.log(e); this.textarea.form.querySelector('[type=submit]').click(); } );
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

		var appendButton = function (icon, label, title, action)
		{
			var btn = document.createElement('button');
			btn.type = 'button';
			btn.innerText = label;
			btn.title = title;
			if (icon) {
				btn.setAttribute('data-icon', icon);
			}
			btn.onclick = () => { action.call(code); return false; };

			toolbar.appendChild(btn);
		};

		appendButton('→', 'Enregistrer', 'Enregistrer les modifications', code.saveFile);
		appendButton('🗘', 'Recharger', 'Recharger le fichier (effacer les modifications)', code.resetFile);

		appendButton('🔍', 'Chercher', 'Chercher', code.search);
		appendButton(null, 'Remplacer', 'Chercher et remplacer', code.searchAndReplace);
		appendButton(null, 'Aller à la ligne', 'Aller à la ligne', code.goToLine);

		code.parent.insertBefore(toolbar, code.parent.firstChild);

		code.shortcuts.push({ctrl: true, key: 's', callback: code.saveFile});

		// Cancel Escape to close
		if (window.parent && window.parent.g.dialog) {
			// Always fullscreen in dialogs
			code.toggleFullscreen();

			// Display error message in editor
			if (msg = document.querySelector('p.error, p.confirm'))
			{
				var m = document.createElement('p');
				m.innerHTML = msg.innerHTML;
				m.className = msg.className;
				toolbar.appendChild(m);
				msg.parentNode.removeChild(msg);
			}

			window.parent.g.dialog.preventClose = () => {
				if (code.textarea.value == code.textarea.defaultValue) {
					return false;
				}

				if (window.confirm('Sauvegarder avant de fermer ?')) {
					code.saveFile();
				}

				return false;
			};
		}
		else {
			appendButton(null, 'Plein écran', 'Plein écran', code.toggleFullscreen);
		}

		g.setParentDialogHeight('90%');
	})});
}());
