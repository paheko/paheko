(function () {
	window.textEditor = function (id)
	{
		if (!document.getElementById(id))
		{
			throw new Error("Invalid ID parameter: " + id);
		}

		this.id = id;
		this.textarea = document.getElementById(id);
		this.shortcuts = [];
		this.supportsExecCommand = null;

		// Browser too old
		if (!('selectionStart' in this.textarea))
		{
			return false;
		}

		this.textarea.addEventListener('keydown', this.keyEvent.bind(this), true);
		this.textarea.addEventListener('keypress', this.keyEvent.bind(this), true);

		return true;
	};

	textEditor.prototype.keyEvent = function (e) {
		var e = e || window.event;

		for (var key in this.shortcuts)
		{
			var shortcut = this.shortcuts[key];

			if (e.metaKey)
				continue;

			if ((e.ctrlKey && !shortcut.ctrl) || (shortcut.ctrl && !e.ctrlKey))
				continue;

			if ((e.shiftKey && !shortcut.shift) || (shortcut.shift && !e.shiftKey))
				continue;

			if ((e.altKey && !shortcut.alt) || (shortcut.alt && !e.altKey))
				continue;

			if (!(key = this.matchKeyPress(shortcut.key, e)))
			{
				continue;
			}

			if (typeof shortcut.callback != 'function')
			{
				var key_name = (shortcut.ctrl ? 'Ctrl-' : '') + (shortcut.alt ? 'Alt-' : '')
				key_name += (shortcut.shift ? 'Shift-' : '') + shortcut;
				throw new Error("Invalid callback type for shortcut "+key_name);
			}

			var r = shortcut.callback.call(this, e, key);

			return r ? this.preventDefault(e) : true;
		}

		return true;
	};

	textEditor.prototype.matchKeyPress = function (key, e)
	{
		if (e.defaultPrevented || !e.key) {
			// Do nothing if the event was already processed
			// or if KeyboardEvent is not supported
			return false;
		}

		key = key.toLowerCase();

		return (e.key.toLowerCase() == key) ? key : false;
	};

	textEditor.prototype.preventDefault = function (e) {
       	if (e.preventDefault) e.preventDefault();
       	if (e.stopPropagation) e.stopPropagation();
       	if (e.stopImmediatePropagation) e.stopImmediatePropagation();
      	e.returnValue = false;
      	e.cancelBubble = true;
		return false;
	};

	textEditor.prototype.getSelection = function ()
	{
		var e = this.textarea;

		var l = e.selectionEnd - e.selectionStart;
		return { start: e.selectionStart, end: e.selectionEnd, length: l, text: e.value.substr(e.selectionStart, l) };
	};

	textEditor.prototype.replaceSelection = function (selection, replace_str)
	{
		var start_pos = selection.start;
		var end_pos = start_pos + replace_str.length;

		this.setSelection(start_pos, selection.end);

		this.insert(replace_str);

		this.setSelection(start_pos, end_pos);

		return {start: start_pos, end: end_pos, length: replace_str.length, text: replace_str};
	};

	textEditor.prototype.insert = function (text) {
		if (this.supportsExecCommand === true) {
			document.execCommand("insertText", false, text);
		}
		else if (this.supportsExecCommand === null) {
			// We still don't know if document.execCommand("insertText") is supported
			let value = this.textarea.value;

			document.execCommand("insertText", false, text);

			this.supportsExecCommand = value !== this.textarea.value;
		}

		if (this.supportsExecCommand === false) {
			this.textarea.setRangeText(text, this.textarea.selectionStart, this.textarea.selectionEnd, "end");
		}

		return this.supportsExecCommand;
	};

	textEditor.prototype.insertAtPosition = function (start_pos, str, new_pos)
	{
		var end_pos = start_pos + str.length;

		this.setSelection(start_pos, start_pos);
		this.insert(str);

		if (!new_pos) new_pos = end_pos;
		return this.setSelection(new_pos, new_pos);
	};

	textEditor.prototype.setSelection = function (start_pos, end_pos)
	{
		var e = this.textarea;

		e.focus();
		e.selectionStart = start_pos;
		e.selectionEnd = end_pos;

		return this.getSelection();
	};

	textEditor.prototype.scrollToSelection = function (selection)
	{
		var e = this.textarea;
		var removed = e.value.substr(selection.end);
		e.value = e.value.substr(0, selection.end);
		e.scrollTop = 100000;
		var scroll = e.scrollTop;
		e.value += removed;
		e.scrollTop = scroll;
		this.setSelection(selection.start, selection.end);
	};

	textEditor.prototype.wrapSelection = function (selection, left_str, right_str)
	{
		var e = this.textarea;
		var scroll = e.scrollTop;
		var the_sel_text = selection.text;
		var selection =  this.replaceSelection(selection, left_str + the_sel_text + right_str );
		if(the_sel_text == '') selection = this.setSelection(selection.start + left_str.length, selection.start + left_str.length);
		e.scrollTop = scroll;
		return selection;
	};
}());