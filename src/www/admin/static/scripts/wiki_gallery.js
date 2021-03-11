(function () {
    var div, fig;

    document.addEventListener('DOMContentLoaded', enableGallery, false);

    function enableGallery()
    {
        if (!document.querySelectorAll) {
            return false;
        }

        var items = document.querySelectorAll('a.internal-image');

        for (var i = 0; i < items.length; i++)
        {
            var a = items[i];
            a.setAttribute('data-pos', i);
            a.onclick = function (e) {
                e.preventDefault();
                openImageBrowser(items, this.getAttribute('data-pos'));
                return false;
            };
        }
    };

    function openImageBrowser(items, pos)
    {
        div = document.createElement('div');
        div.className = 'imageBrowser';

        var fig = document.createElement('figure');

        div.onclick = function (e) {
            div.style.opacity = 0;
            window.setTimeout(function() { div.parentNode.removeChild(div); }, 500);
        };

        var img = document.createElement('img');
        img.title = 'Cliquer sur l\'image pour aller à la suivante, ou à côté pour fermer';
        img.pos = 0;

        img.onload = function () {
            fig.style.opacity = 1;
            img.style.width = 'initial';
            img.style.height = 'initial';
        };

        img.onclick = function (e) {
            e.stopPropagation();
            img.pos++;
            openImage(img, items);
        };

        fig.appendChild(img);
        div.appendChild(fig);
        document.body.appendChild(div);

        openImage(img, items, div);
    }

    function openImage(img, items)
    {
        // Pour animation
        var fig = img.parentNode;
        fig.style.opacity = 0;

        var pos = img.pos;

        if (pos >= items.length)
        {
            var div = img.parentNode.parentNode;
            div.style.opacity = 0;
            window.setTimeout(function() { div.parentNode.removeChild(div); }, 500);
            return;
        }

        img.style.width = 0;
        img.style.height = 0;
        img.src = items[pos].href;
        img.pos = pos;
    }

}());