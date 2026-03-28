{include file="_head.tpl" title="Liste des tables de la base de données" current="config"}

{include file="./_nav.tpl" current="tables"}

<table class="list">
	<thead>
		<tr>
			<th>Nom</th>
			<td class="num">Nombre de lignes</td>
			<td class="size">Taille</td>
			<td></td>
		</tr>
	</thead>
	<tbody>
	{foreach from=$tables_list key="name" item="table"}
		<tr>
			<th><a href="table_list.php?name={$name}">{$name}</a></th>
			<td class="num">{$table.count}</td>
			<td class="size">{if $table.size !== null}{$table.size|size_in_bytes}{else}(inconnue){/if}</td>
			<td class="actions">
				{linkbutton shape="menu" href="table_list.php?name=%s"|args:$name label="Parcourir"}
				{linkbutton shape="table" href="table.php?name=%s"|args:$name label="Structure"}
			</td>
		</tr>
	{/foreach}
	</tbody>
</table>

<h2 class="ruler">Liste des triggers</h2>

<table class="list">
	{foreach from=$triggers_list key="name" item="sql"}
	<tr>
		<th>{$name}</th>
		<td><pre>{$sql}</pre></td>
	</tr>
	{/foreach}
</table>

<h2 class="ruler">Liste des index</h2>

<table class="list">
	{foreach from=$index_list key="name" item="sql"}
	<tr>
		<th>{$name}</th>
		<td><tt>{$sql}</tt></td>
	</tr>
	{/foreach}
</table>

{include file="_foot.tpl"}