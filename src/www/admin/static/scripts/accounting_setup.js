function initLine(row)
{
	var removeBtn = row.querySelector('button[name="remove_line"]');
	removeBtn.onclick = () => {
		var count = $('tbody tr').length;

		if (count <= 1) {
			alert("Il n'est pas possible de supprimer cette ligne.");
			return false;
		}

		row.parentNode.removeChild(row);
	};
}

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