function initTransactionForm() {
	// Advanced transaction: line management
	var lines = $('.transaction-lines tbody tr');

	function initLine(row) {
		row.querySelector('button[name="remove_line"]').onclick = () => {
			var count = $('.transaction-lines tbody tr').length;

			if (count <= 2) {
				alert("Il n'est pas possible d'avoir moins de deux lignes dans une écriture.");
				return false;
			}

			row.parentNode.removeChild(row);
			updateTotals();
		};

		// Update totals and disable other amount input
		var inputs = row.querySelectorAll('input.money');

		inputs.forEach((i, k) => {
			i.onkeyup = (e) => {
				if (!e.key.match(/^([0-9,.]|Separator|Backspace|Delete)$/i)) {
					return true;
				}

				if (i.readOnly) {
					i.value = e.key.match(/[0-9.,]/) ? e.key : '0';
					i.readOnly = false;
					inputs[+!k].readOnly = true;
					inputs[+!k].value = '0';
				}
				else if (!inputs[+!k].readOnly) {
					inputs[+!k].readOnly = true;
					inputs[+!k].value = '0';
				}

				updateTotals();
			};

			if (+i.value == 0 && +inputs[+!k].value != 0) {
				i.readOnly = true;
				i.value = '0';
			}
		});
	}

	lines.forEach(initLine);

	function updateTotals() {
		var amounts = $('.transaction-lines tbody input.money');
		var debit = credit = 0;

		amounts.forEach((i) => {
			var v = i.value.replace(/[^0-9.,]/, '');
			if (v.length == 0) return;

			v = v.split(/[,.]/);
			var d = v.length == 2 ? v[1] : '0';
			v = v[0] + (d + '00').substr(0, 2);
			v = parseInt(v, 10);

			if (i.name.match(/debit/)) {
				debit += v;
			}
			else {
				credit += v;
			}
		});

		$('#lines_message').innerHTML = (debit === credit) ? '' : '<span class="alert">Écriture non équilibrée</span>';

		debit = debit ? debit + '' : '000';
		credit = credit ? credit + '' : '000';
		$('#f_debit_total').value = (debit.substr(0, debit.length-2) || '0') + ',' + debit.substr(-2);
		$('#f_credit_total').value = (credit.substr(0, credit.length-2) || '0') + ',' + credit.substr(-2);
	}

	// Add row button
	$('.transaction-lines tfoot button')[0].onclick = () => {
		var line = $('.transaction-lines tbody tr')[0];
		var n = line.cloneNode(true);
		n.querySelectorAll('input').forEach((e) => {
			e.value = '';
		});
		n.querySelector('.input-list .label').innerHTML = '';
		var b = n.querySelector('.input-list button');
		b.onclick = () => {
			g.current_list_input = b.parentNode;
			g.openFrameDialog(b.value);
			return false;
		};
		initLine(n);
		line.parentNode.appendChild(n);
	};

	updateTotals();
}
