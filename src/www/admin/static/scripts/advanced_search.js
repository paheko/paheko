g.script('scripts/lib/query_builder.js', () => {
	var div = document.getElementById('queryBuilder');
	var columns = JSON.parse(div.dataset.columns);
	var groups = JSON.parse(div.dataset.groups);

	var translations = {
		"after": "après",
		"before": "avant",
		"is equal to": "est égal à",
		"is equal to one of": "est égal à une de ces options",
		"is not equal to one of": "n'est pas égal à une de ces options",
		"is not equal to": "n'est pas égal à",
		"is greater than": "est supérieur à",
		"is greater than or equal to": "est supérieur ou égal à",
		"is less than": "est inférieur à",
		"is less than or equal to": "est inférieur ou égal à",
		"is between": "est situé entre",
		"is not between": "n'est pas situé entre",
		"is null": "n'est pas renseigné",
		"is not null": "est renseigné",
		"begins with": "commence par",
		"doesn't begin with": "ne commence pas par",
		"ends with": "se termine par",
		"doesn't end with": "ne se termine pas par",
		"contains": "contient",
		"doesn't contain": "ne contient pas",
		"matches one of": "correspond à",
		"doesn't match one of": "ne correspond pas à",
		"is true": "oui",
		"is false": "non",
		"Matches ALL of the following conditions:": "Correspond à TOUS les critères suivants :",
		"Matches ANY of the following conditions:": "Correspond à UN des critères suivants :",
		"Doesn't match ANY of the following conditions:": "Ne correspond à AUCUN des critères suivants :",
		"Add a new set of conditions below this one": "— Ajouter un groupe de critères",
		"Remove this set of conditions": "— Supprimer ce groupe de critères",
		"AND": "ET",
		"OR": "OU"
	};

	var q = new SQLQueryBuilder(columns);
	q.__ = function (str) {
		return translations[str] ?? str;
	};
	q.loadDefaultOperators();
	q.types_operators['enum_restricted'] = {
		"= ?": q.__("is equal to"),
		"!= ?": q.__("is not equal to"),
	};
	q.default_operator = ["LIKE %?%", "1"];

	// Add specific condition just to have the column show up in result
	q.operators["1"] = "afficher cette colonne";
	q.types_operators['money'] = q.types_operators['integer'];
	q.types_operators['tel'] = q.types_operators['text'];

	for (var i in q.types_operators) {
		if (i === 'enum_restricted') {
			continue;
		}

		q.types_operators[i]["1"] = q.operators["1"];
	}

	q.buildInput = function (type, label, column) {
		if (label == '+')
		{
			label = '➕';
		}
		else if (label == '-')
		{
			label = '➖';
		}

		if (type == 'button')
		{
			var i = document.createElement('button');
			i.className = 'icn-btn';
			i.type = 'button';
			i.setAttribute('data-icon', label);
		}
		else {
			var i = document.createElement('input');
			i.type = type == 'integer' ? 'number' : type;
			i.value = label;
		}

		return i;
	};

	// This is for handling specific behaviour of subscription search!
	// If subscription column is selected, two more columns are added: subscription_active and subscription_paid
	// If subscription column criteria is not '= ?', then they disappear
	// If subscription criteria is "none" then they disappear
	function rowChanged(row) {
		var select = row.querySelector('td.column select');
		var name = select.value;
		var values_select = row.querySelector('td.values select');
		var column = q.columns['subscription'];

		// Don't interact
		if (!((name === 'subscription' || select.dataset.oldValue === 'subscription') && values_select)) {
			return;
		}

		var operator = row.querySelector('td.operator select').value;
		var value = values_select.value;
		var hidden = name !== 'subscription' || operator === '!= ?' || value === '' || value === '0';
		var next = row.nextElementSibling;

		for (var i = 0; i < column.force.length; i++) {
			// Make existing criterias (from import) read-only
			if (!hidden && next && next.querySelector('select').value === column.force[i]) {
				var select = next.querySelector('td.column select');
				select.options[select.selectedIndex].hidden = false;
				select.setAttribute('aria-readonly', 'true');
				next.classList.add('forced');
				next = next.nextElementSibling;
			}
			// Add rows for forced columns
			else if (!hidden) {
				var f = column.force[i];
				row = q.addRow(q.findAncestor(row, 'fieldset'), row);
				var o = row.querySelector('.column select option[value="' + f + '"]');
				o.parentNode.value = f;
				q.switchColumn(o.parentNode);
				o.parentNode.setAttribute('aria-readonly', 'true');
				row.classList.add('forced');
				next = row.nextElementSibling;
			}
			// remove forced columns
			else {
				if (next && next.classList.contains('forced')) {
					next.remove();
				}

				next = row.nextElementSibling;
			}
		}
	}

	q.addEventListener('columnchange', (select, row) => rowChanged(row));
	q.addEventListener('operatorchange', (select, row) => rowChanged(row));
	q.addEventListener('valuechange', (select, row) => rowChanged(row));

	q.init(div);

	$('#queryBuilderForm').onsubmit = function () {
		$('#jsonQuery').value = JSON.stringify(q.export());
	};

	q.import(groups);
});
