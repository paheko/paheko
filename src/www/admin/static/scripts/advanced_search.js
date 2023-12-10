g.script('scripts/lib/query_builder.js', () => {
	var div = document.getElementById('queryBuilder');
	var columns = JSON.parse(div.dataset.columns);
	var groups = JSON.parse(div.dataset.groups);

	var translations = {
		"after": "après",
		"before": "avant",
		"is equal to": "est égal à",
		"is equal to one of": "est égal à une des ces options",
		"is not equal to one of": "n'est pas égal à une des ces options",
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
	q.default_operator = "LIKE %?%";

	// Add specific condition just to have the column show up in result
	q.operators["1"] = "afficher cette colonne";

	for (var i in q.types_operators) {
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

	q.init(div);

	$('#queryBuilderForm').onsubmit = function () {
		$('#jsonQuery').value = JSON.stringify(q.export());
	};

	q.import(groups);
});
