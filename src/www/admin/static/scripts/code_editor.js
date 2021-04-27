(function (){
	g.style('scripts/code_editor.css');
	g.script('scripts/lib/code_editor.min.js', function ()
	{
		var save_btn = document.querySelector('[name=save]');
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

		code.saveFile = function ()
		{
			save_btn.click();
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
			appendButton('fullscreen', 'Plein écran', code.toggleFullscreen);
		}
	});
}());
