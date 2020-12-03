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
		rows.forEach((r) => {
			if (r == e) {
				return;
			}

			r.classList.remove('focused');
		});

		e.classList.add('focused');
	};

	e.onclick = (evt) => {
		if (evt.target.tagName && evt.target.tagName == 'BUTTON') {
			return;
		}

		e.querySelector('button').click();
	};
});

document.onkeydown = (evt) => {
	let focus = document.activeElement;
	let new_focus;

	// Get first element
	if (focus.tagName != 'BUTTON') {
		new_focus = document.querySelector('table tr');
	}

	if (evt.key == 'ArrowUp' && !new_focus) {
		let idx = focus.parentNode.parentNode.dataset.idx - 1;

		if (idx == 0) {
			return true;
		}

		new_focus = rows[idx - 1];
	}
	else if (evt.key == 'ArrowDown' && !new_focus) {
		let idx = focus.parentNode.parentNode.dataset.idx - 1;

		if (idx >= rows.length - 1) {
			return true;
		}

		new_focus = rows[idx + 1];
	}
	else {
		new_focus = null;
	}

	if (!new_focus) {
		return true;
	}

	new_focus.querySelector('button').focus();
	return false;
};

buttons[0].focus();

var q = document.getElementById('lookup');

if (q) {
	q.onkeyup = (e) => {
		var query = new RegExp(RegExp.escape(normalizeString(q.value)), 'i');

		rows.forEach((elm) => {
			if (elm.getAttribute('data-search-label').match(query)) {
				elm.style.display = null;
			}
			else {
				elm.style.display = 'none';
			}
		});

		return false;
	};

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