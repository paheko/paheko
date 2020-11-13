function initTransactionForm() {
	// Advanced transaction: line management
	var lines = $('.transaction-lines tbody tr');

	function initLine(row) {
		var removeBtn = row.querySelector('button[name="remove_line"]');
		removeBtn.onclick = () => {
			var count = $('.transaction-lines tbody tr').length;
			var min = removeBtn.getAttribute('min');

			if (count <= min) {
				alert("Il n'est pas possible d'avoir moins de " + min + " lignes dans une écriture.");
				return false;
			}

			row.parentNode.removeChild(row);
			updateTotals();
		};

		// To be able to change input just by pressing up/down
		var inputs = row.querySelectorAll('input, select, button');

		inputs.forEach((i, k) => {
			i.onkeydown = (e) => {
				if (e.key == 'ArrowUp' && (p = row.previousElementSibling)) {
					p.querySelectorAll('input, select, button')[k].focus();
					return false;
				}
				else if (e.key == 'ArrowDown' && (n = row.nextElementSibling)) {
					n.querySelectorAll('input, select, button')[k].focus();
					return false;
				}
			};
		});

		// Update totals and disable other amount input
		var inputs = row.querySelectorAll('input.money');

		inputs.forEach((i, k) => {
			i.onkeyup = (e) => {
				var v = i.value.replace(/[^0-9,.]/);
				if (v.length && v != 0) {
					i.classList.remove('disabled');
					inputs[+!k].classList.add('disabled');
					inputs[+!k].value = '0';
					updateTotals();
				}
			};

			if (+i.value == 0 && +inputs[+!k].value != 0) {
				i.classList.add('disabled');
				i.value = '0';
			}
		});
	}

	lines.forEach(initLine);

	function updateTotals() {
		var amounts = $('.transaction-lines tbody input.money');
		var debit = credit = 0;

		amounts.forEach((i) => {
			if (!i.value) {
				return;
			}

			var v = g.getMoneyAsInt(i.value);

			if (i.name.match(/debit/)) {
				debit += v;
			}
			else {
				credit += v;
			}
		});

		if (m = $('#lines_message')) {
			var diff = credit - debit;
			m.innerHTML = (!diff) ? '' : '<span class="alert">Écriture non équilibrée (' + g.formatMoney(diff) + ')</span>';
		}

		debit = debit ? debit + '' : '000';
		credit = credit ? credit + '' : '000';
		$('#f_debit_total').value = g.formatMoney(debit);
		$('#f_credit_total').value = g.formatMoney(credit);
	}

	// Add row button
	$('.transaction-lines tfoot button')[0].onclick = () => {
		var line = $('.transaction-lines tbody tr')[0];
		var n = line.cloneNode(true);
		n.querySelectorAll('input').forEach((e) => {
			e.value = e.className.match(/money/) ? '0' : '';
		});
		if (l = n.querySelector('.input-list .label')) {
			l.parentNode.removeChild(l);
		}
		var b = n.querySelector('.input-list button');
		b.onclick = () => {
			g.current_list_input = b.parentNode;
			g.openFrameDialog(b.value);
			return false;
		};
		line.parentNode.appendChild(n);
		initLine(n);
	};

	updateTotals();
}
