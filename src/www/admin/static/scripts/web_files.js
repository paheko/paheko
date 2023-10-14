(function () {
	g.onload(function () {
		function insertImageHelper(file, from_upload) {
			if (!document.querySelectorAll)
			{
				window.parent.te_insertImage(file.name, 'center');
				return true;
			}

			var f = document.getElementById('insertImage');
			f.style.display = 'block';
			f.onsubmit = () => { window.parent.te_insertImage(file.name, 'center', f.f_caption.value); return false; }

			var inputs = f.querySelectorAll('input[type=button]');

			for (var i = 0; i < inputs.length; i++)
			{
				inputs[i].onclick = function(e) {
					window.parent.te_insertImage(file.name, e.target.name, f.f_caption.value);
				};
			}

			f.querySelector('dd.image').innerHTML = '';
			var img = document.createElement('img');
			img.src = file.thumb;
			img.alt = '';
			f.querySelector('dd.image').appendChild(img);

			f.f_caption.focus();

			f.querySelector('dd.cancel [type=reset]').onclick = function() {
				f.style.display = 'none';

				if (from_upload)
				{
					location.href = location.href;
				}
			};
		}

		window.insertHelper = function(data) {
			var file = (data.file || data);

			if (file.image)
			{
				insertImageHelper(file, true);
			}
			else
			{
				window.parent.te_insertFile(file.name);
			}

			return true;
		}

		document.querySelectorAll('a[data-insert]').forEach((a) => {
			a.onclick = function (e) {
			   insertHelper({
					name: this.dataset.name,
					image: this.dataset.insert == 'image',
					thumb: this.dataset.thumb
				});
				return false;
			};
		});

		var a = document.createElement('button');
		a.className = 'icn-btn';
		a.innerText = 'Supprimer';
		a.dataset.icon = '✘';
		a.type = 'button';
		a.onclick = function() { if (confirm('Supprimer ce fichier ?')) this.parentNode.submit(); };

		var items = document.body.getElementsByTagName('form');

		for (var i = 0; i < items.length, form = items[i]; i++)
		{
			if (form.className != 'actions') continue;
			var s = a.cloneNode(true);
			s.onclick = a.onclick;

			form.appendChild(s);
		}
	});
}());