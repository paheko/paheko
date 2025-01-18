(function () {
	function inherit(proto) {
		function F() {}
		F.prototype = proto
		return new F
	}

	String.prototype.repeat = function(num)
	{
		return new Array(num + 1).join(this);
	}

	window.codeEditor = function (id)
	{
		var r = textEditor.call(this, id);

		if (!r)
		{
			return false;
		}

		this.onlinechange = null;
		this.onlinenumberchange = null;

		this.fullscreen = false;
		this.nb_lines = 0;
		this.current_line = 0;
		this.search_str = null;
		this.search_pos = 0;
		this.params = {
			indent_size: 4, // Size of indentation
			lang: {
				search: "Text to search?\n(regexps allowed, begin them with '/')",
				replace: "Text for replacement?\n(use $1, $2... for regexp replacement)",
				search_selection: "Text to replace in selection?\n(regexps allowed, begin them with '/')",
				replace_result: "%d occurence found and replaced.",
				goto: "Line to go to:",
				no_search_result: "No search result found."
			}
		};

		that = this;

		this.init();
		this.textarea.spellcheck = false;

		this.shortcuts.push({shift: true, key: 'tab', callback: this.indent});
		this.shortcuts.push({key: 'tab', callback: this.indent});
		this.shortcuts.push({ctrl: true, key: 'f', callback: this.search});
		this.shortcuts.push({ctrl: true, key: 'h', callback: this.searchAndReplace});
		this.shortcuts.push({ctrl: true, key: 'g', callback: this.goToLine});
		this.shortcuts.push({key: 'F3', callback: this.searchNext});
		this.shortcuts.push({key: 'backspace', callback: this.backspace});
		this.shortcuts.push({key: 'enter', callback: this.enter});
		this.shortcuts.push({key: '"', callback: this.insertBrackets});
		this.shortcuts.push({key: '\'', callback: this.insertBrackets});
		this.shortcuts.push({key: '[', callback: this.insertBrackets});
		this.shortcuts.push({key: '{', callback: this.insertBrackets});
		this.shortcuts.push({key: '(', callback: this.insertBrackets});
		this.shortcuts.push({key: 'F11', callback: this.toggleFullscreen});

		this.textarea.addEventListener('keypress', this.keyEvent.bind(this), true);
		this.textarea.addEventListener('keydown', this.keyEvent.bind(this), true);
	};

	// Extends textEditor
	codeEditor.prototype = inherit(textEditor.prototype);

	codeEditor.prototype.init = function () {
		var that = this;

		this.nb_lines = this.countLines();

		this.parent = document.createElement('div');
		this.parent.className = 'codeEditor';

		this.lineCounter = document.createElement('span');
		this.lineCounter.className = 'lineCount';

		for (i = 1; i <= this.nb_lines; i++)
		{
			this.lineCounter.innerHTML += '<b>' + i + '</b>';
		}

		this.lineCounter.innerHTML += '<i>---</i>';

		var editor = document.createElement('div');
		editor.className = 'editor';
		editor.appendChild(this.lineCounter);

		// This is to avoid a CSS-spec 'bug' http://snook.ca/archives/html_and_css/absolute-position-textarea
		var container = document.createElement('div');
		container.className = 'container';
		container.appendChild(this.textarea.cloneNode(true));
		editor.appendChild(container);
		this.parent.appendChild(editor);

		var pnode = this.textarea.parentNode;
		pnode.appendChild(this.parent);
		pnode.removeChild(this.textarea);

		this.textarea = this.parent.getElementsByTagName('textarea')[0];
		this.textarea.wrap = 'off';
		this.textarea.style = 'tab-size: ' + this.params.indent_size;

		// Detect indentation type and apply it
		let tabs_count = (this.textarea.value.match(/^\t/mg) || []).length;
		let spaces_regex = new RegExp('^[ ]{' + this.params.indent_size + '}', 'mg');
		let spaces_count = (this.textarea.value.match(spaces_regex) || []).length;

		this.indent_pattern = spaces_count > tabs_count ? ' '.repeat(this.params.indent_size) : "\t";

		this.textarea.addEventListener('focus', function() { that.update(); }, false);
		this.textarea.addEventListener('keyup', function() { that.update(); }, false);
		this.textarea.addEventListener('click', function() { that.update(); }, false);
		this.textarea.addEventListener('scroll', function () {
			that.lineCounter.scrollTop = that.textarea.scrollTop;
		}, false);
	};

	codeEditor.prototype.update = function () {
		var selection = this.getSelection();
		var line = this.getLineNumberFromPosition(selection);
		var nb_lines = this.countLines();
		this.search_pos = selection.end;

		if (nb_lines != this.nb_lines)
		{
			var lines = this.lineCounter.getElementsByTagName('b');

			for (var i = this.nb_lines; i > nb_lines; i--)
			{
				this.lineCounter.removeChild(lines[i-1]);
			}

			var delim = this.lineCounter.lastChild;

			for (var i = lines.length; i < nb_lines; i++)
			{
				var b = document.createElement('b');
				b.innerHTML = i+1;
				this.lineCounter.insertBefore(b, delim);
			}

			this.nb_lines = nb_lines;

			if (typeof this.onlinenumberchange === 'function')
			{
				this.onlinenumberchange.call(this);
			}
		}

		if (line != this.current_line)
		{
			var lines = this.lineCounter.getElementsByTagName('b');

			for (var i = 0; i < this.nb_lines; i++)
			{
				lines[i].className = '';
			}

			lines[line].className = 'current';
			this.current_line = line;

			if (typeof this.onlinechange === 'function')
			{
				this.onlinechange.call(this);
			}
		}
	};

	codeEditor.prototype.countLines = function()
	{
		var match = this.textarea.value.match(/(\r?\n)/g);
		return match ? match.length + 1 : 1;
	};

	codeEditor.prototype.getLineNumberFromPosition = function(s)
	{
		var s = s || this.getSelection();

		if (s.start == 0)
		{
			return 0;
		}

		var match = this.textarea.value.substr(0, s.start).match(/(\r?\n)/g);
		return match ? match.length : 0;
	};

	codeEditor.prototype.getLines = function ()
	{
		return this.textarea.value.split("\n");
	};

	codeEditor.prototype.getLine = function (line)
	{
		return this.textarea.value.split("\n", line+1)[line];
	};

	codeEditor.prototype.getLinePosition = function (lines, line)
	{
		var start = 0;

		for (i = 0; i < lines.length; i++)
		{
			if (i == line)
			{
				return {start: start + i, end: start + lines[i].length, length: lines[i].length, text: lines[i]};
			}

			start += lines[i].length;
		}

		return false;
	};

	codeEditor.prototype.selectLines = function(selection)
	{
		for (var i = selection.start; i > 0; i--)
		{
			if (this.textarea.value.substr(i, 1) == "\n")
			{
				selection.start = i+1;
				break;
			}
		}

		for (var i = selection.end-1; i < this.textarea.length; i++)
		{
			if (this.textarea.value.substr(i, 1) == "\n")
			{
				selection.end = i-1;
				break;
			}
		}

		this.setSelection(selection.start, selection.end);
		return selection;
	};

	codeEditor.prototype.goToLine = function (e)
	{
		var line = window.prompt(that.params.lang.goto);
		if (!line) return;

		var l = this.textarea.value.split("\n", parseInt(line, 10)).join("\n").length;
		this.scrollToSelection(this.setSelection(l, l));

		return true;
	};

	codeEditor.prototype.indent = function (e, key)
	{
		var s = this.getSelection();
		var unindent = e.shiftKey;

		var lines = this.getLines();
		var line = this.getLineNumberFromPosition(s);
		var line_sel = this.getLinePosition(lines, line);
		var multiline_sel = (s.end > line_sel.end) ? true : false;

		// We are not at the start of the line and we didn't select any text
		// or the selection is not multine: just return a tab character
		if ((s.length == 0 || !multiline_sel) && s.start != line_sel.start)
		{
			this.insertAtPosition(s.start, this.indent_pattern);
			return true;
		}

		const match_regexp = new RegExp('^([ ]{' + this.params.indent_size + '}|\t)*');

		if (s.length == 0 && s.start == line_sel.start)
		{
			var prev_match = (line-1 in lines) ? lines[line-1].match(match_regexp) : false;

			if (!prev_match || line_sel.length != 0)
			{
				var insert = this.indent_pattern;
			}
			else
			{
				var insert = this.indent_pattern.repeat(prev_match.length);
			}

			this.insertAtPosition(s.start, insert);
			return true;
		}

		s = this.selectLines(s);

		var txt = this.textarea.value.substr(s.start, (s.end - s.start));
		var lines = txt.split("\n");

		if (unindent)
		{
			var r = new RegExp('^([ ]{' + this.params.indent_size + '}|\t)');

			for (var i = 0; i < lines.length; i++)
			{
				lines[i] = lines[i].replace(r, '');
			}
		}
		else
		{
			for (var i = 0; i < lines.length; i++)
			{
				lines[i] = lines[i].replace(/\s+/, '') == '' ? '' : this.indent_pattern + lines[i];
			}
		}

		txt = lines.join("\n");
		this.replaceSelection(s, txt);
		return true;
	};

	codeEditor.prototype.search = function()
	{
		if (!(this.search_str = window.prompt(this.params.lang.search, this.search_str ? this.search_str : '')))
			return;

		this.search_pos = 0;
		return this.searchNext();
	};

	codeEditor.prototype.searchNext = function()
	{
		if (!this.search_str) return true;

		var s = this.getSelection();

		var pos = s.end >= this.search_pos ? this.search_pos : s.start;
		var txt = this.textarea.value.substr(pos);

		var r = this.getSearchRegexp(this.search_str);
		var found = txt.search(r);

		if (found == -1)
		{
			return window.alert(this.params.lang.no_search_result);
		}

		var match = txt.match(r);

		s.start = pos + found;
		s.end = s.start + match[0].length;
		s.length = match[0].length;
		s.text = match[0];

		this.setSelection(s.start, s.end);
		this.search_pos = s.end;
		this.scrollToSelection(s);

		return true;
	};

	codeEditor.prototype.getSearchRegexp = function(str, global)
	{
		var r, m;

		if (str.substr(0, 1) == '/')
		{
			var pos = str.lastIndexOf("/");
			r = str.substr(1, pos-1);
			m = str.substr(pos+1).replace(/g/, '');
		}
		else
		{
			r = str.replace(/([\/$^.?()[\]{}\\])/, '\\$1');
			m = 'i';
		}

		if (global)
		{
			m += 'g';
		}

		return new RegExp(r, m);
	};

	codeEditor.prototype.searchAndReplace = function(e)
	{
		var selection = this.getSelection();
		var search_prompt = selection.length != 0 ? this.params.lang.search_selection : this.params.lang.search;

		if (!(s = window.prompt(search_prompt, this.search_str ? this.search_str : ''))
			|| !(r = window.prompt(that.params.lang.replace)))
		{
			return true;
		}

		var regexp = this.getSearchRegexp(s, true);

		if (selection.length == 0)
		{
			var nb = this.textarea.value.match(regexp).length;
			this.textarea.value = this.textarea.value.replace(regexp, r);
		}
		else
		{
			var nb = selection.text.match(regexp).length;
			this.replaceSelection(selection, selection.text.replace(regexp, r));
		}

		window.alert(this.params.lang.replace_result.replace(/%d/g, nb));

		return true;
	};

	codeEditor.prototype.enter = function (e)
	{
		var selection = this.getSelection();

		if (selection.start != selection.end) {
			this.replaceSelection(selection, '');
			selection = this.getSelection();
		}

		var line = this.getLineNumberFromPosition(selection);
		var indent = '';
		var indent_bracket = false;
		line = this.getLine(line);

		if (this.textarea.value.substr(selection.start - 1, 1) == '{')
		{
			indent += this.indent_pattern;
			indent_bracket = this.textarea.value.substr(selection.start, 1) == '}';
		}

		if (match = line.match(/^(\s+)/))
		{
			indent += match[1];
		}

		if (!indent) {
			return false;
		}

		this.insertAtPosition(selection.start, "\n" + indent);

		if (indent_bracket) {
			// Indent closing bracket as well
			let s = this.getSelection();
			this.insertAtPosition(s.start, "\n" + indent.substr(0, indent.length - this.indent_pattern.length));
			this.setSelection(s.start, s.end);
		}

		return true;
	};

	codeEditor.prototype.backspace = function(e)
	{
		var s = this.getSelection();

		if (s.length > 0)
		{
			return false;
		}

		var txt = this.textarea.value.substr(s.start - 1, 2);

		if (txt == '""' || txt == "''" || txt == '{}' || txt == '()' || txt == '[]')
		{
			s.start -= 1;
			s.end += 1;
			this.replaceSelection(s, '');
			return true;
		}

		// Unindent
		var txt = this.textarea.value.substr(s.start - 20, 20);

		if ((pos = txt.search(/([ \t]+)$/)) != -1)
		{
			s.start -= (20 - pos);
			this.replaceSelection(s, '');
			return true;
		}

		return false;
	};

	codeEditor.prototype.insertBrackets = function(e, key)
	{
		var s = this.getSelection();
		var o = key;
		var c = o;

		switch (o)
		{
			case '(': c = ')'; break;
			case '[': c = ']'; break;
			case '{': c = '}'; break;
		}

		if (s.length == 0)
		{
			this.insertAtPosition(s.start, o+c, s.start+1);
		}
		else
		{
			this.wrapSelection(s, o, c);
		}

		return true;
	};

	codeEditor.prototype.toggleFullscreen = function (e)
	{
		var classes = this.parent.className.split(' ');

		for (var i = 0; i < classes.length; i++)
		{
			if (classes[i] == 'fullscreen')
			{
				classes.splice(i, 1);
				this.parent.className = classes.join(' ');
				this.fullscreen = false;
				return true;
			}
		}

		classes.push('fullscreen');
		this.parent.className = classes.join(' ');
		this.fullscreen = true;
		return true;
	};
}());