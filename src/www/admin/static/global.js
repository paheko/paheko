(function () {
	window.$ = function(selector) {
		if (!selector.match(/^[.#]?[a-z0-9_-]+$/i))
		{
			return document.querySelectorAll(selector);
		}
		else if (selector.substr(0, 1) == '.')
		{
			return document.getElementsByClassName(selector.substr(1));
		}
		else if (selector.substr(0, 1) == '#')
		{
			return document.getElementById(selector.substr(1));
		}
		else
		{
			return document.getElementsByTagName(selector);
		}
	};

    window.toggleElementVisibility = function(selector, visibility)
    {
    	if (!('classList' in document.documentElement))
    		return false;

    	if (selector instanceof Array)
    	{
    		for (var i = 0; i < selector.length; i++)
    		{
    			toggleElementVisibility(selector[i], visibility);
    		}

    		return true;
    	}

        var elements = $(selector);

        for (var i = 0; i < elements.length; i++)
        {
        	if (!visibility)
        		elements[i].classList.add('hidden');
           	else
        		elements[i].classList.remove('hidden');
        }

        return true;
    };

	function dateInputFallback()
	{
		var inputs = document.getElementsByTagName('input');
		var length = inputs.length;
		var enabled = false;

		for (i = 0; i < inputs.length; i++)
		{
			if (inputs[i].getAttribute('type') == 'date' && (inputs[i].type == 'text' || window.webkitConvertPointFromNodeToPage))
			{
				enabled = true;
			}
		}

		if (enabled)
		{
			var www_url = document.body.getAttribute('data-url') + 'static/';

			var script = document.createElement('script');
			script.type = "text/javascript";
			script.src = www_url + 'datepickr.js';
			document.head.appendChild(script);
			
			var link = document.createElement('link');
			link.type = 'text/css';
			link.rel = 'stylesheet';
			link.href = www_url + 'datepickr.css';
			document.head.appendChild(link);
		}
	}

	if (document.addEventListener)
	{
		document.addEventListener("DOMContentLoaded", dateInputFallback, false);
	}
	else
	{
		document.attachEvent("onDOMContentLoaded", dateInputFallback);
	}
})();