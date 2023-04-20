function compute_total() {
	let items = document.getElementById('item_list').getElementsByClassName('item');
	let total = 0.0;
	let unit_price;
	let quantity;

	for (let i = 0; i < items.length; ++i) {
		unit_price = items[i].children[3].firstChild.firstChild.value.replace(',', '.');
		quantity = items[i].children[4].firstChild.value.replace(',', '.');
		if (!isNaN(unit_price) && !isNaN(quantity) && unit_price > 0)
			total += parseFloat(unit_price) * parseFloat(quantity);
	}
	return Math.round((total + Number.EPSILON) * 100) / 100;
}

function refresh_total() {
	let total;
	
	total = compute_total();
	document.getElementById('quotation_total').textContent = parseFloat(total);
	document.getElementById('quotation_total_input').value = parseFloat(total);
}

function add_input_refresh_total_behavior() {
	$('input.impact_total').forEach((input) => {
		input.onchange = refresh_total;
	});
}

function remove_item(id) {
	document.getElementById('item_' + parseInt(id) + '_row').remove();
	refresh_total();
}

function add_delete_buttons() {
	let button;
	let td;

	$('tr.need_delete_button').forEach((row) => {
		button = document.createElement('button');
		button.type = 'button';
		button.name = 'item_delete_button';
		button.setAttribute('data-icon', '➖');
		button.setAttribute('data-item-id', 17);
		button.class = ' icn-btn';
		button.innerText = 'Enlever';
		button.onclick = (event) => {
			remove_item(event.target.parentNode.parentNode.getAttribute('data-item-id'));
			refresh_total();
		};
		td = document.createElement('td');
		td.classList.add('item_actions');
		td.appendChild(button);
		row.appendChild(td);
		row.classList.remove('need_delete_button');
	});
}

function generate_item_input(type, name, index) {
	let input;

	if (type === 'textarea') {
		input = document.createElement('textarea');
		input.cols = 50;
		input.rows = 2;
	}
	else {
		input = document.createElement('input');
		input.type = type;
		if (type === 'money') {
			input.type = 'text';
			input.pattern = '-?[0-9]+([.,][0-9]{1,2})?';
			input.inputmode = 'decimal';
			input.size = 8;
			input.classList.add('money');
			input.autocomplete = 'off';
		}
	}
	if (name === 'unit_price' || name === 'quantity')
		input.classList.add('impact_total');
	if (name === 'reference')
		input.classList.add('reference');
	if (name === 'quantity') {
		input.min = 0;
		input.max = 9999;
	}
	input.name = 'items[' + index + '][' + name + ']';
	input.id = 'item_' + name + '_' + index;
	return (input);
}

function generate_item_empty_line() {
	let row_line;
	let index;

	row_line = document.createElement('tr');
	row_line.classList.add('item');
	row_line.classList.add('need_delete_button');
	index = window.parent.document.getElementById('item_list').getElementsByTagName('tbody')[0].children.length;
	row_line.id = 'item_' + index + '_row';
	row_line.setAttribute('data-item-id', index);
	return row_line;
}

function add_item() {
	let row_line;
	let td;
	let item_properties = [['reference', 'text'], ['name', 'text'], ['description', 'textarea'], ['unit_price', 'money'], ['quantity', 'number']];
	let input;
	let nobr;

	row_line = generate_item_empty_line();
	for (let i = 0; i < item_properties.length; ++i) {
		td = document.createElement('td');
		td.classList.add('item_' + item_properties[i][0]);
		input = generate_item_input(item_properties[i][1], item_properties[i][0], row_line.id);
		if (item_properties[i][1] === 'money') {
			nobr = document.createElement('nobr');
			nobr.appendChild(input);
			input = nobr;
		}
		td.appendChild(input);
		row_line.appendChild(td);
	}
	document.getElementById('item_list').getElementsByTagName('tbody')[0].appendChild(row_line);
	refresh_total();
	add_delete_buttons();
	add_input_refresh_total_behavior();
}
