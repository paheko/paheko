$('button[name*=bookmark]').forEach((b) => {
	b.onclick = () => {
		b.value = parseInt(b.value) ? 0 : 1;
		b.setAttribute('data-icon', b.value == 1 ? '☑' : '☐');
		fetch(document.forms[0].action, {
			'method': 'POST',
			'headers': {"Content-Type": "application/x-www-form-urlencoded"},
			'body': b.name + '=' + b.value
		});
		return false;
	};
});

RegExp.escape = function(string) {
  return string.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&')
};

function normalizeString(str) {
	return str.normalize('NFD').replace(/[\u0300-\u036f]/g, "")
}

var q = document.querySelector('.quick-search input[type=text]');

if (q) {
	var rows = document.querySelectorAll('table tr.account');

	rows.forEach((e, k) => {
		var l = e.querySelector('td.num').innerText + ' ' + e.querySelector('th').innerText;
		e.setAttribute('data-search-label', normalizeString(l));
	});

	q.addEventListener('keyup', filterTableList);
	document.querySelector('.quick-search button[type=reset]').onclick = () => {
		q.value = '';
		q.focus();
		return filterTableList();
	};
	q.focus();
}

function filterTableList() {
	var query = new RegExp(RegExp.escape(normalizeString(q.value)), 'i');

	rows.forEach((elm) => {
		if (elm.getAttribute('data-search-label').match(query)) {
			g.toggle(elm, true);
		}
		else {
			g.toggle(elm, false);
		}
	});

	return false;
}