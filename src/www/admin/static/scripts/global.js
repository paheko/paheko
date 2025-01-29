(function () {
	let d = document.documentElement;
	window.g = window.garradin = {
		admin_url: d.dataset.url,
		static_url: d.dataset.url + 'static/',
		version: d.dataset.version,
		loaded: {}
	};

	d.classList.remove('nojs');
	d.classList.add('js');

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
		if (typeof dom === 'undefined') {
			dom = true;
		}

		var eventName = dom ? 'DOMContentLoaded' : 'load';

		window.addEventListener(eventName, callback);

		if (!dom && document.readyState === 'complete') {
			callback();
		}
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

			elements[i].querySelectorAll('[data-required]').forEach(e => {
				e.required = parseInt(e.dataset.required, 10);
			});

			// Make sure hidden elements are not really required
			// Avoid Chrome bug "An invalid form control with name='' is not focusable."
			elements[i].querySelectorAll('input[required], textarea[required], select[required], button[required]').forEach((e) => {
				if (typeof e.dataset.disabled === 'undefined') {
					e.dataset.disabled = e.hasAttribute('disabled') ? 1 : 0;
				}

				e.disabled = !visibility ? true : parseInt(e.dataset.disabled, 10);
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

		if (!file.match(/^https?:\/\//)) {
			file = this.static_url + file + '?' + g.version;
		}

		var script = g.loaded[file] = document.createElement('script');
		script.type = 'text/javascript';
		script.src = file;
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

	g.normalizeString = function (str) {
		return str.normalize('NFD').replace(/[\u0300-\u036f]/g, "").toLowerCase();
	};

	g.dialog = null;
	g.dialog_title = null;
	g.focus_before_dialog = null;
	g.dialog_on_close = false;

	g.openDialog = function (content, options) {
		if (null !== g.dialog) {
			g.closeDialog();
		}
		return g.replaceDialog(content, options);
	};

	g.createDialog = function (content, options) {
		options = g.getDialogOptions(options);

		g.focus_before_dialog = document.activeElement;

		g.dialog = document.createElement('dialog');
		g.dialog.id = 'dialog';
		g.dialog.open = true;
		g.dialog.className = options.classname || '';
		g.dialog.dataset.caption = options.caption || '';

		var toolbar = document.createElement('header');
		toolbar.className = 'toolbar';

		var t = document.createElement('h4');
		t.className = 'title';
		toolbar.appendChild(t);

		g.dialog_title = document.title;

		if (options.close) {
			var btn = document.createElement('button');
			btn.className = 'icn-btn closeBtn main';
			btn.setAttribute('data-icon', '✘');
			btn.type = 'button';
			btn.innerHTML = 'Fermer';
			btn.onclick = g.closeDialog;
			toolbar.appendChild(btn);
		}

		g.dialog.style.opacity = 0;
		g.dialog.appendChild(toolbar);
		document.body.appendChild(g.dialog);

		// Remove ability to scoll background when dialog is open
		// Avoid having the page "jumping" because the scrollbar has been removed
		document.body.style.width = document.body.clientWidth + 'px';
		document.body.style.overflow = 'hidden';
	};

	g.getDialogOptions = function (options) {
		if (typeof options !== 'object' || options === null) {
			options = {};
		}

		options.callback = options.callback ?? null;
		options.classname = options.classname ?? null;
		options.close = options.close ?? true;
		options.caption = options.caption ?? null;
		options.click_to_close = options.click_to_close ?? false;
		g.dialog_on_close = options.on_close || false;
		return options;
	};

	g.replaceDialog = function (content, options) {
		if (!g.dialog) {
			g.createDialog(content, options);
		}
		else {
			g.dialog.style.opacity = 0;
			g.dialog.classList.remove('loaded');
			g.dialog.lastElementChild.remove();
			g.resetDialogEvents();
		}

		options = g.getDialogOptions(options);

		var t = g.dialog.querySelector('h4.title');

		if (!t) {
			return;
		}

		t.innerText = options.caption || '';

		if (options.caption && !document.title.match(options.caption)) {
			document.title = options.caption + ' — ' + g.dialog_title;
		}

		g.setDialogKey('Escape', g.closeDialog);

		if (typeof content == 'string') {
			var container = document.createElement('div');
			container.className = 'content';
			container.innerHTML = content;
			content = container;
		}
		else if (content instanceof DocumentFragment) {
			var container = document.createElement('div');
			container.className = 'content';
			container.appendChild(content.cloneNode(true));
			content = container;
		}

		let tag = content.tagName.toLowerCase();

		if (tag !== 'iframe' && tag !== 'div') {
			var container = document.createElement('div');
			container.className = 'preview';
			container.appendChild(content);
			g.dialog.appendChild(container);
		}
		else {
			g.dialog.appendChild(content);
		}

		if (tag == 'img' || tag == 'iframe') {
			event = 'load';
		}
		else if (tag == 'audio' || tag == 'video') {
			event = 'canplaythrough';
		}

		if (event) {
			content.addEventListener(event, () => { if (g.dialog) g.dialog.classList.add('loaded'); });

			if (event && options.callback) {
				content.addEventListener(event, options.callback);
			}
		}
		else {
			g.dialog.classList.add('loaded');
		}

		// Restore CSS defaults
		window.setTimeout(() => { g.dialog.style.opacity = ''; }, 50);

		if (options.click_to_close) {
			g.dialog.onclick = (e) => {
				if (e.target === g.dialog) {
					g.closeDialog();
				}
			};
		}

		return content;
	};

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
				iframe.style.height = iframe.dataset.height == 'auto' && iframe.contentWindow.document.body ? iframe.contentWindow.document.body.offsetHeight + 'px' : iframe.dataset.height;
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

		var p = window.parent;
		var dialog = p.g.dialog;

		if (dialog.classList.contains('fullscreen')) {
			return;
		}

		if (!dialog.dataset.caption && document.title) {
			var title = document.title.replace(/^([^—-]+).*$/, "$1");
			dialog.querySelector('.title').innerText = title;
			p.document.title = document.title + ' — ' + p.g.dialog_title;
		}

		let height;

		if (forced_height) {
			height = forced_height;
		}
		else {
			let body_height = document.body.offsetHeight;
			let parent_height = p.innerHeight;

			if (body_height > parent_height * 0.9) {
				height = '90%';
			}
			else {
				height = body_height + 'px';
			}
		}

		dialog.childNodes[1].style.height = height;
	};

	g.closeDialog = function () {
		for (var i in g.dialog_events) {
			if (!g.dialog_events.hasOwnProperty(i)) {
				continue;
			}

			var evt = g.dialog_events[i];
			if (evt[0] !== 'close') {
				continue;
			}

			evt[1]();
		}

		g.resetDialogEvents();

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
		g.dialog = null;

		window.setTimeout(() => { d.parentNode.removeChild(d); }, 500);

		if (g.focus_before_dialog) {
			g.focus_before_dialog.focus();
		}

		if (g.dialog_title !== null) {
			document.title = g.dialog_title;
			g.dialog_title = null;
		}

		document.body.style.overflow = null;
		document.body.style.width = null;
	};

	g.openFormInDialog = (form) => {
		if (form.target != '_dialog' && form.target != 'dialog') {
			return;
		}

		let url = form.getAttribute('action');
		url = url + (url.indexOf('?') > 0 ? '&' : '?') + '_dialog';
		form.setAttribute('action', url);
		form.target = 'dialog';

		g.openFrameDialog('about:blank', {'height': form.getAttribute('data-dialog-height') ? 90 : 'auto'});
		form.submit();
		return false;
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

	g.formatMoney = (v, html) => {
		if (!v) {
			return '0,00';
		}

		var s = v < 0 ? '-' : '';
		v = '' + Math.abs(v);

		var units = v.substr(0, v.length-2) || '0';

		if (html) {
			// Add spacer
			units = units.split("").reverse().join("");
			units = units.replace(/(\d{3})/g, "$1\xa0");
			units = units.split("").reverse().join("").trim("\xa0");
		}

		return s + units + ',' + ('00' + v).substr(-2);
	};

	g.getMoneyAsInt = (v) => {
		v = v.replace(/[^0-9.,-]/, '');
		if (v.length == 0) return;
		var m = 1;

		if (v.match(/^-/)) {
			m = -1;
			v = v.substr(1);
		}

		v = v.split(/[,.]/);
		var d = v.length == 2 ? v[1] : '0';
		v = v[0] + (d + '00').substr(0, 2);
		v = parseInt(v, 10) * m;
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

	// List selectors, using an iframe for list
	g.onload(() => {
		var inputs = $('form .input-list > button');

		inputs.forEach((i) => {
			i.onclick = () => {
				i.setCustomValidity('');
				g.current_list_input = i.parentNode;
				var max = i.getAttribute('data-max');

				if (max && max <= i.parentNode.querySelectorAll('span').length) {
					alert('Il n\'est pas possible de faire plus de ' + max + ' choix.');
					return false;
				}

				let url = i.value + (i.value.indexOf('?') > 0 ? '&' : '?') + '_dialog';
				var caption = i.dataset.caption || null;
				g.openFrameDialog(url, {caption});
				return false;
			};
		});

		// Set custom error message if required list is not selected
		document.querySelectorAll('form').forEach((form) => {
			let elements = form.elements;

			// Make sure hidden or disabled form elements are not required
			for (var j = 0; j < elements.length; j++) {
				var element = elements[j];

				if (element.required && (element.disabled || !element.offsetParent)) {
					element.dataset.required = element.hasAttribute('required') ? 1 : 0;
					element.required = false;
				}
			}

			form.addEventListener('submit', (e) => {
				let elements = form.elements;

				// Make sure hidden or disabled form elements are not required
				for (var j = 0; j < elements.length; j++) {
					var element = elements[j];

					if (element.disabled || !element.offsetParent) {
						element.required = false;
					}
				}

				let inputs = form.querySelectorAll('.input-list > button[required]');

				for (var k = 0; k < inputs.length; k++) {
					var i2 = inputs[k];

					// Ignore hidden / disabled form elements
					if (i2.disabled || !i2.offsetParent) {
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

				return true;
			});
		});

		var multiples = $('form .input-list span button');

		multiples.forEach((btn) => {
			btn.onclick = () => btn.parentNode.parentNode.removeChild(btn.parentNode);
		});

		// Open links in dialog
		$('a[target*="_dialog"]').forEach((e) => {
			e.onclick = () => g.openPreview(e);
		});

		$('form[target="_dialog"]').forEach((e) => {
			e.addEventListener('submit', () => g.openFormInDialog(e));
		});
	});

	g.dialog_events = [];
	g.setDialogKey = function (key, callback) {
		g.addDialogEvent('keyup', (e) => {
			if (e.key !== key) {
				return;
			}

			e.preventDefault();
			callback();
			return false;
		});

		if (key === 'ArrowLeft') {
			g.addDialogEvent('swipeleft', callback);
		}
		else if (key === 'ArrowRight') {
			g.addDialogEvent('swiperight', callback);
		}
	};

	g.addDialogEvent = function (event, callback) {
		if (event !== 'close') {
			window.addEventListener(event, callback, true);
		}
		g.dialog_events.push([event, callback]);
	};

	g.resetDialogEvents = function () {
		var e;

		while (e = g.dialog_events.pop()) {
			if (e[0] === 'close') {
				continue;
			}

			window.removeEventListener(e[0], e[1], true);
		}
	};

	/**
	 * Open file preview
	 */
	g.openPreview = function (e) {
		let type = e.getAttribute('data-mime');
		let caption = e.getAttribute('data-caption');

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
				'on_close': e.getAttribute('data-dialog-on-close') == 1,
				caption
			});
			return false;
		}

		if (type.match(/^image\//)) {
			var i = document.createElement('img');
			i.src = e.href;
			i.draggable = false;
			i.onclick = () => g.navigateToPreview(e);
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
			g.openFrameDialog(url, {height: '90%', caption});
			return false;
		}

		g.replaceDialog(i, {caption, 'click_to_close': true});

		g.setDialogKey('ArrowLeft', () => g.navigateToPreview(e, true));
		g.setDialogKey('ArrowRight', () => g.navigateToPreview(e));

		var evt = e => { e.stopPropagation(); e.preventDefault(); g.closeDialog(); };
		g.addDialogEvent('swipeup', evt);
		i.addEventListener('swipeup', evt);

		return false;
	};

	/**
	 * Navigate between elements to preview (eg. images)
	 */
	g.navigateToPreview = function (element, to_prev) {
		var preview_items = document.querySelectorAll('a[target="_dialog"][data-mime]');
		preview_items = Array.from(preview_items).filter((e) => e.dataset.mime.match(/^(audio|video|image)\//));
		var next = null;
		var prev = null;

		for (var i = 0; i < preview_items.length; i++) {
			var item = preview_items[i];

			if (item.href === element.href) {
				if (to_prev) {
					next = prev;
					break;
				}

				next = false;
				continue;
			}

			if (next === false) {
				next = item;
				break;
			}

			prev = item;
		}

		if (!next) {
			g.closeDialog();
			return;
		}

		g.openPreview(next);
		return false;
	};

	g.onload(() => {
		document.querySelectorAll('input[data-input="date"]').forEach((e) => {
			g.enhanceDateField(e);
		});

		document.querySelectorAll('input[type="password"]:not([readonly]):not([disabled]):not(.hidden)').forEach((e) => {
			g.enhancePasswordField(e);
		});

		// Enhance file inputs to add image preview, paste support, etc.
		if (document.querySelector('input[type="file"][data-enhanced]')) {
			g.script('scripts/inputs/file.js');
		}

		if (document.querySelector('input[list], textarea[list]')) {
			g.script('scripts/inputs/datalist.js');
		}

		var dropdown;

		var closeDropdownEvent = (evt) => {
			if ((evt.type === 'keydown' && evt.key === 'Escape')
				|| (evt.type === 'click' && !dropdown.contains(evt.target))) {
				closeDropdown();
				evt.preventDefault();
				return false;
			}
		};

		var closeDropdown = () => {
			dropdown.classList.remove('open');
			dropdown.setAttribute('aria-expanded', 'false');
			dropdown = null;
			window.removeEventListener('keydown', closeDropdownEvent, {'capture': true});
			window.removeEventListener('click', closeDropdownEvent, {'capture': true});
		};

		var openDropdown = (e) => {
			if (e.classList.contains('open')) {
				return true;
			}

			dropdown = e;
			e.classList.add('open');
			e.setAttribute('aria-expanded', 'true');
			window.addEventListener('keydown', closeDropdownEvent, {'capture': true});
			window.addEventListener('click', closeDropdownEvent, {'capture': true});
			return false;
		}

		document.querySelectorAll('nav.dropdown').forEach(e => {
			e.onclick = () => openDropdown(e);
		});
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

				var action = this.form.getAttribute('action');
				var target = this.form.getAttribute('target');

				if (this.hasAttribute('data-form-action')) {
					this.form.action = this.dataset.formAction;
				}

				if (this.getAttribute('data-form-target') === '_dialog') {
					this.form.target = '_dialog'
				}

				if (this.options[this.selectedIndex].hasAttribute('data-no-dialog')) {
					this.form.target = '';
				}

				if (this.form.target === '_dialog') {
					g.openFormInDialog(this.form);
				}
				else {
					// Not sure if this is required?
					this.form.dispatchEvent(new Event('submit'));
					this.form.submit();
				}

				this.form.action = action;
				this.form.target = target || '';
				this.selectedIndex = 0;
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

	g.onload(() => g.resizeParentDialog(), false);

	g.onload(() => {
		// File drag and drop support
		if ($('[data-upload-url]').length) {
			g.script('scripts/file_drag.js');
		}
	});

	// Add support for accesskeys, even if web browser doesn't support it, or has a weird keys combination
	window.addEventListener('keydown', (e) => {
		if (!e.altKey || !e.shiftKey) {
			return true;
		}

		if (e.key.length === 1 && (el = document.querySelector('[accesskey="' + e.key + '"]'))) {
			el.click();
			return true;
		}

		// Highlight accesskeys
		document.body.classList.add('accesskeys');
	});
	window.addEventListener('keyup', () => { document.body.classList.remove('accesskeys'); });

	// Implement swipeleft/swiperight events
	let touch_start_x = 0;
	let touch_start_y = 0;
	let touch_start_el = null;

	window.addEventListener('touchstart', e => {
		touch_start_el = e.target;
		touch_start_x = e.changedTouches[0].screenX;
		touch_start_y = e.changedTouches[0].screenY;
	});

	window.addEventListener('touchend', e => {
		if (touch_start_el !== e.target) {
			return;
		}

		var touch_end_x = e.changedTouches[0].screenX;
		var touch_end_y = e.changedTouches[0].screenY;
		var distance_x = touch_end_x - touch_start_x;
		var distance_y = touch_end_y - touch_start_y;
		var direction = null;

		if (Math.abs(distance_x) > Math.abs(distance_y)) {
			if (distance_x < -20) {
				direction = 'left';
			}
			else if (distance_x > 20) {
				direction = 'right';
			}
		}
		else {
			if (distance_y < -20) {
				direction = 'up';
			}
			else if (distance_y > 20) {
				direction = 'down';
			}
		}

		if (!direction) {
			return;
		}

		touch_start_el.dispatchEvent(new CustomEvent('swipe' + direction, {
			bubbles: true,
			cancelable: true,
			detail: {direction, distance_x, distance_y}
		}));
	})
})();