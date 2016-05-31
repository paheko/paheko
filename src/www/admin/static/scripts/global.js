(function () {
	window.g = window.garradin = {
		url: window.location.href.replace(/\/admin\/.*?$/, ''),
		admin_url: window.location.href.replace(/\/admin\/.*?$/, '/admin/'),
		static_url: window.location.href.replace(/\/admin\/.*?$/, '/admin/static/')
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

	g.onload = function(callback, dom)
	{
		if (typeof dom == 'undefined')
			dom = true;
		
		var eventName = dom ? 'DOMContentLoaded' : 'load';

		if (document.addEventListener)
		{
			document.addEventListener(eventName, callback, false);
		}
		else
		{
			document.attachEvent('on' + eventName, callback);
		}
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

		var elements = $(selector);

		for (var i = 0; i < elements.length; i++)
		{
			if (!visibility)
				elements[i].classList.add('hidden');
			else
				elements[i].classList.remove('hidden');
		}

		return true;
	};

	g.script = function (file) {
		var script = document.createElement('script');
		script.type = 'text/javascript';
		script.src = this.static_url + file;
		return document.head.appendChild(script);
	};

	g.style = function (file) {
		var link = document.createElement('link');
		link.rel = 'stylesheet';
		link.type = 'text/css';
		link.href = this.static_url + file;
		return document.head.appendChild(link);
	};

	// From KD2fw/js/xhr.js
	g.load = function(b,d,f,e){var a=new XMLHttpRequest();if(!a||!b)return false;if(a.overrideMimeType)a.overrideMimeType('text/xml');b+=(b.indexOf('?')+1?'&':'?')+(+(new Date));a.onreadystatechange=function(){if(a.readyState!=4)return;if((s=a.status)==200){if(!d)return true;var c=a.responseText;if(f=='json'){return((j=window.JSON)&&j.parse)?j.parse(c):eval('('+c.replace(/[\n\r]/g,'')+')')}d(c)}else if(e){e(s)}};a.open('GET',b,true);a.send(null)};

	g.checkUncheck = function()
	{
		var elements = this.form.getElementsByTagName('input');
		var el_length = elements.length;

		for (i = 0; i < el_length; i++)
		{
			var elm = elements[i];

			if (elm.type == 'checkbox' && elm.name)
			{
				elm.checked = this.checked;

				if (elm.onchange)
					elm.onchange({target: elm});
			}
		}

		return true;
	};

	var dateInputFallback = function ()
	{
		/*
		// Firefox dit implémenter date, mais ne l'implémente pas, aucun moyen de détecter ce cas
		// donc on force l'utilisation du custom datepicker de Garradin…
		var input = document.createElement('input');
		input.setAttribute('type', 'date');
		input.value = ':-)';
		input.style.position = 'absolute';
		input.style.visibility = 'hidden';
		document.body.appendChild(input);
		
		// If input type changed or value hasn't been sanitized then
		// the input type date element is not supported
		if (input.type !== 'text' && input.value !== ':-)')
		{
			document.body.removeChild(input);
		*/
			if (document.querySelector && !document.querySelector('input[type=date]'))
				return false;

			g.script('scripts/datepickr.js');
			g.style('scripts/datepickr.css');
		/*
		}
		else
		{
			document.body.removeChild(input);
		}*/
	};

	g.onload(dateInputFallback);

	if (document.querySelectorAll)
	{
		g.onload(function () {
			var checkTables = document.querySelectorAll('table thead input[type=checkbox]');
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

				var checkBoxes = parent.querySelectorAll('tbody input[type=checkbox]');
				var ll = checkBoxes.length;

				for (var j = 0; j < ll; j++)
				{
					checkBoxes[j].onchange = function (e) {
						var elm = e.target || this;

						var parent = elm.parentNode;

						while (parent.nodeType != Node.ELEMENT_NODE || parent.tagName != 'TR')
						{
							parent = parent.parentNode;
						}
						
						if (elm.checked)
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
	}

})();