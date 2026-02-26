{include file="_head.tpl" title="Prévisionnel — %s"|args:$year.label current="acc/years"}

<nav class="tabs">
	{if !$edit}
	<aside>
		{exportmenu table=true}
		{linkbutton shape="download" label="Télécharger en PDF" href="?id=%d&_pdf=1"|args:$year.id}
		{linkbutton shape="eye" label="Voir le prévisionnel comparé" href="!acc/reports/statement.php?year=%d&provisional=1"|args:$year.id}
	</aside>
	{/if}
	<ul>
		<li{if !$edit} class="current"{/if}>{link href="?id=%d"|args:$year.id label="Prévisionnel"}</li>
		<li{if $edit} class="current"{/if}>{link href="?id=%d&edit=1"|args:$year.id label="Modifier"}</li>
	</ul>
</nav>


{if $edit}
	{form_errors}
	<form method="post" action="{$self_url}">
{else}
	<header class="summary print-only">
		{if $config.files.logo}
		<figure class="logo print-only"><img src="{$config->fileURL('logo', '150px')}" alt="" /></figure>
		{/if}
		<h2>{$config.org_name} — Budget prévisionnel</h2>
	{if isset($year)}
		<p>Exercice&nbsp;: {$year.label} ({if $year->isClosed()}clôturé{else}<strong>en cours</strong>{/if})
			— du {$year.start_date|date_short}
			— au {$year.end_date|date_short}
		</p>
	{/if}
	</header>
{/if}

	<table class="{if $edit}list{else}statement{/if} provisional">
		<thead>
			<tr>
				<th colspan="3" width="49%">Charges</th>
				<td class="spacer"></td>
				<th colspan="3" width="49%">Produits</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td colspan="3" data-column="0">
					{include file="./_provisional_table.tpl" table=$pro.expense type=$type_expense}
				</td>
				<td class="spacer"></td>
				<td colspan="3" data-column="1">
					{include file="./_provisional_table.tpl" table=$pro.revenue type=$type_revenue}
				</td>
			</tr>
		</tbody>
		<tfoot>
			<tr>
				<th>Total</th>
				{if $edit}
					<td class="money" data-column="0"></td>
					<td width="5%" class="actions">{button type="button" shape="plus" class="add" data-column="0" title="Ajouter une ligne"}</td>
				{else}
					<td class="money" colspan="2">{$pro.expense_total|raw|money:false}</td>
				{/if}
				<td class="spacer"></td>
				<th>Total</th>
				{if $edit}
					<td class="money" data-column="1"></td>
					<td width="5%" class="actions">{button type="button" shape="plus" class="add" data-column="1" title="Ajouter une ligne"}</td>
				{else}
					<td class="money" colspan="2">{$pro.revenue_total|raw|money:false}</td>
				{/if}
			</tr>
			{if !$edit}
			<tr>
				<td colspan="4" class="colspan"></td>
				<th>Résultat</th>
				<td class="money" colspan="2">{$pro.result|raw|money:false}</td>
			</tr>
			{/if}
		</tfoot>
	</table>

{if $edit}
	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>
</form>

<script type="text/javascript">
{literal}
function findParent(parent, name) {
	while (parent.tagName.toLowerCase() !== name) {
		parent = parent.parentNode;
	}

	return parent;
}

function removeLine(e) {
	var cell = findParent(findParent(e.target, 'table'), 'td');
	findParent(e.target, 'tr').remove();
	updateTotal(findParent(cell, 'table'), cell.dataset.column);
}

function updateTotal(root_table, column) {
	var total = 0;
	var target = root_table.querySelector('tfoot tr td[data-column="' + column + '"]');

	root_table.querySelectorAll('tbody tr td[data-column="' + column + '"] input.money').forEach(el => {
		total += g.getMoneyAsInt(el.value);
	});

	target.innerText = g.formatMoney(total);
}

document.querySelectorAll('button.remove').forEach(btn => {
	btn.onclick = removeLine;
});

document.querySelectorAll('button.add').forEach(btn => {
	btn.onclick = () => {
		var column = btn.dataset.column;
		var root_table = findParent(btn, 'table');
		var table = root_table.querySelector('tbody tr td[data-column="' + column + '"] table');
		var last_row = table.querySelector('tbody > tr:last-child');
		var new_row = last_row.cloneNode(true);
		new_row.querySelector('button.remove').onclick = removeLine;
		new_row.querySelector('.input-list > button').onclick = g.listSelectorButtonClicked;
		new_row.querySelector('input.money').onkeyup = () => updateTotal(root_table, column);
		last_row.parentNode.append(new_row);
		updateTotal(findParent(btn, 'table'), column);
	};
});

document.querySelectorAll('table.provisional > tbody > tr > td[data-column]').forEach(cell => {
	var table = findParent(cell, 'table');
	cell.querySelectorAll('input.money').forEach(el => {
		el.onkeyup = () => updateTotal(table, cell.dataset.column);
	});
	updateTotal(table, cell.dataset.column);
});
{/literal}
</script>
{/if}

{include file="_foot.tpl"}