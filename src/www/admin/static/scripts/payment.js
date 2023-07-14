(function () {
	g.toggle('.accounting', $('#f_accounting_1').checked);

	$('#f_accounting_1').onchange = () => { g.toggle('.accounting', $('#f_accounting_1').checked); };

	function toggleYearForSelector()
	{
		var btn = document.querySelector('#f_account_container button');
		btn.value = btn.value.replace(/year=\d+/, 'year=' + y.value);

		let v = btn.parentNode.querySelector('span');
		if (v) {
			v.parentNode.removeChild(v);
		}
	}

	var y = $('#f_id_year')

	y.onchange = toggleYearForSelector;
})();

function add_item() {
	let row_line;
	let td;
	let item_properties = [['users', 'list', g.admin_url + 'users/selector.php', null], ['user_notes', 'text', null, 'Ex : adhésion demi-tarif']];
	let input;
	let nobr;

	row_line = generate_item_empty_line();
	for (let i = 0; i < item_properties.length; ++i) {
		td = document.createElement('td');
		input = generate_item_input(item_properties[i][1], item_properties[i][0], row_line.getAttribute('data-row-index'), item_properties[i][2], item_properties[i][3]);
		td.appendChild(input);
		row_line.appendChild(td);
	}
	document.getElementById('user_list').getElementsByTagName('tbody')[0].appendChild(row_line);
	add_delete_buttons();
}

function generate_item_empty_line() {
	let row_line;
	let index;

	row_line = document.createElement('tr');
	row_line.classList.add('user');
	row_line.classList.add('need_delete_button');
	index = window.parent.document.getElementById('user_list').getElementsByTagName('tbody')[0].children.length;
	row_line.id = 'user_' + index + '_row';
	row_line.setAttribute('data-row-index', index);
	return row_line;
}

function generate_item_input(type, name, index, value, placeholder) {
	let input;
	let button;

	if (type === 'list') {
		input = document.createElement('span');
		input.id = 'f_' + name + '[' + index + ']_container';
		input.classList.add('input-list');
		button = document.createElement('button');
		button.type = 'button';
		button.value = value;
		button.textContent = 'Sélectionner';
		button.setAttribute('data-name', name + '[' + index + ']');
		button.setAttribute('data-icon', '𝍢');
		button.classList.add('icn-btn');
		button.onclick = (i) => {
			i.target.setCustomValidity('');
			g.current_list_input = i.target.parentNode;
			let url = i.target.value + (i.target.value.indexOf('?') > 0 ? '&' : '?') + '_dialog';
			g.openFrameDialog(url);
			return false;
		};
		input.appendChild(button);
	}
	else {
		input = document.createElement('input');
		input.type = type;
		input.name = name + '[' + index + ']';
		input.id = 'user_' + name + '_' + index;
		input.placeholder = placeholder;
	}
	return (input);
}

function add_delete_buttons() {
	let button;
	let td;

	$('tr.need_delete_button').forEach((row) => {
		button = document.createElement('button');
		button.type = 'button';
		button.name = 'item_delete_button';
		button.setAttribute('data-icon', '➖');
		button.setAttribute('data-user-id', 17);
		button.class = ' icn-btn';
		button.innerText = 'Enlever';
		button.onclick = (event) => {
			remove_item(event.target.parentNode.parentNode.getAttribute('data-row-index'));
		};
		td = document.createElement('td');
		td.classList.add('user_actions');
		td.appendChild(button);
		row.appendChild(td);
		row.classList.remove('need_delete_button');
	});
}

function remove_item(id) {
	document.getElementById('user_' + parseInt(id) + '_row').remove();
}
