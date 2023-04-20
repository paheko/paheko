<script src="./include/item_list.js"></script>

<fieldset>
	<legend><h2>Liste des articles</h2></legend>
	<table id="item_list" class="list">
		<thead>
			<tr>
				<th>Réf.</th>
				<th>Dénomination</th>
				<th>Description</th>
				<th>Prix unitaire</th>
				<th>Quantité</th>
				<th></th>
			</tr>
		</thead>

		<tbody>

		{{* Items *}}
		{{:assign items_source=$_POST.items|or:$items}}
		{{#foreach from=$items_source key='key' item='item'}}
			{{if $item.unit_price || $item.quantity}} {{* Ignore default empty line *}}
				<tr id="{{'item_%d_row'|args:$key}}" class="item" data-item-id="{{$key}}">
					<td>{{:input type="text" name="items[%d][reference]"|args:$key default=$item.reference class="reference"}}</td>
					<td>{{:input type="text" name="items[%d][name]"|args:$key default=$item.name}}</td>
					<td>{{:input type="textarea" cols="50" rows="2" name="items[%s][description]"|args:$key default=$item.description class="full-width"}}</td>
					<td>{{if $_POST.items}}{{:assign unit_price=$item.unit_price|money_int}}{{else}}{{:assign unit_price=$item.unit_price}}{{/if}}{{:input type="money" name="items[%d][unit_price]"|args:$key default=$unit_price class="impact_total"}}</td>
					<td>{{:input type="number" name="items[%d][quantity]"|args:$key default=$item.quantity class="impact_total" min="0" max="9999"}}</td>
					<td>{{:button name="item_%d_delete_button" label="Enlever" shape="minus" onclick="remove_item(%d)"|args:$key}}</td>
				</tr>
				{{:assign last_key=$key}} {{* $key is local and we need its value right after the foreach *}}
			{{/if}}
		{{/foreach}}

		{{* Empty line for user input *}}
		{{:assign var='key' value="%d + 1"|math:$last_key}}
		<tr id="{{'item_%d_row'|args:$key}}" class="item" data-item-id="{{$key}}">
			<td>{{:input type="text" name="items[%d][reference]"|args:$key class="reference" placeholder="ex : S-812"}}</td>
			<td>{{:input type="text" name="items[%d][name]"|args:$key placeholder="ex : Location vélo - Forfait six jours"}}</td>
			<td>{{:input type="textarea" cols="50" rows="2" name="items[%s][description]"|args:$key class="full-width"}}</td>
			{{* Awaiting #eeb7d3e36ef4e3d7f2e8bc49c18c3c6b672f2e18 resolution {{:input type="money" name="item_unit_price" id="item_unit_price" label="Prix unitaire" default="0"}} *}}
			<td><nobr><input type="text" name="{{"items[%d][unit_price]"|args:$key}}" id="{{"f_items%dunit_price"|args:$key}}" value="0" class="money impact_total" pattern="-?[0-9]+([.,][0-9]{1,2})?" inputmode="decimal" size="8" autocomplete="off" class="money" /></nobr></td>
			{{*<td>{{:input type="money" name="items[%d][unit_price]"|args:$key class="impact_total" default="0" required="false"}}</td>*}}
			<td>{{:input type="number" name="items[%d][quantity]"|args:$key class="impact_total" min="0" max="9999" default="0"}}</td>
			<td>{{:button name="item_%d_delete_button"|args:$key label="Enlever" shape="minus" onclick="remove_item(%d)"|args:$key}}</td>
		</tr>

		</tbody>

		<tfoot>
			<tr>
				<td colspan="5"></td>
				<td>{{:button name="item_%d_delete_button" label="Ajouter" shape="plus" onclick="add_item()"}}</td>
			</tr>
		</tfoot>
	</table>
	<h3 class="quotation_total">Total des articles : <span id="quotation_total">{{$document.total|intval|money:false}}</span> €</h3>
	<input type="hidden" name="quotation_total" id="quotation_total_input" label="" value="" />
</fieldset>

<script type="text/javascript">
	add_input_refresh_total_behavior();
	refresh_total();
</script>
