function initLine(row)
{
	var removeBtn = row.querySelector('button[name="remove_line"]');
	removeBtn.onclick = () => {
		var count = $('tbody tr').length;

		if (count <= 1) {
			if (confirm('Ne créer aucun compte bancaire ?')) {
				document.querySelector('form table').remove();
				g.toggle('#no_accounts_msg', true);
				return true;
			}
			return false;
		}

		row.parentNode.removeChild(row);
	};
}

if ($('table').length) {
	$('tbody tr').forEach(initLine);

	// Add row "plus" button
	$('tfoot button')[0].onclick = () => {
		let lines = $('tbody tr');
		var line = lines[lines.length - 1];
		var n = line.cloneNode(true);

		// Reset label and reference
		n.querySelectorAll('input').forEach((i) => {
			i.value = '';
		})

		line.parentNode.appendChild(n);
		initLine(n);
	};
}

if (c = document.forms[0].country) {
	function changeCountry() {
		// Unselect chart
		if (chart = document.querySelector('input[name="chart"]:checked')) {
			chart.checked = false;
		}

		g.toggle('.chart', c.value);
		g.toggle('.charts-FR, .charts-BE, .charts-CH', false);
		g.toggle('.charts-' + c.value, true);
		changeChart();
	}

	$('input[name=country]').forEach(i => i.onchange = changeCountry);
	changeCountry();

	function changeChart() {
		var chart = document.forms[0].chart;
		g.toggle('.submit', chart.value);
	}

	changeChart();
	$('input[name=chart]').forEach(i => i.onchange = changeChart);
}
