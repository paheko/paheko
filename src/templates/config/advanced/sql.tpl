{include file="_head.tpl" title="SQL" current="config"}

{include file="config/_menu.tpl" current="advanced" sub_current="sql"}

{if !$diagram}
<nav class="tabs">
	{if isset($query) || isset($table) || isset($table_info)}
		{linkbutton shape="left" label="Retour à la liste des tables SQL" href="sql.php"}
	{else}
		{linkbutton shape="search" label="Exécuter une requête SQL" href="?query="}
	{/if}
	<aside>
		{linkbutton shape="table" label="Diagramme" href="?diagram"}
		{linkbutton shape="check" label="Vérifier la BDD" href="?pragma=integrity_check"}
		{linkbutton shape="check" label="Vérifier les clés étrangères" href="?pragma=foreign_key_check"}
		{if ENABLE_TECH_DETAILS}
			{linkbutton shape="reload" label="Reconstruire" href="?pragma=vacuum"}
		{/if}
	</aside>
</nav>
{/if}

{form_errors}

{if isset($query)}
	<form method="post" action="{$self_url}" data-disable-progress="1">
	{if $query !== null}
		<h2 class="ruler">Requête SQL</h2>

		<fieldset>
			<legend>Faire une requête SQL en lecture</legend>
			<dl>
				{input type="textarea" cols="70" rows="10" name="query" default=$query class="full-width"}
			</dl>
			<p>
				{button type="submit" name="run" label="Exécuter" shape="search"}
			</p>
		</fieldset>
	{/if}

	{if !empty($result_count)}

		<p class="actions">
			{exportmenu form=true right=true}
		</p>
		<p class="alert block">{$result_count} résultats trouvés pour cette requête, en {$query_time} ms.</p>
		<table class="list search">
			{if $result_header}
			<thead>
				<tr>
					{foreach from=$result_header item="label"}
						<td>{$label}</td>
					{/foreach}
				</tr>
			</thead>
			{/if}
			<tbody>
				{foreach from=$result item="row"}
					<tr>
						{foreach from=$row key="key" item="value"}
							<td>
								{if null === $value}
									<em>NULL</em>
								{else}
									{$value}
								{/if}
							</td>
						{/foreach}
					</tr>
				{/foreach}
			</tbody>
		</table>

	{elseif isset($result)}

		<p class="block alert">
			Aucun résultat trouvé.
		</p>

	{/if}
	</form>

{elseif !empty($table_info)}

	<div class="center-block">
		<h2 class="ruler">Table : {$table_info.name}</h2>
		<p>
			{linkbutton shape="menu" href="?table=%s"|args:$table_info.name label="Parcourir les données"}
		</p>


		{include file="common/_sql_table.tpl" table=$table_info.schema indexes=$table_info.indexes fk_link=true class="center"}

		<h2 class="ruler">Schéma de la table</h2>
		<pre>{$table_info.sql}</pre>
		<h2 class="ruler">Schéma des index</h2>
		<pre>{if empty($table_info.sql_indexes)}<em>Aucun index</em>{else}{$table_info.sql_indexes}{/if}</pre>
	</div>

{elseif !empty($table)}

	<h2 class="ruler">Table : {$table}</h2>
	<div class="center-block">
		<p>
			{linkbutton shape="table" href="?table_info=%s"|args:$table label="Voir la structure"}
			{exportmenu}
		</p>
	</div>

	{$list->getHTMLPagination()|raw}

	{include file="common/dynamic_list_head.tpl"}

		{foreach from=$list->iterate() item="row"}
			<tr>
				{foreach from=$row key="key" item="value"}
				<td>
					{if null == $value}
						<em>NULL</em>
					{elseif ($fk = $foreign_keys[$key] ?? null) && $fk.to}
						{link href="?table=%s&only[%s]=%s"|args:$fk.table:$fk.to:$value label=$value class="num"}
					{elseif Utils::is_json($value) || ($is_module && $key === 'document')}
						<pre>{$value|format_json}</pre>
					{else}
						{$value}
					{/if}
				</td>
				{/foreach}
				<td></td>
			</tr>
		{/foreach}

		</tbody>
	</table>

	{$list->getHTMLPagination()|raw}

{elseif $diagram}

	<h2 class="ruler">Schéma de la base de données</h2>

	<div class="er-diagram">
	{foreach from=$tables item="table"}
		<table id="t_{$table.name}">
			<caption>{link href="?table_info=%s"|args:$table.name label=$table.name}</caption>
			<tbody>
			{foreach from=$table.columns item="column"}
				<tr id="t_{$table.name}_{$column.name}"
					{if $column.fk.table && $column.fk.to}
					data-fk-table="{$column.fk.table}"
					data-fk-column="{$column.fk.to}"
					{/if}>
					<td>{$column.name}</td>
				</tr>
			{/foreach}
			</tbody>
		</table>
	{/foreach}
	</div>

	<script type="text/javascript" src="{$admin_url}static/scripts/erdiagram.js"></script>
{else}

	<h2 class="ruler">Liste des tables</h2>

	<table class="list auto center">
		<thead>
			<tr>
				<th>Nom</th>
				<td></td>
				<td>Nombre de lignes</td>
				<td>Taille</td>
			</tr>
		</thead>
		<tbody>
		{foreach from=$tables_list key="name" item="table"}
			<tr>
				<th><a href="?table={$name}">{$name}</a></th>
				<td>
					{linkbutton shape="menu" href="?table=%s"|args:$name label="Parcourir"}
					{linkbutton shape="table" href="?table_info=%s"|args:$name label="Structure"}
				</td>
				<td class="num">{$table.count}</td>
				<td class="size">{if $table.size !== null}{$table.size|size_in_bytes}{else}(inconnue){/if}</td>
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

{/if}

{include file="_foot.tpl"}