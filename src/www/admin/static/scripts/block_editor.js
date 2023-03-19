/*
- sélectionner un bloc -> OK
- modifier le contenu du bloc (texte/image/alignement/etc.)
- exporter le contenu en MarkDown
- déplacer un bloc au dessus / en dessous
- supprimer un bloc
- insérer un bloc en dessous / au dessus
- édition des sous-blocs d'un bloc (grid/align)
- édition de tableau
- quand on fait BackSpace dans un textarea vide, aller à l'édition du bloc précédent
 */
var selectedBlock = null;
var editor = null;
var buttons = `<p class="buttons">
	<button data-icon="↑" title="Déplacer vers le haut"></button>
	<button data-icon="↓" title="Déplacer vers le bas"></button>
	<button data-icon="✘" title="Supprimer"></button>
</p>`;

function makeEditable(content, editorContainer) {
	content.classList.add('content-edit');

	let nodes_length = content.childNodes.length;
	editor = editorContainer;

	for (var i = 0; i < nodes_length; i++) {
		let block = content.childNodes[i];

		if (block.nodeType !== 1) {
			continue;
		}

		//if (block.nodeType != )
		block.classList.add('block');
		block.onclick = (e) => { selectBlock(block); e.stopPropagation(); return false; };
		block.title = 'Cliquer pour modifier ce bloc';
	}
}

function selectBlock(block) {
	if (selectedBlock) {
		selectedBlock.classList.remove('selected');
		editor.innerHTML = '';
	}

	block.classList.add('selected');
	selectedBlock = block;

	editor.insertAdjacentHTML('afterbegin', buttons);

	if (block.nodeName == 'P') {
		blockText(block);
	}
	else if (block.nodeName == 'FIGURE') {
		blockImage(block);
	}
}

function blockText(block) {
	var t = document.createElement('textarea');
	editor.appendChild(t);
	t.value = block.innerText;
	t.onkeyup = () => {
		block.innerText = t.value;
	};
}

g.onload(() => {
	makeEditable(document.querySelector('.web-content'), document.querySelector('.block'));
	$('#toggleVisualEditor').onclick = (e) => {
		$('.web-edit')[0].classList.add('web-edit-visual');
		$('.web-edit')[0].classList.remove('web-edit-text');
		$('#toggleVisualEditor').parentNode.classList.add('current');
		$('#toggleTextEditor').parentNode.classList.remove('current');
	};
	$('#toggleTextEditor').onclick = () => {
		$('.web-edit')[0].classList.remove('web-edit-visual');
		$('.web-edit')[0].classList.add('web-edit-text');
		$('#toggleVisualEditor').parentNode.classList.remove('current');
		$('#toggleTextEditor').parentNode.classList.add('current');
	};
});