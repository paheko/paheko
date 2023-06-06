(function () {
	let form = document.getElementById('restoreDocuments');
	form.style.display = 'block';

	const MAX_UNCOMPRESSED_SIZE = 1024*1024*1024*10; // 10 GB
	const MAX_FILE_SIZE = form.querySelector('input[name="MAX_FILE_SIZE"]').value - 250;

	let status = null;

	let input = document.getElementById('f_file');

	let failed_entries = [];
	let fd = new FormData(form);
	fd.delete('file');
	fd.append('restore', 1);

	form.addEventListener('submit', processZip);

	async function processZip(e) {
		g.openDialog('<div class="progressing block"><span class="progress-status"></span></div><p style="width: 400px; height: 1.5em; text-align: center; overflow: hidden; text-overflow: ellipsis;" id="unzip-status-update"></p>', {close: false});
		status = document.querySelector('#unzip-status-update');

		form.disabled = true;
		e.preventDefault();

		if (!input.files.length) {
			alert('Aucun fichier sélectionné');
			return false;
		}

		if (!input.files[0].name.match(/\.zip$/i)) {
			alert('Le fichier sélectionné n\'est pas un fichier ZIP.');
			return false;
		}

		try {
			var {entries} = await unzipit(input.files[0]);
		}
		catch (error) {
			console.log(error);
			alert(error);
			location.href = location.href;
			return;
		}

		let total_size = 0;

		// print all entries and their sizes
		for (const [name, entry] of Object.entries(entries)) {
			total_size += entry.size;
		}

		if (total_size > MAX_UNCOMPRESSED_SIZE) {
			alert('Archive ZIP trop grosse !');
			return;
		}

		for (const [name, entry] of Object.entries(entries)) {
			// Skip directories
			if (entry.isDirectory) {
				continue;
			}

			if (entry.size > MAX_FILE_SIZE) {
				// Skip files that are too large
				failed_entries.push(name);
				continue;
			}

			if (entry.size == 0) {
				// Skip empty files
				continue;
			}

			try {
				const blob = await entry.blob();
				const r = await upload(name, blob, entry.compressionMethod == 8 ? true : false);
			}
			catch (error) {
				console.log(error);
				alert(error);
				location.href = location.href;
				return;
			}
		}

		location.href = form.action + '?ok&failed=' + failed_entries.length;
		return false;
	}

	async function upload(name, blob, compressed) {
		let dt = new DataTransfer();
		let file_name = name.replace(/^.*\/([^\/]+)$/, '$1');

		let f = new File([blob], file_name);

		status.innerHTML = name;

		dt.items.add(f);

		fd.delete('file1');
		fd.delete('target');
		fd.delete('compressed');
		fd.append('target', name);
		fd.append('compressed', compressed ? 1 : 0);
		fd.append('file1', dt.files[0], file_name);

		const response = await fetch(form.action, {
			method: 'POST',
			body: fd
		});

		if (!response.ok) {
			const message = `Erreur du serveur : ${response.status}`;
			throw new Error(message);
		}

		const body = await response.text();

		if (body.substr(0, 1) != '{') {
			console.log(body);
			throw "Réponse invalide du serveur";
		}

		let result = JSON.parse(body);

		if (result.error) {
			console.log(body);
			throw result.error;
		}

		return true;
	}
}());