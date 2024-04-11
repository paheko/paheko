(function () {
	const enhanceInput = (input) => {
		var list = document.getElementById(input.getAttribute('list'));

		if (!list) {
			return;
		}

		var mode;

		// Only autocomplete address for France right now
		if (list.hasAttribute('data-autocomplete') && list.dataset.autocomplete === 'address') {
			mode = 'address';
		}
		else if (list.options.length) {
			mode = 'static';
		}
		else {
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
			close();
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
					var option = options[i];
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

		input.addEventListener('mousedown', open);
		input.addEventListener('focus', open);
		input.addEventListener('blur', close);

		// If it's a static list of items, autocomplete
		if (mode === 'static') {
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

			var initial_options = [];

			Object.values(list.options).forEach(option => {
				option.dataset.search = g.normalizeString(option.getAttribute('value') ?? option.innerText);
				initial_options.push(option);
			});

			var options_search = null;

			input.addEventListener('input', e => {
				var value = g.normalizeString(input.value);
				var matching_options = [];
				var other_options = [];

				initial_options.forEach(option => {
					option.classList.remove('focus');

					if (value && option.dataset.search.includes(value)) {
						option.classList.add('match');
						matching_options.push(option);
					}
					else {
						option.classList.remove('match');
						other_options.push(option);
					}
				});

				list.innerHTML = '';

				matching_options.forEach(option => list.appendChild(option));
				other_options.forEach(option => list.appendChild(option));

				current = 0;
				list.options[0].classList.add('focus');
				open();
			});
		}
		// If the list autocompletes an address
		else if (mode === 'address') {
			var t;

			var autocomplete = () => {
				clearTimeout(t);
				list.innerHTML = '';

				if (!input.value.trim()) {
					close();
					return;
				}

				var country = $('#f_pays');

				if (!country || country.value !== 'FR') {
					return;
				}

				var fd = new FormData;
				fd.append('search', g.normalizeString(input.value));

				fetch(g.admin_url + 'common/autocomplete_address.php', {
					method: 'POST',
					cache: 'no-cache',
					body: fd
				}).then(r => r.json()).then(r => {
					list.innerHTML = '';

					if (!r.length) {
						return;
					}

					Object.values(r).forEach(e => {
						var o = new Option(e.label, e.label);
						Object.assign(o.dataset, e);

						// Don't use click or it won't be handled because of blur
						o.addEventListener('mousedown', (e) => {
							selectOption(o);
							e.preventDefault();
							return false;
						});

						list.appendChild(o);
					});

					current = 0;
					list.options[0].classList.add('focus');
					open();
				});
			};

			input.addEventListener('input', () => {
				window.clearTimeout(t);
				t = window.setTimeout(autocomplete, 500);
			});

			selectOption = (option) => {
				if (!option) {
					var i = getSelectedOptionIndex();
					if (i === null) {
						return;
					}
					option = list.options[i];
				}

				input.value = option.dataset.address;

				if (a = $('#f_code_postal')) {
					a.value = option.dataset.code;
				}

				if (a = $('#f_ville')) {
					a.value = option.dataset.city;
				}

				close();
			};
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