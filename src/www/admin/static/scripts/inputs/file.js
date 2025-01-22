(function () {
	if (!DataTransfer || !FileReader || !File) {
		return;
	}

	// This is using DataTransfer API to replace FileList in input, so that files can just be sent using the POST

	const enhanceFileInput = (input) => {
		// When a file has been selected
		const handleChange = () => {
			if (!input.multiple) {
				dt.items.clear();
				preview.innerHTML = '';
			}

			if (!input.files.length) {
				label.innerHTML = label_unselected;
				return;
			}

			Array.from(input.files).forEach(addItem);

			updateLabel();
		};

		const handleUpload = (e) => {
			input.files = dt.files;

			let total = 0;
			Array.from(input.files).forEach((f) => total += f.size);

			// Check size
			if (total >= max_size - 1000) {
				alert("Les fichiers sélectionnés dépassent la taille maximale autorisée. Merci de choisir moins de fichiers.");
				e.preventDefault();
				e.stopPropagation();
				return false;
			}

			input.form.firstElementChild.classList.add('progressing');
		};

		const updateLabel = () => {
			let l;

			if (dt.files.length == 0) {
				l = label_unselected;
			}
			else if (dt.files.length == 1) {
				l = label_selected[1];
			}
			else {
				l = label_selected[0];
			}

			label.innerHTML = '<p class="help">' + l.replace(/%d/, dt.files.length) + '</p>';

			let total = 0;

			Array.from(dt.files).forEach((f) => total += f.size);

			// Let's assume the rest of the form is only 1000 extra bytes
			if (!split_upload && total >= max_size - 1000) {
				label.innerHTML += '<p class="alert block">Les fichiers sélectionnés dépassent la taille maximale autorisée. Merci de choisir moins de fichiers.</p>';
			}

			g.resizeParentDialog();
		};

		const getByteSize = (size) => {
			if (size < 1024)
				return (Math.round(size / 1024 * 10) / 10) + ' Ko';
			else if (size < 1024*1024)
				return Math.round(size / 1024) + ' Ko';
			else
				return (Math.round(size / 1024 / 1024 * 100) / 100) + ' Mo';
		};

		const addItem = (f) => {
			// Skip if duplicate
			for (let i = 0; i < dt.files.length; i++) {
				let f2 = dt.files[i];
				if (f2.name == f.name && f2.size == f.size) {
					return;
				}
			}

			dt.items.add(f);

			let size_msg = (f.size > max_size) ? '<span class="error">' + '(dépasse la taille autorisée)' + '</span>' : '';

			let item = document.createElement('tr');
			item.innerHTML = `<td class="img"></td>
				<th>${f.name} ${size_msg}</th>
				<td class="num">${getByteSize(f.size)}</td>
				<td class="actions"><button data-icon="✘" class="icn-btn" type="button" title="Enlever de la liste"></button></td>`;

			if (size_msg) {
				item.className = 'disabled';
			}

			item.querySelector('button').onclick = () => {
				let idx = [...preview.children].indexOf(item);
				dt.items.remove(idx);
				item.remove();
				updateLabel();
			};

			preview.appendChild(item);

			// If image, add preview thumbnail
			if (!f.type.startsWith('image/')) {
				return;
			}

			const img = document.createElement('img');
			img.file = f;
			item.querySelector('.img').appendChild(img);

			const reader = new FileReader();
			reader.onload = (e) => {
				img.onload = g.resizeParentDialog;
				img.src = e.target.result;
			};
			reader.readAsDataURL(f);
		};

		const max_size = input.form.querySelector('input[type=hidden][name=MAX_FILE_SIZE]').value;
		const split_upload = 'splitUpload' in input.form.dataset && input.multiple;

		let label_unselected = (input.multiple ? '…ou glisser-déposer des fichiers ici' : '…ou glisser-déposer un fichier ici');
		let label_button = (input.multiple ? 'Sélectionner des fichiers' : 'Sélectionner un fichier');
		let label_selected = ['%d fichiers sélectionnés', '1 fichier sélectionné'];

		input.form.addEventListener('submit', handleUpload, false);

		// Hide real input
		input.style.display = 'none';

		let container = document.createElement('div');
		container.className = 'file-selector';

		let btn = document.createElement('button');
		btn.className = 'icn-btn';
		btn.setAttribute('data-icon', '⇑');
		btn.type = 'button';
		btn.innerHTML = label_button;
		btn.onclick = () => input.click();

		let label = document.createElement('label');
		label.setAttribute('for', input.id);
		label.innerHTML = label_unselected;

		let preview = document.createElement('table');
		preview.className = 'preview list';

		container.appendChild(btn);
		container.appendChild(label);
		container.appendChild(preview);

		let dt = new DataTransfer();

		const drag = (e) => {
			e.stopPropagation();
			e.preventDefault();
		};

		container.addEventListener('dragenter', drag, false);
		container.addEventListener('dragover', drag, false);

		container.addEventListener('drop', (e) => {
			e.stopPropagation();
			e.preventDefault();

			if (!e.dataTransfer.files.length) {
				return;
			}

			if (!input.multiple) {
				dt.items.clear();
				preview.innerHTML = '';
			}

			Array.from(e.dataTransfer.files).forEach(addItem);
			updateLabel();
		}, false);

		input.addEventListener('change', handleChange);
		input.addItem = addItem;

		input.parentNode.insertBefore(container, input.nextSibling);

		// Support paste events, if there's only one file input in the document
		if (document.querySelectorAll('input[type=file][data-enhanced]').length == 1) {
			const IMAGE_MIME_REGEX = /^image\/(p?jpeg|gif|png)$/i;

			document.addEventListener('paste', (e) => {
				let items = e.clipboardData.items;

				for (var i = 0; i < items.length; i++) {
					if (IMAGE_MIME_REGEX.test(items[i].type)) {
						let f = items[i].getAsFile();
						let name = f.name.replace(/\./, '-' + (+(new Date)) + '.');
						let f2 = new File([f], name, {type: f.type});
						addItem(f2);
						e.preventDefault();
						return;
					}
				}
			});
		}
	};

	document.querySelectorAll('input[type=file][data-enhanced]').forEach((e) => {
		enhanceFileInput(e);
	});
}());