function changeType() {
	var type = $('#f_type').value;
	g.toggle('.type-select, .type-multiple, .type-virtual', false);
	g.toggle('.type-' + type, true);
	g.toggle('.type-not-virtual', false);
	g.toggle('.type-not-password', false);
	g.toggle('.type-not-virtual', type !== 'virtual');
	g.toggle('.type-not-password', type !== 'password');
	g.toggle('.type-not-password.type-not-virtual', type !== 'password' && type !== 'virtual');
}

$('#f_type').onchange = changeType;

function normalizeString(str) {
	return str.normalize('NFD').replace(/[\u0300-\u036f]/g, "")
}

var label = $('#f_label');
label.onkeyup = () => {
	var n = $('#f_name');
	if (!n || n.disabled) {
		return;
	}

	n.value = normalizeString(label.value).toLowerCase().replace(/[^a-z_]+/g, '_');
};

changeType();

var addBtn = document.createElement('button');
addBtn.type = "button";
addBtn.dataset.icon = "➕";
addBtn.className = "icn-btn add";
addBtn.title = "Ajouter une option";

var delBtn = document.createElement('button');
delBtn.type = "button";
delBtn.dataset.icon = "➖";
delBtn.className = "icn-btn delete";
delBtn.title = "Enlever cette option";

var options = $('.options dd');

options.forEach((o, i) => {
	if (i == 0) {
		return;
	}

	let btn = delBtn.cloneNode(true);
	btn.onclick = delOption;
	o.appendChild(btn);
});

addPlusButton();

function addOption(e) {
	var options = $('.options dd');
	var target = e.target;
	var new_option = target.parentNode.cloneNode(true);
	new_option.querySelector('input').value = '';

	new_option.querySelectorAll('button').forEach((b) => b.remove());

	var btn = delBtn.cloneNode();
	btn.onclick = delOption;
	new_option.appendChild(btn);

	target.parentNode.parentNode.appendChild(new_option);
	target.remove(); // Remove add button from previous line

	addPlusButton();
}

function delOption(e) {
	var options = $('.options dd');
	if (options.length == 1) {
		return;
	}

	e.target.parentNode.remove();
	addPlusButton();
}

function addPlusButton () {
	var options = $('.options dd');
	var btn = addBtn.cloneNode();
	btn.onclick = addOption;

	if (options.length < 30) {
		let last = options[options.length - 1];

		if (last.querySelector('.add')) {
			return;
		}

		last.appendChild(btn);
	}
}