(function () {

const TYPES = ['code', ];

window.WebEditor = class {
	constructor(textarea) {
		this.textarea = textarea;
		this.parse(textarea.value)
		this.editor = 
	}

	build() {

	}

	parse(text) {
		var blocks = text.split("\n\n----\n\n");
		this.blocks = [];

		for (var i = 0; i < blocks.length; i++) {
			let block = blocks[i];

			if ((i % 1) == 0) {
				this.blocks.push(this.parseMeta(block));
			}
			else {
				this.blocks[this.blocks.length - 1].content = block.trim("\r\n");
			}
		}
	}

	parseMeta(text) {
		var lines = text.split("\n");
		var meta = {};

		for (var i = 0; i < lines.length; i++) {
			var line = lines[i].split(':', 2);

			if (line.length != 2) {
				continue;
			}

			meta[line[0].trim().toLowerCase()] = line[1].trim();
		}

		return meta;
	}
}

})();