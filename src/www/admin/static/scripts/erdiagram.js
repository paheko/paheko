// https://github.com/hunyadi/entity-relationship-diagram/
// https://github.com/stanko-arbutina/arrow-line
// https://www.beyondjava.net/how-to-connect-html-elements-with-an-arrow-using-svg

var d = document.querySelector('.er-diagram');

var svg = null;

var observer = new MutationObserver(mutations => {
	mutations.forEach(mutation => {
		generateDiagram();

		var clicked = d.querySelector('.clicked');

		if (!clicked) {
			return;
		}

		var table_name = clicked.id.substr(2);
		d.querySelectorAll('g[data-table=' + table_name + ']').forEach(path => {
			path.classList.add('focused');
		});

		d.querySelectorAll('g[data-fk-table=' + table_name + ']').forEach(path => {
			path.classList.add('linked');
		});
	});
});

d.querySelectorAll('table').forEach(table => {
	observer.observe(table, { attributes : true, attributeFilter : ['class']});

	var table_name = table.id.substr(2);

	table.onclick = (e) => {
		if (e.target.tagName.toLowerCase() === 'a' || e.target.parentNode.tagName.toLowerCase() === 'a') {
			return true;
		}

		var clicked = table.classList.contains('clicked');
		d.querySelectorAll('.focused').forEach(e => e.classList.remove('focused'));
		d.querySelectorAll('.clicked').forEach(e => e.classList.remove('clicked'));

		if (clicked) {
			return false;
		}

		table.classList.add('focused');
		table.classList.add('clicked');

		table.querySelectorAll('tr[data-fk-table]').forEach(col => {
			d.querySelector('#t_' + col.dataset.fkTable).classList.add('focused');
		});

		d.querySelectorAll('tr[data-fk-table=' + table_name + ']').forEach(col => {
			col.parentNode.parentNode.classList.add('focused');
		});

		window.scrollTo(0, 0);
		return false;
	};
});

generateDiagram();


function generateDiagram() {
	var w = d.scrollWidth, h = d.scrollHeight;

	if (svg !== null) {
		svg.remove();
	}

	svg = createSVG(d, w, h);
	var hue = 0;
	var i = 0;

	d.querySelectorAll('[data-fk-column]').forEach((e) => {
		var table_name = e.parentNode.parentNode.id.substr(2);
		var target_id = 't_' + e.dataset.fkTable + '_' + e.dataset.fkColumn;
		var target = document.querySelector('#' + target_id);
		target.dataset.arrowFrom = e.id;
		var path = drawArrow(d, e, target);//connectElements(d, e, target, 0.3);

		hue = Math.floor(Math.random() * 360);
		saturation = Math.floor(Math.random() * 30) + 30;
		light = Math.floor(Math.random() * 30) + 30;
		var hsl = `hsl(${hue}, ${saturation}%, ${light}%)`;
		path.setAttributeNS(null, 'stroke', hsl);
		path.setAttributeNS(null, 'fill', hsl);
		path.dataset.table = table_name;
		path.dataset.fkTable = e.dataset.fkTable;
	});
}

function createSVG(parent, w, h) {
	parent.style = 'position: relative;';
	var svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
	svg.setAttribute('style', 'position:absolute;top:0px;left:0px; z-index: -1');
	svg.setAttribute('width', w);
	svg.setAttribute('height', h);
	svg.setAttributeNS("http://www.w3.org/2000/xmlns/", "xmlns:xlink", "http://www.w3.org/1999/xlink");
	d.appendChild(svg);
	return svg;
}

function findAbsolutePosition(htmlElement, parentRect) {
	var x = htmlElement.offsetLeft;
	var y = htmlElement.offsetTop;
	for (var x=0, y=0, el=htmlElement;
		el != null;
		el = el.offsetParent) {
			x += el.offsetLeft;
			y += el.offsetTop;
	}
	var w = htmlElement.offsetWidth;
	var h = htmlElement.offsetHeight;

	if (parentRect) {
		x -= parentRect.x;
		y -= parentRect.y;
	}

	return {x, y, w, h,
		'left': x,
		'top': y,
		'right': x+w,
		'bottom': y+h
	};
}

function drawArrow(parent, source, target) {
	var parentRect = findAbsolutePosition(parent);
	var sourceRect = findAbsolutePosition(source, parentRect);
	var targetRect = findAbsolutePosition(target, parentRect);

	let sourceX = 0;
	let targetX = 0;
	let sourceY = (sourceRect.top + sourceRect.bottom) / 2;
	let targetY = (targetRect.top + targetRect.bottom) / 2;
	var sourceCtlPt = {x: 0, y: 0}
	var targetCtlPt = {x: 0, y: 0}

	var markerWidth = 3;
	var arrow = 'left';

	if (targetRect.left - sourceRect.right > 3 * markerWidth || sourceRect.left - targetRect.right > 3 * markerWidth) {
		if (sourceRect.right < targetRect.left) {
			// rightward pointing arrow
			sourceX = sourceRect.right;
			targetX = targetRect.left;
			arrow = 'right';
		} else {
			// leftward pointing arrow
			sourceX = sourceRect.left;
			targetX = targetRect.right;
		}

		const midX = (sourceX + targetX) / 2;
		sourceCtlPt = {x: midX, y: sourceY};
		targetCtlPt = {x: midX, y: targetY};
	} else {
		let offsetX = 0;

		if (Math.abs(sourceRect.left - targetRect.left) < Math.abs(sourceRect.right - targetRect.right)) {
			sourceX = sourceRect.left;
			targetX = targetRect.left;
			offsetX = -(sourceRect.w + targetRect.w) / 2;
			arrow = 'right';
		} else {
			sourceX = sourceRect.right;
			targetX = targetRect.right;
			offsetX = (sourceRect.w + targetRect.w) / 2;
		}

		sourceCtlPt = {x: sourceX + offsetX, y: sourceY};
		targetCtlPt = {x: targetX + offsetX, y: targetY};
	}

	const path = `M${sourceX} ${sourceY} C${sourceCtlPt.x} ${sourceCtlPt.y} ${targetCtlPt.x} ${targetCtlPt.y} ${targetX} ${targetY}`;

	var marker = 'normal';

	if (source.parentNode.parentNode.classList.contains('clicked')) {
		marker = 'clicked';
	}
	else if (target.parentNode.parentNode.classList.contains('clicked')) {
		marker = 'linked';
	}

	var shape = document.createElementNS("http://www.w3.org/2000/svg", "path");
	shape.setAttributeNS(null, "d", path);
	shape.setAttributeNS(null, "fill", "none");

	var g = document.createElementNS("http://www.w3.org/2000/svg", "g");

	g.appendChild(shape);
	g.appendChild(createArrowMarker(targetX, targetY, arrow == 'left'));

	svg.appendChild(g);
	return g;
}

function createArrowMarker(x, y, reversed) {
	var shape = document.createElementNS("http://www.w3.org/2000/svg", "polygon");
	if (reversed) {
		var points = `${x+12},${y-7} ${x},${y} ${x+12},${y+7}`;
	}
	else {
		var points = `${x-12},${y-7} ${x},${y} ${x-12},${y+7}`;
	}
	shape.setAttributeNS(null, "points", points);
	shape.setAttributeNS(null, "stroke", 'none');
	shape.setAttributeNS(null, "stroke-width", '2px');
	return shape;
}
