var textarea;
var initial_value;
var save_button;
var modified = false;

function createCodeEditor(lang, selector) {
	g.style('scripts/lib/prism/prism_editor.css');
	g.script('scripts/lib/prism/prism_editor.js', () => {
		textarea = document.querySelector(selector);
		textarea.style.display = 'none';

		// This is to avoid an empty editor (bug in prism editor)
		if (textarea.value === '') {
			textarea.value = "\n";
		}

		const editor = createEditor('#editor', {
			language: lang,
			value: textarea.value,
			wordWrap: true,
			insertSpaces: false,
			tabSize: 4,
			onSelectionChange: updateDocHint
		});

		editor.textarea.name = textarea.name;
		textarea.parentNode.remove();
		textarea = editor.textarea;

		window.setTimeout(() => textarea.focus(), 500);

		initial_value = textarea.value;
		save_button = textarea.form.querySelector('nav button[type="submit"]');

		textarea.addEventListener('input', updateSaveStatus);

		window.addEventListener('keydown', e => {
			if (e.ctrlKey && e.key.toLowerCase() === 's') {
				if (modified) {
					save();
				}

				e.preventDefault();
				return false;
			}
		});

		updateSaveStatus();

		textarea.form.onsubmit = (e) => {
			save();
			e.preventDefault();
			return false;
		};

		window.addEventListener('beforeunload', preventClose, { capture: true });

		if (window.parent && window.parent.g.dialog) {
			window.parent.g.toggleDialogFullscreen();

			// Cancel Escape to close, this interacts badly with escape in Ctrl+F
			window.parent.g.dialog_options.escape_to_close = false;

			window.parent.g.dialog.preventClose = () => {
				if (!modified) {
					return false;
				}

				if (window.confirm("Le contenu a été modifié.\nSauvegarder avant de fermer ?")) {
					save();
				}

				return false;
			};
		}
	});
}

// Warn before closing window if content was changed
function preventClose(e) {
	if (!modified) {
		return;
	}

	e.preventDefault();
	e.returnValue = '';
	return true;
}

function updateDocHint(args) {
	const doc_url = g.admin_url + 'static/doc/';
	const pos = args[0];

	if (!textarea.value.match('{' + '{')) {
		return;
	}

	var help = document.querySelector('#help');
	var open = 0;
	var close = 0;

	for (var i = pos; i > 0; i--) {
		var char = textarea.value.charAt(i);

		if (char === '{') {
			open++;
		}
		else if (char === '}') {
			close++;
		}
		else {
			open = 0;
			close = 0;
		}

		if (open === 2 || close === 2) {
			break;
		}
	}

	if (close === 2 || open !== 2) {
		help.innerHTML = '<ul></ul>';
		return;
	}

	var tag = textarea.value.substring(i + open, textarea.value.indexOf('}}', pos) || textarea.value.length);

	var doc = [{link: 'brindille.html', title: 'Brindille'}];

	if (match = tag.match(/^:(\w+)/)) {
		doc.push({link: 'brindille_functions.html', title: 'Fonctions', class: 'general'});
		doc.push({link: 'brindille_functions.html#'+match[1], title: '{{:' + match[1] + '}}', class: 'function'});
	}
	else if (match = tag.match(/^#(\w+)/)) {
		doc.push({link: 'brindille_sections.html', title: 'Sections', class: 'general'});
		doc.push({link: 'brindille_sections.html#'+match[1], title: '{{#' + match[1] + '}}', class: 'section'});
	}
	else if (match = tag.match(/^(select)/i)) {
		doc.push({link: 'brindille_sections.html', title: 'Sections', class: 'general'});
		doc.push({link: 'brindille_sections.html#'+match[1], title: '{{#' + match[1] + '}}', class: 'section'});
	}

	if (f = tag.match(/\|\w+/g)) {
		doc.push({link: 'brindille_modifiers.html', title: 'Filtres', class: 'general'});

		f = Array.from(new Set(f));

		for (var i = 0; i < f.length; i++) {
			var name = f[i].substring(1);
			doc.push({link: 'brindille_modifiers.html#'+name, title: name, class: 'modifier'});
		}
	}

	help.innerHTML = '<ul>';

	for (var i = 0; i < doc.length; i++) {
		help.innerHTML += '<li><a href="' + doc_url + doc[i].link + '" onclick="g.openFrameDialog(this.href); return false;" class="' + doc[i].class + '">' + doc[i].title + '</a></li>';
	}

	help.innerHTML += '</ul>';
}

async function save () {
	const data = new URLSearchParams();

	for (const pair of new FormData(textarea.form)) {
		data.append(pair[0], pair[1]);
	}

	data.append('save', 1);
	textarea.form.classList.add('progressing');

	var r = await fetch(textarea.form.action, {
		'method': 'post',
		'body': data,
		'headers': {
			'Accept': 'application/json'
		}
	});

	if (!r.ok) {
		console.log(r);
		const data = await r.json();
		console.error(data);

		if (data.message) {
			alert(data.message);
		}
		else if (!data.success) {
			throw Error('Invalid response');
		}

		this.textarea.form.querySelector('[type=submit]').click();
		return;
	}

	initial_value = this.textarea.value;
	updateSaveStatus();
	textarea.form.classList.remove('progressing');
}

function updateSaveStatus()
{
	modified = initial_value !== textarea.value;
	textarea.form.classList.toggle('modified', modified);
	save_button.disabled = !modified;

	if (!modified) {
		save_button.innerText = 'Enregistré ✓';
	}
	else {
		save_button.innerText = 'Enregistrer';
	}
}
