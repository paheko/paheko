(function () {

	$('[data-upload-url]').forEach(enableFileDragDrop);

	// Detect directories to dismiss them
	// see https://web.dev/patterns/files/drag-and-drop-directories/
	const supportsFileSystemAccessAPI = 'getAsFileSystemHandle' in DataTransferItem.prototype;
	const supportsWebkitGetAsEntry = 'webkitGetAsEntry' in DataTransferItem.prototype;

	function isItemFile(item) {
		if (item.kind !== 'file') {
			return false;
		}
		else if (supportsFileSystemAccessAPI && item.getAsFileSystemHandle().kind == 'directory') {
			return false;
		}
		else if (supportsWebkitGetAsEntry && (entry = item.webkitGetAsEntry()) && entry.isDirectory) {
			return false;
		}
		else {
			return true;
		}
	}

	function enableFileDragDrop(p) {
		var drag_elements = [];
		var upload_url = p.dataset.uploadUrl;
		var upload_token_name = p.dataset.uploadTokenName;
		var upload_token_value = p.dataset.uploadTokenValue;

		var bg = document.createElement('div');
		bg.className = 'overlay';
		var msg = document.createElement('div');
		msg.className = 'message';
		bg.appendChild(msg);
		p.appendChild(bg);

		if (p === document.body) {
			window.addEventListener('paste', (e) => {
				const files = [...e.clipboardData.items]
					.filter(isItemFile)
					.map(item => item.getAsFile());

				if (!files.length) {
					return;
				}

				e.preventDefault();
				document.body.appendChild(bg);
				document.body.classList.add('loading');

				for (var i = 0; i < files.length; i++) {
					let f = files[i];
					let name = f.name == 'image.png' ? f.name.replace(/\./, '-' + (+(new Date)) + '.') : f.name;

					msg.innerText = 'Envoi de ' + name + '…';

					var r = upload(upload_url, upload_token_name, upload_token_value, f, name);

					if (!r) {
						break;
					}
				}

				window.setTimeout(() => {
					location.href = location.href;
				}, 500);
			});
		}

		p.addEventListener('dragover', (e) => {
			e.preventDefault();
			e.stopPropagation();
		});

		p.addEventListener('dragenter', (e) => {
			drag_elements.push(e.target);

			e.preventDefault();
			e.stopPropagation();

			if (drag_elements.length == 1) {
				p.classList.add('dragging');
				msg.innerText = 'Déposer des fichiers ici';
			}
		});

		p.addEventListener('dragleave', (e) => {
			var idx = drag_elements.indexOf(e.target);

			if (idx === -1) {
				return;
			}

			drag_elements.splice(idx, 1);

			e.preventDefault();
			e.stopPropagation();

			if (drag_elements.length === 0) {
				p.classList.remove('dragging');
			}
		});

		p.addEventListener('drop', (e) => {
			e.preventDefault();
			e.stopPropagation();
			p.classList.remove('dragging');

			drag_elements = [];

			const files = [...e.dataTransfer.items]
				.filter(isItemFile)
				.map(item => item.getAsFile());

			if (!files.length) return;

			document.body.appendChild(bg);
			document.body.classList.add('loading');

			(async () => {
				for (var i = 0; i < files.length; i++) {
					var f = files[i];
					msg.innerText = 'Envoi de ' + f.name + '…';

					var r = upload(upload_url, upload_token_name, upload_token_value, f);

					if (!r) {
						break;
					}
				}

				window.setTimeout(() => {
					location.href = location.href;
				}, 500);
			})();
		});
	}

	async function upload(url, token_name, token_value, file, file_name) {
		var data = new FormData();
		data.append('file', file, file_name ? file_name : file.name);
		data.append(token_name, token_value);
		data.append('upload', 'yes');

		var r = await fetch(url, {
			'method': 'POST',
			'body': data,
			'headers': {
				'Accept': 'application/json'
			}
		});

		if (r.ok) {
			return true;
		}

		console.error(r);

		if (!r.headers.get('content-type').match(/json/)) {
			alert('Erreur d\'envoi : ' + r.status + ' ' + r.statusText);
			return false;
		}

		var r = await r.json();
		console.error(r);

		if (typeof r.message !== 'undefined') {
			alert('Erreur : ' + r.message);
		}
		else {
			alert('Erreur inconnue');
		}

		return false;
	}
})();