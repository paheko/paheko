(function () {
	// Source: http://stackoverflow.com/questions/401593/textarea-selection
	var selection =
	{
		get: function (e)
		{
			//Mozilla and DOM 3.0
			if('selectionStart' in e)
			{
				var l = e.selectionEnd - e.selectionStart;
				return { start: e.selectionStart, end: e.selectionEnd, length: l, text: e.value.substr(e.selectionStart, l) };
			}
			//IE
			else if(document.selection)
			{
				e.focus();
				var r = document.selection.createRange();
				var tr = e.createTextRange();
				var tr2 = tr.duplicate();
				tr2.moveToBookmark(r.getBookmark());
				tr.setEndPoint('EndToStart',tr2);
				if (r == null || tr == null) return { start: e.value.length, end: e.value.length, length: 0, text: '' };
				var text_part = r.text.replace(/[\r\n]/g,'.'); //for some reason IE doesn't always count the \n and \r in the length
				var text_whole = e.value.replace(/[\r\n]/g,'.');
				var the_start = text_whole.indexOf(text_part,tr.text.length);
				return { start: the_start, end: the_start + text_part.length, length: text_part.length, text: r.text };
			}
			//Browser not supported
			else return { start: e.value.length, end: e.value.length, length: 0, text: '' };
		},

		replace: function (e, replace_str)
		{
			var selection = this.get(e);
			var start_pos = selection.start;
			var end_pos = start_pos + replace_str.length;
			e.value = e.value.substr(0, start_pos) + replace_str + e.value.substr(selection.end, e.value.length);
			this.set(e,start_pos,end_pos);
			return {start: start_pos, end: end_pos, length: replace_str.length, text: replace_str};
		},

		set: function (e, start_pos,end_pos)
		{
			//Mozilla and DOM 3.0
			if('selectionStart' in e)
			{
				e.focus();
				e.selectionStart = start_pos;
				e.selectionEnd = end_pos;
			}
			//IE
			else if(document.selection)
			{
				e.focus();
				var tr = e.createTextRange();

				//Fix IE from counting the newline characters as two seperate characters
				var stop_it = start_pos;
				for (i=0; i < stop_it; i++) if( e.value[i].search(/[\r\n]/) != -1 ) start_pos = start_pos - .5;
				stop_it = end_pos;
				for (i=0; i < stop_it; i++) if( e.value[i].search(/[\r\n]/) != -1 ) end_pos = end_pos - .5;

				tr.moveEnd('textedit',-1);
				tr.moveStart('character',start_pos);
				tr.moveEnd('character',end_pos - start_pos);
				tr.select();
			}
			return this.get(e);
		},

		wrap: function (e, left_str, right_str, sel_offset, sel_length)
		{
			var the_sel_text = this.get(e).text;
			var selection =  this.replace(e, left_str + the_sel_text + right_str );
			if(sel_offset !== undefined && sel_length !== undefined) selection = this.set(e, selection.start +  sel_offset, selection.start +  sel_offset + sel_length);
			else if(the_sel_text == '') selection = this.set(e, selection.start + left_str.length, selection.start + left_str.length);
			return selection;
		}
	};

	function launchToolbar()
	{
		function addBtn(className, label, action)
		{
			var btn = document.createElement('input');
			btn.type = 'button';
			btn.className = className;
			btn.value = label;
			btn.onclick = action;
			toolbar.appendChild(btn);
		}

		var txt = document.getElementById('f_contenu');
		var parent = txt.parentNode.parentNode;
		var toolbar = document.createElement('div');
		toolbar.className = "toolbar";

		addBtn('title', 'Titre', function () { selection.wrap(txt, '{{{', "}}}\n"); } );
		addBtn('italic', 'Italique', function () { selection.wrap(txt, '{', '}'); } );
		addBtn('bold', 'Gras', function () { selection.wrap(txt, '{{', '}}'); } );
		addBtn('strike', 'Barré', function () { selection.wrap(txt, '<del>', '</del>'); } );
		addBtn('code', 'Chasse fixe', function () { selection.wrap(txt, '<pre>', '</pre>'); } );
		addBtn('link', 'Lien', function () {
				if (url = window.prompt('Adresse du lien ?'))
				{
						selection.wrap(txt, '[', '->' + url + ']');
				}
			} );

		parent.insertBefore(toolbar, txt.parentNode);
	}

	if (document.addEventListener)
	{
		document.addEventListener("DOMContentLoaded", launchToolbar, false);
	}
	else
	{
		document.attachEvent("onDOMContentLoaded", launchToolbar);
	}
} () );