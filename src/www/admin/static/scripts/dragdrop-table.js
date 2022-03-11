window.enableTableDragAndDrop = function (table) {
	var items = table.querySelectorAll('tbody tr');

	items.forEach(function (row) {
		const btn = row.querySelector('button');
		row.draggable = true;
		btn.classList.add('draggable');

		row.dataset.label = row.querySelector('th').textContent;

		row.addEventListener('dragstart', handleDragStart, false);
		row.addEventListener('dragenter', handleDragEnter, false);
		row.addEventListener('dragover', handleDragOver, false);
		row.addEventListener('dragleave', handleDragLeave, false);
		row.addEventListener('drop', handleDrop, false);
		row.addEventListener('dragend', handleDragEnd, false);
	});

	var dragSrcEl = null;

	function handleDragStart(e) {
		this.parentNode.parentNode.classList.add('dragging');
		this.classList.add('dragging');

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
			dragSrcEl.querySelector('th').textContent = dragSrcEl.dataset.label;
			return;
		}

		changeLabel(this);

		this.classList.add('placeholder');
	}

	function handleDragLeave(e) {
		if (this == dragSrcEl) {
			return;
		}

		// Restore label, but only of current element
		this.querySelector('th').textContent = this.dataset.label;

		this.classList.remove('placeholder');
	}

	function handleDrop(e) {
		if (e.stopPropagation) {
			e.stopPropagation(); // stops the browser from redirecting.
		}

		if (dragSrcEl != this) {
			var src_label = dragSrcEl.dataset.label;
			var target_label = this.dataset.label;

			dragSrcEl.innerHTML = this.innerHTML;
			dragSrcEl.querySelector('th').textContent = target_label;
			dragSrcEl.dataset.label = target_label;

			this.dataset.label = src_label;
			this.innerHTML = e.dataTransfer.getData('text/html');
		}

		return false;
	}

	function changeLabel(elm) {
		dragSrcEl.querySelector('th').textContent = elm.dataset.label;
		elm.querySelector('th').textContent = dragSrcEl.dataset.label;
	}

	function handleDragEnd(e) {
		this.classList.remove('dragging');
		this.parentNode.parentNode.classList.remove('dragging');

		items.forEach(function (item) {
			item.classList.remove('placeholder');
		});
	}
};