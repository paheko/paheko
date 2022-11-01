$('button[name]').forEach((b) => {
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
