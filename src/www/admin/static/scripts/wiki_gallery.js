(function () {
    var enableGallery = function () {
        var gallery = document.getElementsByClassName('gallery');

        if (gallery.length == 1 && document.querySelector)
        {
            var items = gallery[0].getElementsByTagName('li');

            for (var i = 0; i < items.length; i++)
            {
                var a = items[i].querySelector('figure > a');
                a.pos = i;
                a.onclick= function (e) {
                    e.preventDefault();
                    openImageBrowser(items, this.pos);
                    return false;
                };
            }
        }
    };

    if (document.addEventListener)
        document.addEventListener('DOMContentLoaded', enableGallery, false);
    else
        document.attachEvent('onDOMContentLoaded', callback);

    function openImageBrowser(items, pos)
    {
        var div = document.createElement('div');
        div.className = 'imageBrowser';

        var img = document.createElement('img');
        img.pos = pos-1;

        img.onclick = function (e) {
            e.stopPropagation();
            openImage(img, items);
        };

        var fig = document.createElement('figure');

        div.onclick = function (e) {
            div.parentNode.removeChild(div);
        };

        fig.appendChild(img);
        div.appendChild(fig);
        document.body.appendChild(div);

        openImage(img, items);
    }

    function openImage(img, items)
    {
        // Pour animation
        img.style.opacity = 0;

        if (++img.pos == items.length)
        {
            img.pos = 0;
        }

        var newImg = new Image;
        newImg.onload = function (e) {
            img.src = e.target.src;
            img.style.opacity = 1;
        };

        newImg.src = items[img.pos].querySelector('figure > a').href;
        return false;   
    }

}());