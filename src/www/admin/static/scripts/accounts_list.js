$('button[name*=bookmark]').forEach((b) => {
	b.onclick = () => {
		var ct = document.querySelector('input[type="hidden"]');
		var fd = new FormData();
		fd.set(b.name, parseInt(b.value) ? 0 : 1);
		fd.set(ct.name, ct.value);

		fetch(document.forms[0].action, {
			'method': 'POST',
			'body': new URLSearchParams(fd)
		}).then(r => {
			if (!r.ok) {
				alert(r.status);
				return;
			}

			b.value = parseInt(b.value) ? 0 : 1;
			b.setAttribute('data-icon', b.value == 1 ? '☑' : '☐');
		});

		return false;
	};
});

var q = document.querySelector('.quick-search input[type=text]');

if (q) {
	var rows = document.querySelectorAll('table tr.account');

	rows.forEach((e, k) => {
		var l = e.querySelector('td.num').innerText + ' ' + e.querySelector('th').innerText;
		e.setAttribute('data-search-label', g.normalizeString(l));
	});

	q.addEventListener('keyup', (e) => {
		filterTableList();
		return true;
	});
	document.querySelector('.quick-search button[type=reset]').onclick = () => {
		q.value = '';
		q.focus();
		return filterTableList();
	};
	q.focus();
}

function filterTableList() {
	var query = g.normalizeString(q.value);

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