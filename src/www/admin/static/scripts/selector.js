RegExp.escape = function(string) {
  return string.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&')
};

function normalizeString(str) {
	return str.normalize('NFD').replace(/[\u0300-\u036f]/g, "")
}

var buttons = document.querySelectorAll('button');

buttons.forEach((e) => {
	e.onclick = () => {
		window.parent.g.inputListSelected(e.value, e.getAttribute('data-label'));
	};
});

var rows = document.querySelectorAll('table tr');

rows.forEach((e, k) => {
	e.classList.add('clickable');
	var l = e.querySelector('td').innerText + ' ' + e.querySelector('th').innerText;
	e.setAttribute('data-search-label', normalizeString(l));

	e.querySelector('button').onfocus = () => {
		if (f = document.querySelector('tr.focused')) {
			f.classList.remove('focused');
		}
		e.classList.add('focused');
	};

	e.onclick = (evt) => {
		if (evt.target.tagName && evt.target.tagName == 'BUTTON') {
			return;
		}

		e.querySelector('button').click();
	};
});

document.addEventListener('keydown', (evt) => {
	let current = document.querySelector('tbody tr.focused:not(.hidden)') || document.querySelector('tbody tr');
	let available = [];
	let idx = 0;

	for (var i = 0; i < rows.length; i++) {
		if (rows[i].classList.contains('hidden')) {
			continue;
		}

		available.push(rows[i]);

		if (rows[i] === current) {
			idx = available.length - 1;
		}
	}

	if (!available.length) {
		return false;
	}

	if (evt.key == 'ArrowUp') { // Previous item
		idx--;
	}
	else if (evt.key == 'ArrowDown') {
		idx++;
	}
	else if (evt.key == 'PageUp') {
		idx-=10;
	}
	else if (evt.key == 'PageDown') {
		idx+=10;
	}
	else if (evt.key == 'Home') {
		idx = 0;
	}
	else if (evt.key == 'End') {
		idx = available.length;
	}
	else {
		return true;
	}

	if (idx < 0) {
		idx = 0;
	}
	else if (idx >= available.length - 1) {
		idx = available.length - 1;
	}

	current = available[idx];
	current.querySelector('button').focus();

	evt.preventDefault();
	return false;
});

buttons[0].focus();

var q = document.getElementById('lookup');

if (q) {
	q.addEventListener('keyup', (e) => {
		var query = new RegExp(RegExp.escape(normalizeString(q.value)), 'i');

		rows.forEach((elm) => {
			if (elm.getAttribute('data-search-label').match(query)) {
				g.toggle(elm, true);
			}
			else {
				g.toggle(elm, false);
			}
		});

		if (first = document.querySelector('tbody tr:not(.hidden)')) {
			if (f = document.querySelector('tr.focused')) {
				f.classList.remove('focused');
			}
			first.classList.add('focused');
		}

		if (e.key == 'Enter') {
			if (first = document.querySelector('tbody tr.focused:not(.hidden) button')) {
				first.click();
			}
		}

		return false;
	});

	q.focus();
}

var o = document.getElementById('f_typed_only_0');

if (o) {
	o.onchange = () => {
		let s = new URLSearchParams(window.location.search);
		s.set("all", o.checked ? 0 : 1);
		window.location.search = s.toString();
	};
}