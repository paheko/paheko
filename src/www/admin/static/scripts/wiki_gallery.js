(function () {
    var enableGallery = function () {
        if (!document.querySelectorAll)
        {
            return false;
        }

        var items = document.querySelectorAll('a.internal-image');

        for (var i = 0; i < items.length; i++)
        {
            var a = items[i];
            a.pos = i;
            a.onclick= function (e) {
                e.preventDefault();
                openImageBrowser(items, this.pos);
                return false;
            };
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
        fig.style.opacity = 0;

        div.onclick = function (e) {
            div.style.opacity = 0;
            window.setTimeout(function() { div.parentNode.removeChild(div); }, 500);
        };

        fig.appendChild(img);
        div.appendChild(fig);
        document.body.appendChild(div);

        openImage(img, items);
    }

    function openImage(img, items)
    {
        // Pour animation
        img.parentNode.style.opacity = 0;

        if (++img.pos == items.length)
        {
            var div = img.parentNode.parentNode;
            div.style.opacity = 0;
            window.setTimeout(function() { div.parentNode.removeChild(div); }, 500);
            return;
        }

        var newImg = new Image;
        newImg.onload = function (e) {
            var new_src = e.target.src;
            window.setTimeout(function() {
                img.src = new_src;
                img.parentNode.style.opacity = 1;
            }, img.src ? 250 : 0);
        };

        newImg.src = items[img.pos].href;
        return false;   
    }

}());