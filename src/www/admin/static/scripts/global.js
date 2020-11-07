(function () {
	window.g = window.garradin = {
		url: window.location.href.replace(/\/admin\/.*?$/, ''),
		admin_url: window.location.href.replace(/\/admin\/.*?$/, '/admin/'),
		static_url: window.location.href.replace(/\/admin\/.*?$/, '/admin/static/'),
		version: document.head.querySelector('script').src.match(/\?(.*)$/)[1],
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

	g.toggle = function(selector, visibility)
	{
		if (!('classList' in document.documentElement))
			return false;

		if (selector instanceof Array)
		{
			for (var i = 0; i < selector.length; i++)
			{
				g.toggle(selector[i], visibility);
			}

			return true;
		}

		var elements = document.querySelectorAll(selector);

		for (var i = 0; i < elements.length; i++)
		{
			if (!visibility)
				elements[i].classList.add('hidden');
			else
				elements[i].classList.remove('hidden');
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
		script.src = this.static_url + file + '?' + this.version;
		script.onload = callback;
		document.head.appendChild(script);
	};

	g.style = function (file) {
		var link = document.createElement('link');
		link.rel = 'stylesheet';
		link.type = 'text/css';
		link.href = this.static_url + file + '?' + this.version;
		return document.head.appendChild(link);
	};

	g.dialog = null;

	g.openDialog = function (content) {
		if (null !== g.dialog) {
			g.closeDialog();
		}

		g.dialog = document.createElement('dialog');
		g.dialog.id = 'dialog';
		g.dialog.open = true;
		g.dialog.style.opacity = content.style.width = content.style.height = 0;
		g.dialog.appendChild(content);
		g.dialog.onclick = (e) => { if (e.target == g.dialog) g.closeDialog(); };
		window.onkeyup = (e) => { if (e.key == 'Escape') g.closeDialog(); };

		document.body.appendChild(g.dialog);

		// Restore CSS defaults
		window.setTimeout(() => { g.dialog.style.opacity = content.style.width = content.style.height = ''; }, 50);
	}

	g.openFrameDialog = function (url) {
		var iframe = document.createElement('iframe');
		iframe.src = url;
		iframe.name = 'dialog';
		iframe.frameborder = '0';
		iframe.scrolling = 'yes';
		iframe.width = iframe.height = 0;
		iframe.onload = () => { iframe.contentWindow.onkeyup = (e) => { if (e.key == 'Escape') g.closeDialog(); };}

		g.openDialog(iframe);
	};

	g.closeDialog = function () {
		if (null === g.dialog) {
			return;
		}

		var d = g.dialog;
		var c = d.firstChild;
		d.style.opacity = c.style.width = c.style.height = 0;
		window.onkeyup = g.dialog = null;

		window.setTimeout(() => { d.parentNode.removeChild(d); }, 500);
	}

	// From KD2fw/js/xhr.js
	g.load = function(b,d,f,e){var a=new XMLHttpRequest();if(!a||!b)return false;if(a.overrideMimeType)a.overrideMimeType('text/xml');b+=(b.indexOf('?')+1?'&':'?')+(+(new Date));a.onreadystatechange=function(){if(a.readyState!=4)return;if((s=a.status)==200){if(!d)return true;var c=a.responseText;if(f=='json'){return((j=window.JSON)&&j.parse)?j.parse(c):eval('('+c.replace(/[\n\r]/g,'')+')')}d(c)}else if(e){e(s)}};a.open('GET',b,true);a.send(null)};

	g.checkUncheck = function()
	{
		var elements = this.form.querySelectorAll('input[type=checkbox]');
		var el_length = elements.length;
		var checked = this.checked;

		for (var i = 0; i < el_length; i++)
		{
			var elm = elements[i];
			elm.checked = checked;

			if (elm.onchange && elm.name)
			{
				elm.onchange({target: elm});
			}
		}

		return true;
	};

	g.enhancePasswordField = function (field, repeat_field = null)
	{
		var show_password = document.createElement('input');
		show_password.type = 'button';
		show_password.className = 'icn action showPassword';
		show_password.title = 'Voir/cacher le mot de passe';
		show_password.value = '👁';
		show_password.onclick = function (e) {
			var pos = field.selectionStart;
			var hidden = field.type.match(/pass/i);
			field.type = hidden ? 'text' : 'password';
			this.value = !hidden ? '👁' : '⤫';
			field.classList.toggle('clearTextPassword');

			if (null !== repeat_field)
			{
				repeat_field.type = field.type;
				repeat_field.classList.toggle('clearTextPassword');
			}

			// Remettre le focus sur le champ mot de passe
			// on ne peut pas vraiment remettre le focus sur le champ
			// précis qui était utilisé avant de cliquer sur le bouton 
			// car il faudrait enregistrer les actions onfocus de tous
			// les champs de la page
			field.focus();
			field.selectionStart = field.selectionEnd = pos;
		};

		field.parentNode.insertBefore(show_password, field.nextSibling);
	};

	g.enhanceDateField = (input) => {
		var span = document.createElement('span');
		span.className = 'datepicker-parent';
		var btn = document.createElement('button');
		var cal = null;
		btn.className = 'icn-btn';
		btn.setAttribute('data-icon', '📅');
		btn.type = 'button';
		btn.onclick = () => {
			g.script('scripts/datepicker2.js', () => {
				if (null == cal) {
					cal = new DatePicker(btn, input, {lang: 'fr', format: 1});
					cal.open();
				}
			});
		};
		span.appendChild(btn);
		input.parentNode.insertBefore(span, input.nextSibling);
	};

	g.current_list_input = null;

	g.inputListSelected = function(value, label) {
		var i = g.current_list_input;
		var multiple = i.firstChild.getAttribute('data-multiple');
		var name = i.firstChild.getAttribute('data-name');

		var span = document.createElement('span');
		span.className = 'label';
		span.innerHTML = '<input type="hidden" name="' + name + '[' + value + ']" value="' + label + '" />' + label;

		// Add delete button
		if (parseInt(multiple, 10) == 1) {
			var btn = document.createElement('button');
			btn.className = 'icn-btn';
			btn.type = 'button';
			btn.setAttribute('data-icon', '✘');
			btn.onclick = () => span.parentNode.removeChild(span);
			span.appendChild(btn);
		}
		else if (old = i.querySelector('span')) {
			i.removeChild(old);
		}

		i.appendChild(span);
		g.closeDialog();
		i.firstChild.focus();
	};

	g.formatMoney = (v) => {
		v = '' + v;
		return (v.substr(0, v.length-2) || '0') + ',' + v.substr(-2);
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
		if ((i = document.querySelector('form[data-focus="1"] [value]')) && (!document.activeElement || document.activeElement.tagName.toLowerCase() == 'body')) {
			i.focus();
		}
	}, 'dom');

	// Sélecteurs de listes
	g.onload(() => {
		var inputs = $('form .input-list > button');

		inputs.forEach((i) => {
			i.onclick = () => {
				g.current_list_input = i.parentNode;
				g.openFrameDialog(i.value);
				return false;
			};
		});

		var multiples = $('form .input-list span button');

		multiples.forEach((btn) => {
			btn.onclick = () => btn.parentNode.parentNode.removeChild(btn.parentNode);
		});
	});

	g.onload(() => {
		document.querySelectorAll('input[data-input="date"]').forEach((e) => {
			g.enhanceDateField(e);
		});
	});

	g.onload(function () {
		var tableActions = document.querySelectorAll('form table tfoot .actions select');

		for (var i = 0; i < tableActions.length; i++)
		{
			tableActions[i].onchange = function () {
				if (!this.form.querySelector('table tbody input[type=checkbox]:checked'))
				{
					return !window.alert("Aucune ligne sélectionnée !");
				}

				this.form.submit();
			};
		}

		// Ajouter action check/uncheck sur les checkbox de raccourci dans les tableaux
		var checkTables = document.querySelectorAll('table thead input[type=checkbox], table tfoot input[type=checkbox]');
		var l = checkTables.length;

		for (var i = 0; i < l; i++)
		{
			var masterCheck = checkTables[i];
			masterCheck.onchange = g.checkUncheck;

			var parent = masterCheck.parentNode;

			while (parent.nodeType != Node.ELEMENT_NODE || parent.tagName != 'TABLE')
			{
				parent = parent.parentNode;
			}

			var checkBoxes = parent.querySelectorAll('tbody tr input[type=checkbox]');
			var ll = checkBoxes.length;

			for (var j = 0; j < ll; j++)
			{
				checkBoxes[j].onchange = function (e) {
					var elm = e.target || this;
					var checked = elm.checked ? true : false;

					var parent = elm.parentNode;

					while (parent.nodeType != Node.ELEMENT_NODE || parent.tagName != 'TR')
					{
						parent = parent.parentNode;
					}

					if (checked)
						parent.className = parent.className.replace(/ checked$|$/, ' checked');
					else
						parent.className = parent.className.replace(/ checked/, '');
				};

				if (checkBoxes[j].checked)
				{
					checkBoxes[j].onchange({target: checkBoxes[j]});
				}
			}
		}
	});
})();