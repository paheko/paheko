(function () {
let create = false;

function selectService(elm, first_load) {
	$('[data-service]').forEach((e) => {
		e.style.display = ('s' + elm.value == e.getAttribute('data-service')) ? 'block' : 'none';
	});

	let expiry = $('#f_expiry_date');

	if (create && (!first_load || !expiry.value)) {
		// Set the expiry date
		expiry.value = elm.dataset.expiry;
	}

	var first = document.querySelector('[data-service="s' + elm.value + '"] input[name=id_fee]');

	if (first) {
		first.checked = true;
		selectFee(first);
	}
}

function selectFee(elm) {
	var amount = parseInt(elm.getAttribute('data-user-amount'), 10);

	// Toggle accounting part of the form
	var accounting = elm.getAttribute('data-account') ? true : false;
	g.toggle('.accounting', accounting);

	if (accounting) {
		$('#f_create_payment_1').checked = true;
		let btn = $('#f_account_container').firstElementChild;
		btn.value = btn.value.replace(/&year=\d+/, '') + '&year=' + elm.getAttribute('data-year');
	}

	// Fill the amount paid by the user
	if (amount && create) {
		$('#f_amount').value = g.formatMoney(amount);
	}
}

function initForm() {
	$('input[name=id_service]').forEach((e) => {
		e.onchange = () => { selectService(e); };
	});

	$('input[name=id_fee]').forEach((e) => {
		e.onchange = () => { selectFee(e); };
	});

	var selected = document.querySelector('input[name="id_service"]:checked') || document.querySelector('input[name="id_service"]');
	selected.checked = true;

	g.toggle('.accounting', false);
	selectService(selected, true);

	if (create) {
		let checkbox = $('#f_create_payment_1');
		checkbox.onchange = (e) => {
			g.toggle('.accounting dl', checkbox.checked);
			//$('#f_amount').required = checkbox.checked;
		};
	}

	// Automatically increase expiry date when date is changed
	let date_input = $('#f_date');
	let expiry_input = $('#f_expiry_date');

	date_input.onchange = (e) => {
		if (!selected.dataset.duration) {
			return;
		}

		let d = date_input.value.split('/').reverse();
		d = new Date(d[0], d[1]-1, d[2], 12);
		d.setDate(d.getDate() + parseInt(selected.dataset.duration, 10));
		expiry_input.value = d.toISOString().split('T')[0].split('-').reverse().join('/');
	};

	create = date_input.form.dataset.create == 'true';
}

g.onload(initForm);

})();