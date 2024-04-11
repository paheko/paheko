(function () {
	const enhanceInput = (input) => {
		var list = document.getElementById(input.getAttribute('list'));

		if (!list) {
			return;
		}

		var container = document.createElement('span');
		container.className = 'datalist';

		var input_container = document.createElement('span');

		var input2 = input.cloneNode(true);
		input2.removeAttribute('list');
		input2.setAttribute('autocomplete', 'off');
		input2.setAttribute('role', 'combobox');
		var list2 = list.cloneNode(true);
		list2.setAttribute('role', 'listbox');

		input_container.appendChild(input2);
		input_container.appendChild(list2);
		container.appendChild(input_container);

		input.parentNode.insertBefore(container, input.nextSibling);

		input.remove();
		list.remove();

		input = input2;
		list = list2;

		var open = () => {
			container.classList.add('open');
			let current = getSelectedOptionIndex();
		};

		var close = () => {
			container.classList.remove('open');
		};

		var selectOption = (option) => {
			if (!option) {
				var i = getSelectedOptionIndex();
				if (i === null) {
					return;
				}
				option = list.options[i];
			}

			input.value = option.getAttribute('value') ?? option.innerText;
			container.classList.remove('open');
		};

		var getSelectedOptionIndex = () => {
			if (current !== null) {
				return current;
			}
			else if (!input.value) {
				return null;
			}

			for (var i = 0; i < list.options.length; i++) {
				var option = list.options[i];
				if (input.value === option.getAttribute('value') ?? option.innerText) {
					current = i;
					return current;
				}
			}

			return null;
		};

		var current = null;

		input.addEventListener('keydown', e => {
			let move;

			if (e.key === 'Enter') {
				if (!container.classList.contains('open')) {
					return;
				}
				selectOption();
				e.preventDefault();
				return false;
			}
			else if (e.key === 'ArrowDown') {
				move = 1;
			}
			else if (e.key === 'ArrowUp') {
				move = -1;
			}
			else {
				return;
			}

			open();
			e.preventDefault();
			var options = list.options;
			var next = null;

			if (!options.length) {
				return;
			}

			if (current === null && input.value) {
				for (var i = 0; i < options.length; i++) {
					if (input.value === option.getAttribute('value') ?? option.innerText) {
						current = i;
						break;
					}
				}
			}

			if (current === null) {
				next = 0;
			}
			else {
				next = current + move;
			}

			if (next >= options.length || next < 0) {
				return;
			}

			if (c = list.querySelector('.focus')) {
				c.classList.remove('focus');
			}

			options[next].classList.add('focus');
			current = next;
		});

		input.addEventListener('input', e => {
			console.log('ok');
			open();
			var new_options = [];
			// TODO: store original order of options
			// TODO: put matching options at top of list, with <mark> matching others 
		});

		input.addEventListener('click', open);
		input.addEventListener('focus', open);
		input.addEventListener('blur', close);

		if (list.querySelector('option')) {
			var btn = document.createElement('button');
			btn.dataset.icon = '↓';
			btn.type = 'button';
			btn.setAttribute('tabindex', '-1');

			// Don't use onclick or it won't be handled because of blur
			btn.onmousedown = (e) => {
				if (container.classList.contains('open')) {
					e.preventDefault();
					input.blur();
					return;
				}

				input.focus() && input.select();
				open();
				return false;
			};

			container.appendChild(btn);
		}

		list.addEventListener('mouseover', (e) => {
			if (c = list.querySelector('.focus')) {
				c.classList.remove('focus');
			}
		});

		list.querySelectorAll('option').forEach(option => {
			// Don't use click or it won't be handled because of blur
			option.addEventListener('mousedown', (e) => {
				selectOption(option);
				e.preventDefault();
				return false;
			});
		});
	};

	document.querySelectorAll('input[list], textarea[list]').forEach((e) => {
		enhanceInput(e);
	});
}());