window.enableTableDragAndDrop = function (table) {
	table.classList.add('drag');
	var items = table.querySelectorAll('tbody tr');

	items.forEach(function (row) {
		row.draggable = true;
		addEvents(row);
	});

	function swapNodes(node1, node2) {
		const afterNode2 = node2.nextElementSibling;
		const parent = node2.parentNode;
		node1.replaceWith(node2);
		parent.insertBefore(node1, afterNode2);
	}

	var dragSrcEl = null;
	var dragTargetEl = null;

	function addEvents(row) {
		row.querySelector('.up').onclick = () => swapNodes(row.previousElementSibling, row);
		row.querySelector('.down').onclick = () => swapNodes(row, row.nextElementSibling);
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

		if (!table.contains(this)) {
			return;
		}

		this.classList.add('placeholder');
	}

	function handleDragLeave(e) {
		if (!table.contains(this)) {
			return;
		}

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
			this.parentNode.insertBefore(dragSrcEl, dragTargetEl.nextElementSibling);
		}

		// Items list has changed
		items = table.querySelectorAll('tbody tr');

		items.forEach(function (item) {
			item.classList.remove('placeholder');
		});
	}
};

enableTableDragAndDrop(document.querySelector('table'));
