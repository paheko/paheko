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
		};

		var close = () => {
			container.classList.remove('open');
		};

		var current = null;

		input.addEventListener('keyup', e => {
			let move;

			if (e.key === 'ArrowDown') {
				move = 1;
			}
			else if (e.key === 'ArrowUp') {
				move = -1;
			}

			if (move) {
				e.preventDefault();
				var options = list.options;
				var next = null;

				if (!options.length) {
					return;
				}

				if (current !== null) {
					for (var i = 0; i < options.length; i++) {
						if (options[i] === current) {
							next = i + move;
							break;
						}
					}

					options[current].classList.remove('focus');
				}
				else if (move === -1) {
					next = options.length - 1;
				}
				else {
					next = 0;
				}

				if (next >= options.length) {
					return;
				}
				else if (next < 0) {
					return;
				}

				console.log(current, next);

				options[next].classList.add('focus');
				current = next;

				return;
			}
		});
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

		list.querySelectorAll('option').forEach(option => {
			// Don't use click or it won't be handled because of blur
			option.addEventListener('mousedown', (e) => {
				input.value = option.getAttribute('value') ?? option.innerText;
				container.classList.remove('open');
				e.preventDefault();
				return false;
			});
		});
	};

	document.querySelectorAll('input[list], textarea[list]').forEach((e) => {
		enhanceInput(e);
	});
}());