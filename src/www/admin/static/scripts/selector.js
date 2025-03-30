var buttons = document.querySelectorAll('button[type=button]');

buttons.forEach((e) => {
	e.onclick = () => {
		window.parent.g.inputListSelected(e.value, e.getAttribute('data-label'));
	};
});

var rows = document.querySelectorAll('table tr');

rows.forEach((e, k) => {
	e.classList.add('clickable');
	var l = '';

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
	var t;

	q.addEventListener('keyup', (e) => {
		if (e.key === 'Enter' && (first = document.querySelector('tbody tr.focused:not(.hidden) button'))) {
			first.click();
			e.preventDefault();
			return false;
		}
	});

	q.addEventListener('input', () => {
		window.clearTimeout(t);
		t = window.setTimeout(filterTableList, 200);
	});

	qr.onclick = (e) => {
		q.value = '';
		q.focus();
		return filterTableList(e);
	};

	q.focus();
}

function filterTableList() {
	window.clearTimeout(t);
	var code = q.value.trim().match(/^\d/) ? q.value.trim().toLowerCase() : null;
	var query = g.normalizeString(q.value).toLowerCase();
	var found = 0;

	rows.forEach((elm) => {
		if ((code && elm.dataset.searchCode.startsWith(code))
			|| elm.dataset.searchLabel.includes(query)) {
			g.toggle(elm, true, false);
			found++;
		}
		else {
			g.toggle(elm, false, false);
		}
	});

	if (first = document.querySelector('tbody tr:not(.hidden)')) {
		if (f = document.querySelector('tr.focused')) {
			f.classList.remove('focused');
		}
		first.classList.add('focused');
	}

	document.querySelectorAll('section.accounts-group').forEach(e => {
		g.toggle(e, !e.querySelectorAll('tr:not(.hidden)').length == 0, false);
	});

	g.toggle('.alert.no-results', found == 0);

	g.resizeParentDialog();
}
