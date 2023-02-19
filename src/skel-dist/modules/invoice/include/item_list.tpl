<script type="text/javascript">
function compute_total() {
	let items = document.getElementById('item_list').getElementsByClassName('item');
	let total = 0.0;
	let unit_price;
	let quantity;

	for (let i = 0; i < items.length; ++i) {
		unit_price = items[i].children[2].firstChild.firstChild.value.replace(',', '.');
		quantity = items[i].children[3].firstChild.value.replace(',', '.');
		if (!isNaN(unit_price) && !isNaN(quantity) && unit_price > 0)
			total += parseFloat(unit_price) * parseFloat(quantity);
	}
	return total;
}

function refresh_total() {
	const DEVISE = '{{$config.currency|htmlspecialchars|args:ENT_QUOTES}}';
	let total;
	
	total = compute_total();
	document.getElementById('quotation_total').textContent = parseFloat(total) + ' ' + DEVISE;
	document.getElementById('quotation_total_input').value = parseFloat(total);
}

function remove_item(id) {
	let items;

	document.getElementById('item_' + parseInt(id) + '_row').remove();
	items = document.getElementById('item_list').getElementsByClassName('item');
	if (items === 'undefined' || items.length === 0) {
		document.getElementById('item_list_no_item_message').style.display = 'block';
		document.getElementById('item_list').style.display = 'none';
	}
	refresh_total();
}

function add_delete_buttons() {
	let button;
	let td;

	$('tr.need_delete_button').forEach((row) => {
		button = document.createElement('button');
		button.type = 'button';
		button.name = 'item_delete_button';
		button.setAttribute('data-icon', '✘');
		button.setAttribute('data-item-id', 17);
		button.class = ' icn-btn';
		button.onclick = (event) => {
			remove_item(event.target.parentNode.parentNode.getAttribute('data-item-id'));
			refresh_total();
		};
		td = document.createElement('td');
		td.classList.add('item_actions');
		td.appendChild(button);
		row.appendChild(td);
		row.classList.remove('need_delete_button');
	});
}

function add_input_refresh_total_behavior() {
	$('input.impact_total').forEach((input) => {
		input.onchange = refresh_total;
	});
}
</script>

<fieldset>
	<legend><h2>Liste des articles</h2></legend>
	<p id="item_list_no_item_message" style="display: {{if !$items}}block{{else}}none{{/if}};">Aucun article pour le moment.</p>
	<table id="item_list" class="list" style="display: {{if $items}}block{{else}}none{{/if}};">
		<thead>
			<tr>
				<th>Dénomination</th>
				<th>Description</th>
				<th>Prix unitaire</th>
				<th>Quantité</th>
				<th></th>
			</tr>
		</thead>
		<tbody>

		{{#foreach from=$items key='key' item='item'}}
			<tr id={{"item_%d_row"|args:$key}} class="item" data-item-id="{{$key}}">
				<td>{{:input type="text" name="items[%d][name]"|args:$key default=$item.name}}</td>
				<td>{{:input type="textarea" cols="50" rows="2" name="items[%s][description]"|args:$key default=$item.description class="full-width"}}</td>
				<td>{{:input type="money" name="items[%d][unit_price]"|args:$key default=$item.unit_price class="impact_total"}}</td>
				<td>{{:input type="number" name="items[%d][quantity]"|args:$key default=$item.quantity class="impact_total"}}</td>
				<td>{{:button name="item_%d_delete_button" label="" shape="delete" onclick="remove_item(%d)"|args:$key}}</td>
			</tr>
		{{/foreach}}

		</tbody>
	</table>
	<p>{{:linkbutton label="Ajouter un article" href="item_form.html" shape="plus" target="_dialog"}}</p>
	<h3 style="padding: 1em 0em 0.5em 0em;">Total des articles</h3>
	<p>
		<span id="quotation_total">0 {{$config.currency|htmlspecialchars}}</span>
		<input type="hidden" name="quotation_total" id="quotation_total_input" label="" value="" />
		{{:button name="refresh_total_button" label="Recalculer" shape="reload" onclick="refresh_total()"}}
	</p>
</fieldset>

<script type="text/javascript">
	add_input_refresh_total_behavior();
	refresh_total();
</script>