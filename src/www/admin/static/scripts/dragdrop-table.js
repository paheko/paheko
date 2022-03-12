window.enableTableDragAndDrop = function (table) {
	var items = table.querySelectorAll('tbody tr');

	items.forEach(function (row) {
		const btn = row.querySelector('button');
		row.draggable = true;
		btn.classList.add('draggable');
		addDragEvents(row);
	});

	var dragSrcEl = null;
	var dragTargetEl = null;

	function addDragEvents(row) {
		row.addEventListener('dragstart', handleDragStart, false);
		row.addEventListener('dragenter', handleDragEnter, false);
		row.addEventListener('dragover', handleDragOver, false);
		row.addEventListener('dragleave', handleDragLeave, false);
		row.addEventListener('drop', handleDrop, false);
		row.addEventListener('dragend', handleDragEnd, false);
	}

	function handleDragStart(e) {
		this.parentNode.parentNode.classList.add('dragging');
		this.classList.add('dragging');

		dragTargetEl = null;
		dragSrcEl = this;

		e.dataTransfer.effectAllowed = 'move';
		e.dataTransfer.setData('text/html', this.innerHTML);

		// Hide ghost image
		var i = new Image;
		i.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==';
		e.dataTransfer.setDragImage(i, 0, 0);
	}

	function handleDragOver(e) {
		if (e.preventDefault) {
			e.preventDefault();
		}

		e.dataTransfer.dropEffect = 'move';

		return false;
	}

	function handleDragEnter(e) {
		if (this == dragSrcEl) {
			return;
		}

		this.classList.add('placeholder');
	}

	function handleDragLeave(e) {
		this.classList.remove('placeholder');

		if (this == dragSrcEl) {
			return;
		}
	}

	function handleDrop(e) {
		if (e.stopPropagation) {
			e.stopPropagation(); // stops the browser from redirecting.
		}

		if (dragSrcEl != this) {
			dragTargetEl = this;
		}

		return false;
	}

	function handleDragEnd(e) {
		this.classList.remove('dragging');
		this.parentNode.parentNode.classList.remove('dragging');

		if (dragTargetEl) {
			// Clone source row
			var new_row = dragSrcEl.cloneNode(true);
			// Remove source row
			this.remove();
			// Re-add events
			addDragEvents(new_row);

			// Insert new cloned row where it's supposed to go
			dragTargetEl.parentNode.insertBefore(new_row, dragTargetEl.nextSibling);
		}

		// Items list has changed
		items = table.querySelectorAll('tbody tr');

		items.forEach(function (item) {
			item.classList.remove('placeholder');
		});
	}
};

enableTableDragAndDrop(document.querySelector('table'));
