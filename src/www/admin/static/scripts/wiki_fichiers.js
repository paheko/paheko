(function () {
    g.onload(function () {
        uploadHelper($('#f_fichier'), {
            width: 1920,
            height: null,
            resize: true,
            bytes: 'o',
            size_error_msg: 'Le fichier %file fait %size, soit plus que la taille maximale autorisée de %max_size.'
        });

        function insertImageHelper(file, from_upload) {
            if (!document.querySelectorAll)
            {
                window.parent.te_insertImage(file.id, 'centre');
                return true;
            }

            var f = document.getElementById('insertImage');
            f.style.display = 'block';

            var inputs = f.querySelectorAll('input[type=button]');

            for (var i = 0; i < inputs.length; i++)
            {
                inputs[i].onclick = function(e) {
                    window.parent.te_insertImage(file.id, e.target.name, f.f_caption.value);
                };
            }

            f.querySelector('dd.image').innerHTML = '';
            var img = document.createElement('img');
            img.src = file.thumb;
            img.alt = '';
            f.querySelector('dd.image').appendChild(img);

            f.querySelector('dd.cancel input[type=reset]').onclick = function() {
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
                window.parent.te_insertFile(data.file.id);
            }

            return true;
        }

        var gallery = document.getElementsByClassName('gallery');

        if (gallery.length == 1 && document.querySelector)
        {
            var items = gallery[0].getElementsByTagName('li');

            for (var i = 0; i < items.length; i++)
            {
                var a = items[i].querySelector('figure > a');
                a.onclick= function (e) {
                    insertImageHelper({
                        id: this.getAttribute('data-id'),
                        thumb: this.firstChild.src
                    });
                    return false;
                };
            }
        }

        var a = document.createElement('a');
        a.className = 'icn';
        a.title = 'Supprimer';
        a.innerHTML = '✘';
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