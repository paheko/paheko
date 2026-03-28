{include file="_head.tpl" title="%s — Structure"|args:$table current="config"}

{include file="./_nav.tpl" current="tables"}

<div class="center-block">
	<p>
		{linkbutton shape="menu" href="table_list.php?name=%s"|args:$info.name label="Parcourir les données"}
	</p>

	{include file="common/_sql_table.tpl" table=$info.schema indexes=$info.indexes fk_link=true class="center"}

	<h2 class="ruler">Schéma SQL de la table</h2>
	<pre>{$info.sql}</pre>
	<h2 class="ruler">Schéma des index</h2>
	<pre>{if empty($info.sql_indexes)}<em>Aucun index</em>{else}{$info.sql_indexes}{/if}</pre>
</div>

{include file="_foot.tpl"}
