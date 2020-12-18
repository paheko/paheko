function initTransactionForm(is_new) {
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
					inputs[+!k].value = '0';
					updateTotals();
				}
			};

			if (+i.value == 0 && +inputs[+!k].value != 0) {
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

	// Add row "plus" button
	$('.transaction-lines tfoot button')[0].onclick = () => {
		let lines = $('.transaction-lines tbody tr');
		var line = lines[lines.length - 1];
		var n = line.cloneNode(true);

		// Reset label and reference
		n.querySelectorAll('input').forEach((i) => {
			if (!i.name.match(/label|reference/)) {
				return;
			}

			i.value = '';
		})

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

	// Hide type specific parts of the form
	function hideAllTypes() {
		g.toggle('[data-types]', false);
	}

	// Toggle parts of the form when a type is selected
	function selectType(v) {
		hideAllTypes();
		g.toggle('[data-types~=t' + v + ']', true);
		g.toggle('[data-types=all-but-advanced]', v != 0);
		// Disable required form elements, or the form won't be able to be submitted
		$('[data-types=all-but-advanced] input[required]').forEach((e) => {
			e.disabled = v == 'advanced' ? true : false;
		});

	}

	var radios = $('fieldset input[type=radio][name=type]');

	radios.forEach((e) => {
		e.onchange = () => {
			document.querySelectorAll('fieldset').forEach((e, k) => {
				if (!is_new || k == 0 || e.dataset.types) return;
				g.toggle(e, true);
				g.toggle('p.submit', true);
			});
			selectType(e.value);
		};
	});

	hideAllTypes();

	// In case of a pre-filled form: show the correct part of the form
	var current = document.querySelector('input[name=type]:checked');
	if (current) {
		selectType(current.value);
	}

	if (is_new) {
		document.querySelectorAll('fieldset').forEach((e, k) => {
			if (k == 0) return;
			g.toggle(e, false);
			g.toggle('p.submit', false);
		});
	}
}
