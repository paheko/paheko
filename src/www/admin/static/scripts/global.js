(function () {
	let d = document.documentElement.dataset;
	window.g = window.garradin = {
		admin_url: d.url,
		static_url: d.url + 'static/',
		version: d.version,
		loaded: {}
	};

	window.$ = function(selector) {
		if (!selector.match(/^[.#]?[a-z0-9_-]+$/i))
		{
			return document.querySelectorAll(selector);
		}
		else if (selector.substr(0, 1) == '.')
		{
			return document.getElementsByClassName(selector.substr(1));
		}
		else if (selector.substr(0, 1) == '#')
		{
			return document.getElementById(selector.substr(1));
		}
		else
		{
			return document.getElementsByTagName(selector);
		}
	};

	if (!document.querySelectorAll)
	{
		return;
	}

	g.onload = function(callback, dom)
	{
		if (typeof dom == 'undefined')
			dom = true;

		var eventName = dom ? 'DOMContentLoaded' : 'load';

		document.addEventListener(eventName, callback, false);
	};

	g.toggle = function(selector, visibility, resize_parent)
	{
		if (!('classList' in document.documentElement))
			return false;

		if (selector instanceof Array)
		{
			for (var i = 0; i < selector.length; i++)
			{
				g.toggle(selector[i], visibility, false);
			}

			if (resize_parent !== false) {
				g.resizeParentDialog();
			}

			return true;
		}
		else if (selector instanceof HTMLElement) {
			var elements = [selector];
		}
		else {
			var elements = document.querySelectorAll(selector);
		}

		for (var i = 0; i < elements.length; i++) {
			elements[i].classList.toggle('hidden', visibility ? false : true);

			// Make sure hidden elements are not really required
			// Avoid Chrome bug "An invalid form control with name='' is not focusable."
			elements[i].querySelectorAll('input[required], textarea[required], select[required], button[required]').forEach((e) => {
				e.disabled = !visibility ? true : (e.getAttribute('disabled') ? true : false);
			});
		}

		if (resize_parent !== false) {
			g.resizeParentDialog();
		}

		return true;
	};

	g.script = function (file, callback) {
		if (file in g.loaded) {
			callback();
			return;
		}

		var script = g.loaded[file] = document.createElement('script');
		script.type = 'text/javascript';
		script.src = this.static_url + file + '?' + g.version;
		script.onload = callback;
		document.head.appendChild(script);
	};

	g.style = function (file) {
		var link = document.createElement('link');
		link.rel = 'stylesheet';
		link.type = 'text/css';
		link.href = this.static_url + file + '?' + g.version;
		return document.head.appendChild(link);
	};

	g.dialog = null;
	g.focus_before_dialog = null;
	g.dialog_on_close = false;

	g.openDialog = function (content, options) {
		var close = true,
			callback = null,
			classname = null;

		if (typeof options === "object" && options !== null) {
			callback = options.callback ?? null;
			classname = options.classname ?? null;
			close = options.close ?? true;
			g.dialog_on_close = options.on_close || false;
		}

		if (null !== g.dialog) {
			g.closeDialog();
		}

		g.focus_before_dialog = document.activeElement;

		g.dialog = document.createElement('dialog');
		g.dialog.id = 'dialog';
		g.dialog.open = true;
		g.dialog.className = classname || '';

		if (close) {
			var btn = document.createElement('button');
			btn.className = 'icn-btn closeBtn';
			btn.setAttribute('data-icon', '✘');
			btn.type = 'button';
			btn.innerHTML = 'Fermer';
			btn.onclick = g.closeDialog;
			g.dialog.appendChild(btn);

			g.dialog.onclick = (e) => { if (e.target == g.dialog) g.closeDialog(); };
			window.onkeyup = (e) => { if (e.key == 'Escape') g.closeDialog(); };
		}

		if (typeof content == 'string') {
			var container = document.createElement('div');
			container.innerHTML = content;
			content = container;
		}
		else if (content instanceof DocumentFragment) {
			var container = document.createElement('div');
			container.appendChild(content.cloneNode(true));
			content = container;
		}

		g.dialog.appendChild(content);

		g.dialog.style.opacity = 0;

		let tag = content.tagName.toLowerCase();

		if (tag == 'img' || tag == 'iframe') {
			event = 'load';
		}
		else if (tag == 'audio' || tag == 'video') {
			event = 'canplaythrough';
		}

		if (event) {
			content.addEventListener(event, () => { if (g.dialog) g.dialog.classList.add('loaded'); });

			if (event && callback) {
				content.addEventListener(event, callback);
			}
		}
		else {
			g.dialog.classList.add('loaded');
		}

		document.body.appendChild(g.dialog);

		// Restore CSS defaults
		window.setTimeout(() => { g.dialog.style.opacity = ''; }, 50);

		return content;
	}

	g.openFrameDialog = function (url, options) {
		options = options ?? {};
		options.height = options.height || 'auto';
		options.callback = options.callback || null;
		options.classname = options.classname || null;

		var iframe = document.createElement('iframe');
		iframe.src = url;
		iframe.name = 'dialog';
		iframe.id = 'frameDialog';
		iframe.frameborder = '0';
		iframe.scrolling = 'yes';
		iframe.width = iframe.height = 0;
		iframe.setAttribute('data-height', options.height);

		iframe.addEventListener('load', () => {
			iframe.contentWindow.onkeyup = (e) => { if (e.key == 'Escape') g.closeDialog(); };

			if (iframe.parentNode.className) {
				return;
			}

			// We need to wait a bit for the height to be correct, not sure why
			window.setTimeout(() => {
				iframe.style.height = iframe.dataset.height == 'auto' ? iframe.contentWindow.document.body.offsetHeight + 'px' : iframe.dataset.height;
			}, 200);
		});

		g.openDialog(iframe, options);
		return iframe;
	};

	g.reloadParentDialog = () => {
		if (typeof window.parent.g === 'undefined' || !window.parent.g.dialog) {
			return;
		}

		location.href = window.parent.g.dialog.querySelector('iframe').getAttribute('src');
	};

	g.setParentDialogHeight = (height) => {
		if (typeof window.parent.g === 'undefined' || !window.parent.g.dialog) {
			return;
		}

		window.parent.g.dialog.querySelector('iframe').setAttribute('data-height', height);
		g.resizeParentDialog(height);
	};

	g.toggleDialogFullscreen = () => {
		g.dialog.classList.add('fullscreen')
		g.dialog.childNodes[1].style.height = null;
	};

	g.resizeParentDialog = (forced_height) => {
		if (typeof window.parent.g === 'undefined' || !window.parent.g.dialog) {
			return;
		}

		let height;

		if (forced_height) {
			height = forced_height;
		}
		else {
			let body_height = document.body.offsetHeight;
			let parent_height = window.parent.innerHeight;

			if (body_height > parent_height * 0.9) {
				height = '90%';
			}
			else {
				height = body_height + 'px';
			}
		}

		window.parent.g.dialog.childNodes[1].style.height = height;
	};

	g.closeDialog = function () {
		if (null === g.dialog) {
			return;
		}

		if (g.dialog.preventClose && g.dialog.preventClose()) {
			return false;
		}

		if (g.dialog_on_close) {
			location.href = g.dialog_on_close == true ? location.href : g.dialog_on_close.replace(/!/, g.admin_url);
			return;
		}

		var d = g.dialog;
		d.style.opacity = 0;
		window.onkeyup = g.dialog = null;

		window.setTimeout(() => { d.parentNode.removeChild(d); }, 500);

		if (g.focus_before_dialog) {
			g.focus_before_dialog.focus();
		}
	};

	g.checkUncheck = function()
	{
		var checked = this.checked;
		this.form.querySelectorAll('tbody input[type=checkbox]').forEach((elm) => {
			elm.checked = checked;
			elm.dispatchEvent(new Event("change"));
		});

		this.form.querySelectorAll('thead input[type=checkbox], tfoot input[type=checkbox]').forEach((elm) => {
			elm.checked = checked;
		});

		return true;
	};

	g.togglePasswordVisibility = (field, repeat, show) => {
		if (typeof show == 'undefined') {
			show = field.type.toLowerCase() == 'password';
		}

		var btn = field.nextSibling;

		if (!btn) {
			throw Error('button not found');
		}

		field.type = show ? 'text' : 'password';
		btn.dataset.icon = !show ? '👁' : '⤫';
		btn.innerHTML = !show ? 'Voir le mot de passe' : 'Cacher le mot de passe';
		field.classList.toggle('clearTextPassword', !show);

		if (repeat) {
			repeat.type = field.type;
			repeat.classList.toggle('clearTextPassword', !show);
		}
	};

	/**
	 * Adds a "show password" button next to password inputs
	 */
	g.enhancePasswordField = function (field)
	{
		if (field.id.indexOf('_confirmed') != -1) {
			return;
		}

		var show_password = document.createElement('button');
		show_password.type = 'button';
		show_password.className = 'icn-btn';

		field.parentNode.insertBefore(show_password, field.nextSibling);

		let repeat_field = document.getElementById(field.id + '_confirmed');

		g.togglePasswordVisibility(field, repeat_field, false);

		show_password.onclick = function (e) {
			var pos = field.selectionStart;

			g.togglePasswordVisibility(field, repeat_field);

			// Remettre le focus sur le champ mot de passe
			// on ne peut pas vraiment remettre le focus sur le champ
			// précis qui était utilisé avant de cliquer sur le bouton
			// car il faudrait enregistrer les actions onfocus de tous
			// les champs de la page
			field.focus();
			field.selectionStart = field.selectionEnd = pos;
		};
	};

	g.enhanceDateField = (input) => {
		var span = document.createElement('span');
		span.className = 'datepicker-parent';
		var btn = document.createElement('button');
		var cal = null;
		btn.className = 'icn-btn';
		btn.title = 'Cliquer pour ouvrir le calendrier. Utiliser les flèches du clavier pour sélectionner une date, et page précédente suivante pour changer de mois.';
		btn.setAttribute('data-icon', '📅');
		btn.type = 'button';
		btn.onclick = () => {
			g.script('scripts/lib/datepicker2.min.js', () => {
				if (null == cal) {
					btn.onclick = null;
					cal = new DatePicker(btn, input, {lang: 'fr', format: 1});
					cal.open();
				}
			});
		};
		span.appendChild(btn);
		input.parentNode.insertBefore(span, input.nextSibling);

		const getCaretPosition = e => e && e.selectionStart || -1;

		const inputKeyEvent = (e) => {
			if (input.value.match(/^\d$|^\d\d?\/\d$/) && e.key.match(/^[0-9]$/)) {
				input.value += e.key + '/';
				e.preventDefault();
				return false;
			}

			if (e.key == '/' && input.value.slice(-1) == '/') {
				e.preventDefault();
				return false;
			}

		};
		input.addEventListener('keydown', inputKeyEvent, true);
	};

	g.current_list_input = null;

	g.inputListSelected = function(value, label) {
		var i = g.current_list_input;

		if (!i) {
			throw Error('Parent input list not found');
		}

		var can_delete = i.firstChild.getAttribute('data-can-delete');
		var multiple = i.firstChild.getAttribute('data-multiple');
		var name = i.firstChild.getAttribute('data-name');

		var span = document.createElement('span');
		span.className = 'label';
		span.innerHTML = '<input type="hidden" name="' + name + '[' + value + ']" value="' + label + '" />' + label;

		// Add delete button
		if (can_delete == 1) {
			var btn = document.createElement('button');
			btn.className = 'icn-btn';
			btn.type = 'button';
			btn.setAttribute('data-icon', '✘');
			btn.onclick = () => span.parentNode.removeChild(span);
			span.appendChild(btn);
		}

		if (!multiple && (old = i.querySelector('span'))) {
			i.removeChild(old);
		}

		i.appendChild(span);
		g.closeDialog();
		i.firstChild.focus();
	};

	g.formatMoney = (v) => {
		if (!v) {
			return '0,00';
		}

		var s = v < 0 ? '-' : '';
		v = '' + Math.abs(v);
		return s + (v.substr(0, v.length-2) || '0') + ',' + ('00' + v).substr(-2);
	};

	g.getMoneyAsInt = (v) => {
		v = v.replace(/[^0-9.,]/, '');
		if (v.length == 0) return;

		v = v.split(/[,.]/);
		var d = v.length == 2 ? v[1] : '0';
		v = v[0] + (d + '00').substr(0, 2);
		v = parseInt(v, 10);
		return v;
	};

	// Focus on first form input when loading the page
	g.onload(() => {
		if (!document.activeElement || document.activeElement.tagName.toLowerCase() == 'body') {
			let form = document.querySelector('form[data-focus]');

			if (!form) {
				return;
			}

			let f = form.dataset.focus;
			let n = f.match(/^\d+$/) ? (parseInt(f, 10) - 1) : null;
			let i = form.querySelectorAll(n !== null ? '[name]:not([type="hidden"]):not([readonly]):not([type=button])' : f);

			if (n !== null && i[n]) {
				i[n].focus();
			}
			else if (n === null && i[0]) {
				i[0].focus();
			}
		}
	});

	// Sélecteurs de listes
	g.onload(() => {
		var inputs = $('form .input-list > button');

		inputs.forEach((i) => {
			i.onclick = () => {
				i.setCustomValidity('');
				g.current_list_input = i.parentNode;
				let url = i.value + (i.value.indexOf('?') > 0 ? '&' : '?') + '_dialog';
				g.openFrameDialog(url);
				return false;
			};
		});

		// Set custom error message if required list is not selected
		document.querySelectorAll('form').forEach((form) => {
			form.addEventListener('submit', (e) => {
				let inputs = form.querySelectorAll('.input-list > button[required]');

				for (var k = 0; k < inputs.length; k++) {
					var i2 = inputs[k];

					// Element is hidden or disabled
					if (!i2.offsetParent || i2.disabled) {
						i2.required = false;
						continue;
					}

					let v = i2.parentNode.querySelector('input[type="hidden"]:nth-child(1)');

					if (!v || !v.value) {
						// Force button to have error message, <button type="button"> cannot show validity message
						i2.type = 'submit';
						i2.setCustomValidity('Merci de faire une sélection.');
						i2.reportValidity();
						e.preventDefault();
						return false;
					}
				}
			});
		});

		var multiples = $('form .input-list span button');

		multiples.forEach((btn) => {
			btn.onclick = () => btn.parentNode.parentNode.removeChild(btn.parentNode);
		});

		// Open links in dialog
		$('a[target*="_dialog"]').forEach((e) => {
			e.onclick = () => {
				let type = e.getAttribute('data-mime');

				if (!type) {
					let url = e.href + (e.href.indexOf('?') > 0 ? '&' : '?') + '_dialog';

					if (m = e.getAttribute('target').match(/_dialog=(.*)/)) {
						url += '=' + m[1];
					}

					if (location.href.match(/_dialog/)) {
						location.href = url;
						return false;
					}

					g.openFrameDialog(url, {
						'height': e.getAttribute('data-dialog-height') || 'auto',
						'classname': e.getAttribute('data-dialog-class'),
						'on_close': e.getAttribute('data-dialog-on-close') == 1
					});
					return false;
				}

				if (type.match(/^image\//)) {
					var i = document.createElement('img');
					i.src = e.href;
					i.draggable = false;
				}
				else if (type.match(/^audio\//)) {
					var i = document.createElement('audio');
					i.autoplay = true;
					i.controls = true;
					i.src = e.href;
					i.draggable = false;
				}
				else if (type.match(/^video\/|^application\/ogg$/)) {
					var i = document.createElement('video');
					i.autoplay = true;
					i.controls = true;
					i.src = e.href;
					i.draggable = false;
				}
				else {
					let url = e.href + (e.href.indexOf('?') > 0 ? '&' : '?') + '_dialog';
					g.openFrameDialog(url, {height: '90%'});
					return false;
				}

				g.openDialog(i);

				return false;
			};
		});

		$('form[target="_dialog"]').forEach((e) => {
			e.addEventListener('submit', () => {
				if (e.target != '_dialog' && e.target != 'dialog') return;

				let url = e.getAttribute('action');
				url = url + (url.indexOf('?') > 0 ? '&' : '?') + '_dialog';
				e.setAttribute('action', url);
				e.target = 'dialog';

				g.openFrameDialog('about:blank', {height: e.getAttribute('data-dialog-height') ? 90 : 'auto'});
				e.submit();
				return false;
			});
		});
	});

	g.onload(() => {
		document.querySelectorAll('input[data-input="date"]').forEach((e) => {
			g.enhanceDateField(e);
		});
	});

	g.onload(() => {
		document.querySelectorAll('input[type="password"]:not([readonly]):not([disabled]):not(.hidden)').forEach((e) => {
			g.enhancePasswordField(e);
		});
	});

	g.onload(() => {
		if (document.querySelector('input[type="file"][data-enhanced]')) {
			g.script('scripts/file_input.js');
		}
	});

	g.onload(() => {
		let forms = document.forms;

		if (forms.length != 1) return;

		// Disable progress on search or the form will stay blurred when clicking export buttons
		if (forms[0].hasAttribute('data-disable-progress')) return;

		forms[0].addEventListener('submit', (e) => {
			if (e.defaultPrevented) return;
			forms[0].classList.add('progressing');
		});
	});

	// To be able to select a whole table line just by clicking the row
	g.onload(function () {
		var tableActions = document.querySelectorAll('form table tfoot .actions select');

		for (var i = 0; i < tableActions.length; i++)
		{
			tableActions[i].onchange = function () {
				if (!this.value) {
					return;
				}

				if (!this.form.querySelector('table tbody input[type=checkbox]:checked'))
				{
					this.selectedIndex = 0;
					return !window.alert("Aucune ligne sélectionnée !");
				}

				if (this.options[this.selectedIndex].hasAttribute('data-no-dialog')) {
					this.form.target = '';
				}

				this.form.dispatchEvent(new Event('submit'));
				this.form.submit();
			};
		}

		// Ajouter action check/uncheck sur les checkbox de raccourci dans les tableaux
		document.querySelectorAll('table thead input[type=checkbox], table tfoot input[type=checkbox]').forEach((elm) => {
			elm.addEventListener('change', g.checkUncheck);
		});

		document.querySelectorAll('table tbody input[type=checkbox]').forEach((elm) => {
			elm.addEventListener('change', () => {
				elm.parentNode.parentNode.classList.toggle('checked', elm.checked);
			});
		});
	});

	g.onload(() => {
		g.resizeParentDialog();

		// File drag and drop support
		if ($('[data-upload-url]').length) {
			g.script('scripts/file_drag.js');
		}
	});

})();