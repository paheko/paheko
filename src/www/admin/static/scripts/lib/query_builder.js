(function () {
	var qb = function (columns) {
		this.columns = columns;
		this.events = {};
	};

	qb.prototype.findAncestor = (el, sel) => {
		while ((el = el.parentElement) && !((el.matches || el.matchesSelector).call(el,sel)));
		return el;
	};

	window.SQLQueryBuilder = qb;

	qb.prototype.loadDefaultOperators = function () {
		// List of operators
		// ? is a binded value
		// ?? is a list of binded values (eg. IN(??) will be replaced by IN('42', '43'))
		// %?, ?% and %?% are for LIKE conditions, eg. %? will be replaced with '%query'
		// note that in those LIKE cases, % and _ characters will be escaped with a backslash
		this.operators = {
			"= ?": this.__("is equal to"),
			"!= ?": this.__("is not equal to"),
			"IN (??)": this.__("is equal to one of"),
			"NOT IN (??)": this.__("is not equal to one of"),
			"> ?": this.__("is greater than"),
			">= ?": this.__("is greater than or equal to"),
			"< ?": this.__("is less than"),
			"<= ?": this.__("is less than or equal to"),
			"BETWEEN ? AND ?": this.__("is between"),
			"NOT BETWEEN ? AND ?": this.__("is not between"),
			"IS NULL": this.__("is null"),
			"IS NOT NULL": this.__("is not null"),
			"LIKE ?%": this.__("begins with"),
			"NOT LIKE ?%": this.__("doesn't begin with"),
			"LIKE %?": this.__("ends with"),
			"NOT LIKE %?": this.__("doesn't end with"),
			"LIKE %?%": this.__("contains"),
			"NOT LIKE %?%": this.__("doesn't contain"),
			"&": this.__("matches one of"),
			"NOT &": this.__("doesn't match one of"),
			"= 1": this.__("is true"),
			"= 0": this.__("is false"),
		};

		// Build list of operators per type
		this.types_operators = {
			"integer": ["= ?", "!= ?", "IN (??)", "NOT IN (??)", "> ?", ">= ?", "< ?", "<= ?", "BETWEEN ? AND ?", "NOT BETWEEN ? AND ?"],
			"enum": ["= ?", "!= ?", "IN (??)", "NOT IN (??)"],
			"boolean": ["= 1", "= 0"],
			"text": ["= ?", "!= ?", "IN (??)", "NOT IN (??)", "LIKE ?%", "NOT LIKE ?%", "LIKE %?", "NOT LIKE %?", "LIKE %?%", "NOT LIKE %?%"],
			"bitwise": ["&", "NOT &"],
		};

		// Build objects of operators per types
		for (var to in this.types_operators)
		{
			var list = {};
			for (var to_l in this.types_operators[to])
			{
				var op = this.types_operators[to][to_l];
				list[op] = this.operators[op];
			}
			this.types_operators[to] = list;
		}

		// Date and datetime are a bit special
		this.types_operators["date"] = JSON.parse(JSON.stringify(this.types_operators["integer"]));
		delete this.types_operators["date"]["<= ?"];
		delete this.types_operators["date"][">= ?"];
		this.types_operators["date"]["< ?"] = this.__("before");
		this.types_operators["date"]["> ?"] = this.__("after");
		this.types_operators["datetime"] = this.types_operators["date"];
		this.default_operator = null;
	};

	/**
	 * Used to translate a string
	 * @param  string str Original string
	 * @return string Translated string
	 */
	qb.prototype.__ = function (str) {
		return str;
	};

	qb.prototype.init = function (targetElement) {
		this.parent = targetElement;
		var options = {};
		for (column in this.columns)
		{
			options[column] = this.columns[column].label;
		}

		this.columnSelect = this.buildSelect(options);

		// Hide columns
		for (var i = 0; i < this.columnSelect.options.length; i++) {
			var o = this.columnSelect.options[i];

			// Hide some columns by default
			if (this.columns[o.value].hidden ?? null) {
				o.hidden = true;
			}
		}
	};

	qb.prototype.addGroup = function (targetParent, operator, join_operator) {
		var f = document.createElement('fieldset');
		var l = document.createElement('legend');
		var s = this.buildSelect({
			"AND": this.__("Matches ALL of the following conditions:"),
			"OR": this.__("Matches ANY of the following conditions:"),
			//"NOT AND": this.__("Doesn't match ANY of the following conditions:"),
			"ADD": this.__("Add a new set of conditions below this one"),
			"DEL": this.__("Remove this set of conditions"),
		});
		s.name = 'operator';
		s.onfocus = function () {
			this.oldValue = this.value;
		};
		s.value = operator;

		var _self = this;

		s.onchange = function () {
			if (this.value === 'DEL') {
				if (targetParent.childNodes.length == 1) {
					this.value = this.oldValue;
					return;
				}

				targetParent.removeChild(f);
			}
			else if (this.value === 'ADD') {
				var n = _self.addGroup(targetParent, 'AND');
				_self.addRow(n);
				this.value = this.oldValue;
			}
		};

		if (targetParent.childNodes.length >= 1) {
			var o = this.buildSelect({"AND": this.__("AND"), "OR": this.__("OR")});
			o.name = 'join_operator';
			l.appendChild(o);
			l.appendChild(document.createTextNode(" "));
			o.value = join_operator || 'AND';
		}

		l.appendChild(s);
		f.appendChild(l);

		var table = document.createElement('table');
		f.appendChild(table);

		targetParent.appendChild(f);
		return f;
	};

	qb.prototype.addRow = function (targetGroup, after) {
		var targetTable = targetGroup.getElementsByTagName('table')[0];
		var row = document.createElement('tr');
		var cell = document.createElement('td');
		cell.className = 'buttons';

		var _self = this;

		var btn = this.buildInput('button', '+');
		btn.onclick = function () {
			_self.addRow(_self.findAncestor(this, 'fieldset'), this.parentNode.parentNode);
		};

		cell.appendChild(btn);

		var btn = this.buildInput('button', '-');
		btn.onclick = function () {
			_self.deleteRow(this.parentNode.parentNode);
		};

		cell.appendChild(btn);
		row.appendChild(cell);

		var cell = document.createElement('td');
		cell.className = 'column';

		var select = this.columnSelect.cloneNode(true);
		select.onchange = function () { return _self.switchColumn(this); };
		cell.appendChild(select);
		row.appendChild(cell);

		var cell = document.createElement('td');
		cell.className = 'operator';
		row.appendChild(cell);

		var cell = document.createElement('td');
		cell.className = 'values';
		row.appendChild(cell);

		if (typeof after == 'undefined')
		{
			targetTable.appendChild(row);
		}
		else
		{
			targetTable.insertBefore(row, after.nextSibling);
		}

		this.switchColumn(select);

		return row;
	};

	qb.prototype.deleteRow = function (row) {
		if (row.parentNode.childNodes.length <= 1) return;
		row.parentNode.removeChild(row);
	}

	qb.prototype.switchColumn = function (columnSelect, operator, current_values) {
		var row = columnSelect.parentNode.parentNode;
		this.dispatchEvent('columnchange', columnSelect, row);

		var current_operator = row.cells[2].firstChild ? row.cells[2].firstChild.value : null;
		var current_values = current_values ?? this.getValues(row);
		var old_value = columnSelect.dataset.oldValue ?? null;
		var value = columnSelect.value;
		columnSelect.dataset.oldValue = value;
		row.childNodes[2].innerHTML = '';

		if (!value) {
			return;
		}

		var column = this.columns[value];
		var o = this.addOperator(row, column);
		var operators = this.types_operators[column.type];

		if (operator) {
			o.value = operator;
		}
		// Select same operator if it exists
		else if (current_operator && operators.hasOwnProperty(current_operator)) {
			o.value = current_operator;
		}
		else {
			if (this.default_operator && typeof this.default_operator === 'string' && operators.hasOwnProperty(this.default_operator)) {
				o.value = this.default_operator;
			}
			// Multiple default_operator, take the first available one
			else if (this.default_operator) {
				for (var i = 0; i < this.default_operator.length; i++) {
					if (operators.hasOwnProperty(this.default_operator[i])) {
						o.value = this.default_operator[i];
						break;
					}
				}
			}
			else {
				o.value = o.children[1].value;
			}

			current_values = null;
		}

		this.switchOperator(o, current_values);
	};

	qb.prototype.addOperator = function (targetRow, column) {
		var operators = this.types_operators[column.type];
		var options = {};

		if (column.null)
		{
			operators["IS NULL"] = this.operators["IS NULL"];
			operators["IS NOT NULL"] = this.operators["IS NOT NULL"];
		}

		for (var o in operators)
		{
			options[o] = operators[o];
		}

		var select = this.buildSelect(options);

		var _self = this;
		select.onchange = function () { return _self.switchOperator(this); };
		targetRow.childNodes[2].appendChild(select);

		return select;
	};

	qb.prototype.switchOperator = function (operatorSelect, values) {
		var row = operatorSelect.parentNode.parentNode;

		if (!values && row.childNodes[3].firstChild) {
			values = [row.childNodes[3].firstChild.value];
		}

		row.childNodes[3].innerHTML = '';

		var parent = row.childNodes[3];
		var columnSelect = row.childNodes[1].firstChild;

		var operator = operatorSelect.value;
		var column = this.columns[columnSelect.value];

		if (!operator) {
			return;
		}

		this.dispatchEvent('operatorchange', operatorSelect, row, column);

		var number = 1;
		var buttons = false;
		var prev = null;
		var params = operator.match(/\?/g);

		if (params && operator.match(/\?\?/))
		{
			number = values ? values.length : 1;
			buttons = true;
		}
		else if (params && params.length >= 1)
		{
			number = params.length;
		}
		else if (column.type == 'bitwise' && (operator == '&' || operator == 'NOT &'))
		{
			number = 1;
		}
		else
		{
			return;
		}

		for (var i = 0; i < number; i++) {
			prev = this.addMatchField(parent, prev, column, row);

			if (column.type == 'bitwise' && values) {
				// Check the boxes!
				for (var j = 0; j < column.values.length; j++) {
					parent.querySelectorAll('input')[j].checked = values.indexOf(j.toString()) != -1;
				}
			}
			else if (values) {
				prev.value = values[i];
			}

			this.dispatchEvent('valuechange', prev, row);
		}

		if (buttons) {
			// append add/remove values button
			var btn = this.buildInput('button', '-');
			btn.onclick = function () {
				if (this.parentNode.childNodes.length <= 3) return;
				this.parentNode.removeChild(this.previousSibling);
				this.parentNode.removeChild(this.previousSibling); // remove <br />
			};

			parent.appendChild(btn);

			var btn = this.buildInput('button', '+');
			var _self = this;

			btn.onclick = function () {
				_self.addMatchField(parent, this.previousSibling.previousSibling, column, row);
			};

			parent.appendChild(btn);
		}
	};

	qb.prototype.addEventListener = function (name, callback) {
		if (typeof this.events[name] === 'undefined') {
			this.events[name] = [];
		}

		this.events[name].push(callback);
	};

	qb.prototype.dispatchEvent = function (name, ...args) {
		if (!(this.events[name] ?? null)) {
			return;
		}

		for (var i = 0; i < this.events[name].length; i++) {
			this.events[name][i].call(this, ...args);
		}
	};

	qb.prototype.addMatchField = function (targetParent, prev, column, row) {
		if (column.type.indexOf('enum') === 0) {
			var field = this.buildSelect(column.values);
		}
		else if (column.type == 'bitwise') {
			var field = document.createElement('span');

			for (var v in column.values) {
				var checkbox = this.buildInput('checkbox', v);
				var label = document.createElement('label');
				label.appendChild(checkbox);
				label.appendChild(document.createTextNode(' ' + column.values[v]));
				field.appendChild(label.cloneNode(true));
			}
		}
		else {
			var field = this.buildInput(column.type, '', column);
		}

		field = targetParent.insertBefore(field, prev ? prev.nextSibling : null);

		if (prev) {
			targetParent.insertBefore(document.createElement('br'), field);
		}

		field.addEventListener('change', () => this.dispatchEvent('valuechange', field, row));

		return field;
	};

	qb.prototype.buildInput = function (type, label, column) {
		var i = document.createElement('input');
		i.type = type == 'integer' ? 'number' : type;
		i.value = label;
		return i;
	};

	qb.prototype.buildSelect = function (options) {
		var select = document.createElement('select');

		for (var i in options) {
			var o = options[i];

			if (typeof o === 'string') {
				var option = document.createElement('option');
				option.value = i;
				option.innerText = o;
				select.appendChild(option);
			}
			else {
				var sub_options = o.options ?? o;
				var optgroup = document.createElement('optgroup');
				optgroup.label = o.label ?? i;

				for (var j in sub_options) {
					var option = document.createElement('option');
					option.value = j;
					option.innerText = sub_options[j];
					optgroup.appendChild(option);
				}

				select.appendChild(optgroup);
			}
		}

		return select;
	};

	qb.prototype.import = function (groups) {
		var rows = [];

		for (var g in groups)
		{
			if (groups[g].conditions.length == 0)
			{
				// Ignore empty groups
				continue;
			}

			var groupElement = this.addGroup(this.parent, groups[g].operator, groups[g].join_operator ?? null);

			for (var i in groups[g].conditions) {
				var condition = groups[g].conditions[i];
				var column = this.columns[condition.column] ?? null;

				// Skip columns that don't exist anymore
				if (!column) {
					continue;
				}

				var row = this.addRow(groupElement);
				var columnSelect = row.childNodes[1].firstChild;
				columnSelect.value = condition.column;
				columnSelect.dataset.oldValue = columnSelect.value;
				rows.push({row, columnSelect, condition});
			}
		}

		// Switch columns once they have all been added
		for (var i = 0; i < rows.length; i++) {
			this.switchColumn(rows[i].columnSelect, rows[i].condition.operator, rows[i].condition.values);
		}

/*
				// Handle forced columns
				if (column.force) {
					forced = column.force.length;
				}
				else if (forced && forced--) {
					select.options[select.selectedIndex].hidden = false;
					select.setAttribute('aria-readonly', 'true');
					row.classList.add('forced');
				}
			}
		}
		*/
	};

	// Fetch all values in an array
	qb.prototype.getValues = function (row) {
		var values = Array.prototype.slice.call(row.cells[3].querySelectorAll('input, select')).map(function (input) {
			if (input.type == 'checkbox')
			{
				return input.checked ? input.value : null;
			}
			else if (input.type != 'button')
			{
				return input.value;
			}
		});

		values = values.filter(function (v) {
			return v === null ? false : true;
		});

		return values;
	};

	qb.prototype.export = function () {
		var source_groups = this.parent.querySelectorAll('table');
		var groups = [];

		for (var g in source_groups)
		{
			if (!source_groups.hasOwnProperty(g)) continue;

			g = source_groups[g];
			var rows = g.rows;
			var conditions = [];

			for (var i = 0; i < rows.length; i++) {
				var r = rows[i];

				if (!r.getElementsByTagName('select')[0].value) {
					// Ignore rows where the operator has not been selected
					continue;
				}

				var row = {
					"column": r.cells[1].firstChild.value,
					"operator": r.cells[2].firstChild.value,
					"values": this.getValues(r)
				};

				if (!row.operator)
				{
					continue;
				}

				conditions.push(row);
			}

			groups.push({
				"operator": g.parentNode.querySelector('select[name=operator]').value,
				"join_operator": (a = g.parentNode.querySelector('select[name=join_operator]')) ? a.value : null,
				"conditions": conditions,
			});
		}

		return groups;
	};
}());