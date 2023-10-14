RegExp.escape = function(string) {
  return string.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&')
};

function normalizeString(str) {
	return str.normalize('NFD').replace(/[\u0300-\u036f]/g, "")
}

var buttons = document.querySelectorAll('button[type=button]');

buttons.forEach((e) => {
	e.onclick = () => {
		window.parent.g.inputListSelected(e.value, e.getAttribute('data-label'));
	};
});

var rows = document.querySelectorAll('table tr');

rows.forEach((e, k) => {
	e.classList.add('clickable');
	var l = e.querySelector('td.num').innerText + ' ' + e.querySelector('th').innerText;
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

	// Do not intercept home/end inside text input
	if ((evt.key == 'Home' || evt.key == 'End')
		&& document.activeElement instanceof HTMLInputElement
		&& document.activeElement.type == 'text') {
		return;
	}

	if (evt.key == 'Home') {
		idx = 0;
	}
	else if (evt.key == 'End') {
		idx = available.length;
	}
	else if (evt.key == 'ArrowUp') { // Previous item
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

if (buttons[0]) {
	buttons[0].focus();
}

var q = document.querySelector('.quick-search input[type=text]');
var qr = document.querySelector('.quick-search button[type=reset]');

if (q && qr) {
	q.addEventListener('keyup', filterTableList);
	qr.onclick = (e) => {
		q.value = '';
		q.focus();
		return filterTableList(e);
	};

	q.focus();
}

function filterTableList(e) {
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
}
