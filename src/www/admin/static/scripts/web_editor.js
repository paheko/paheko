(function () {

const TYPES = ['code', ];

window.WebEditor = class {
	constructor() {
	}

	editor(textarea) {
		this.parse(textarea.value);
		this.textarea = textarea;
		this.form = textarea.form;
		textarea.style.display = 'none';

		this.form.addEventListener('submit', () => {
			let e = document.createElement('input');
			e.type = 'hidden';
			e.name = this.textarea.name;
			e.value = this.export();
			this.textarea.remove();
			form.appendChild(e);
		});

		this.textarea.parentNode.insertBefore(this.buildEditor(), this.textarea);
	}

	buildEditor() {
		var e = document.createElement('div');
		e.className = 'web-editor';

		e.appendChild(this.buildToolbar());

		var c = document.createElement('div');
		c.className = 'web-editor-content';

		for (var i = 0; i < this.blocks; i++) {
			c.appendChild(this.buildBlock(this.blocks[i]));
		}

		e.appendChild(c);

		return e;
	}

	buildToolbar() {
		// add column, add row, move up, move down, change type, delete + block buttons
	}

	buildBlock(block) {
		let o = document.createElement('div');

		switch (block.type) {
			case 'columns':
				o.className = 'web-columns';
				block.columns.forEach((b) => o.appendChild(this.buildBlock(b)));
				break;
			case 'column':
				o.className = 'web-column';
				block.blocks.forEach((b) => o.appendChild(this.buildBlock(b)));
				break;
			case 'heading':
				var i = document.createElement('input');
				i.value = block.content;
				o.appendChild(i);
				break;
			case 'markdown':
				o.appendChild(this.buildMarkdownEditor(block));
				break;
			case 'skriv':
				o.appendChild(this.buildSkrivEditor(block));
				break;
			case 'video':
				o.appendChild(this.buildVideoEditor(block));
				break;
			case 'image':
				o.appendChild(this.buildImageEditor(block));
				break;
			case 'gallery':
				o.appendChild(this.buildGalleryEditor(block));
				break;
			case 'code':
			case 'quote':
			default:
				var i = document.createElement('textarea');
				i.value = block.content;
				o.appendChild(i);
				break;
				//throw Error('Unknown block type');
		}
	}

	buildMarkdownEditor(block) {
	}

	parse(text) {
		var raw_blocks = text.split("\n\n----\n\n");
		this.blocks = [];

		for (var i = 0; i < raw_blocks.length; i++) {
			let block = this.parseBlock(raw_blocks[i]);
			let last = this.blocks.length ? this.blocks[this.blocks.length - 1] : null;

			if (block.type == 'columns') {
				block.columns = [];
				this.blocks.push(block);
			}
			else if (block.type == 'column') {
				if (last && last.type == 'columns') {
					block.blocks = [];
					last.columns.push(block);
				}
				else {
					console.log("Invalid column", block);
				}
			}
			else {
				if (last && last.columns.length) {
					last.columns[last.columns.length - 1].blocks = block;
				}
				else {
					console.log("Invalid block", block);
				}
			}
		}
	}

	parseBlock(text) {
		text = text.replace("\r", "");
		var parts = text.split("\n\n", 2);
		var headers = {};
		var content = parts.length == 2 ? parts[1] : '';
		var lines = parts[0].split("\n");

		// Un-escape line separators
		content = content.replace("\n\n\\----\n\n", "\n\n----\n\n");

		for (var i = 0; i < lines.length; i++) {
			var line = lines[i].split(':', 2);

			if (line.length != 2) {
				continue;
			}

			headers[line[0].trim().toLowerCase()] = line[1].trim();
		}

		return {'content': content, 'headers': headers};
	}
}

})();