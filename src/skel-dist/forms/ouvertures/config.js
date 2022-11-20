if (!open_data) {
	open_data = {
		'closed': [{'day_close': '25', 'month_close': 'december', 'day_reopen': '2', 'month_reopen': 'january'}],
		'open': [{
			'frequency': '',
			'day': 'saturday',
			'open': '15:00',
			'close': '19:00'
		}]
	};
}

const open_row = `<tr>
	<td>
		<select name="slots[frequency][]">
		</select>
	</td>
	<td>
		<select name="slots[day][]">
		</select>
	</td>
	<td>
		<input type="number" name="slots[open_hour][]" min="0" max="23" step="1" required="required" size="2" class="time" pattern="^\d{1,2}$" />
		: <input type="number" name="slots[open_minutes][]" min="0" max="59" step="1" required="required" size="2" class="time" pattern="^\d{1,2}$" />
	</td>
	<td>
		<input type="number" name="slots[close_hour][]" min="0" max="23" step="1" required="required" size="2" class="time" pattern="^\d{1,2}$" />
		: <input type="number" name="slots[close_minutes][]" min="0" max="59" step="1" required="required" size="2" class="time" pattern="^\d{1,2}$" />
	</td>
	<td class="actions">
		<button data-icon="➖" type="button">Enlever cette ligne</button>
	</td>
</tr>`;

const close_row = `<tr>
	<td>
		<input type="number" name="closed[close_day][]" min="1" max="31" step="1" size="2" required="required" class="time" pattern="^\d{1,2}$" />
		<select name="closed[close_month][]">
		</select>
	</td>
	<td>
		<input type="number" name="closed[reopen_day][]" min="1" max="31" step="1" size="2" required="required" class="time" pattern="^\d{1,2}$" />
		<select name="closed[reopen_month][]">
		</select>
		inclus
	</td>
	<td class="actions">
		<button data-icon="➖" type="button">Enlever cette ligne</button>
	</td>
</tr>`;

const populate_select = (s, data) => {
	Object.entries(data).forEach((e) => {
		const [k, v] = e;
		var o = `<option value="${k}">${v}</option>`;
		s.insertAdjacentHTML('beforeend', o);
	});
}

const add_slot_row = (data) => {
	$('fieldset.slots table tbody')[0].insertAdjacentHTML('beforeend', open_row);
	var r = $('fieldset.slots table tbody tr:last-child')[0];
	r.querySelector('button').onclick = removeRow;
	var s = r.querySelectorAll('select');
	populate_select(s[0], frequencies);
	populate_select(s[1], days);

	if (!data) {
		return;
	}

	data.open = data.open.split(':');
	data.close = data.close.split(':');
	data.open_hour = data.open[0];
	data.open_minutes = data.open[1];
	data.close_hour = data.close[0];
	data.close_minutes = data.close[1];

	delete data.open, data.close;

	Object.entries(data).forEach((e) => {
		const [k, v] = e;
		r.querySelector('[name*=' + k + ']').value = !v ? '' : v;
	});
};

const add_closed_row = (data) => {
	$('fieldset.closed table tbody')[0].insertAdjacentHTML('beforeend', close_row);
	var r = $('fieldset.closed table tbody tr:last-child')[0];
	r.querySelector('button').onclick = removeRow;

	var s = r.querySelectorAll('select');
	populate_select(s[0], months);
	populate_select(s[1], months);

	if (!data) {
		return;
	}

	Object.entries(data).forEach((e) => {
		const [k, v] = e;
		r.querySelector('[name*=' + k + ']').value = !v ? '' : v;
	});
};

$('fieldset.slots p.actions button')[0].onclick = () => {
	var rows = $('fieldset.slots table tbody tr');

	if (!rows.length) {
		add_slot_row();
		return;
	}

	var n = rows[rows.length - 1].cloneNode(true);
	rows[0].parentNode.appendChild(n);
};

$('fieldset.closed p.actions button')[0].onclick = () => {
	var rows = $('fieldset.closed table tbody tr');

	if (!rows.length) {
		add_closed_row();
		return;
	}

	var n = rows[rows.length - 1].cloneNode(true);
	rows[0].parentNode.appendChild(n);
}

open_data.open.forEach((slot) => {
	add_slot_row(slot);
});

open_data.closed.forEach((slot) => {
	add_closed_row(slot);
});


function removeRow(e) {
	var row = e.target.parentNode.parentNode;
	var table = row.parentNode.parentNode;

	if (table.rows.length == 1)
	{
		return false;
	}

	row.parentNode.removeChild(row);
	return false;
}

function addRow(e) {
	var table = e.parentNode.parentNode.querySelector('table');
	var row = table.rows[table.rows.length-1];
	row.parentNode.appendChild(row.cloneNode(true));
	return false;
}
