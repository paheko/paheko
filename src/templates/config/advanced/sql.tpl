{include file="_head.tpl" title="SQL" current="config" custom_css=["config.css"]}

{include file="config/_menu.tpl" current="advanced" sub_current="sql"}

{form_errors}

{if isset($result)}
	{if $query !== null}
		<h2 class="ruler">Requête SQL</h2>

		<form method="post" action="{$self_url}">
			<fieldset>
				<legend>Requête SQL</legend>
				<dl>
					{input type="textarea" cols="70" rows="10" name="query" default=$query}
				</dl>
				<p class="submit">
					{button type="submit" name="run" label="Exécuter" shape="search" class="main"}
				</p>
			</fieldset>
		</form>
	{else}
		<p>
			{linkbutton shape="left" label="Retour" href="?"}
		</p>
	{/if}

	{if !empty($result_count)}

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

{elseif !empty($table_info)}

<div class="center-block">
	<h2 class="ruler">Table : {$table_info.name}</h2>
	<p class="actions">
		{linkbutton shape="menu" href="?table=%s"|args:$table_info.name label="Parcourir les données"}
	</p>


	{include file="common/_sql_table.tpl" table=$table_info.schema indexes=$table_info.indexes fk_link=true class="center"}

	<h2 class="ruler">Schéma</h2>
	<pre>{$table_info.sql}</pre>
	<pre>{$table_info.sql_indexes}</pre>
</div>

{elseif !empty($table)}

	<h2 class="ruler">Table : {$table}</h2>
	<div class="center-block">
		<p class="actions">
			{linkbutton shape="table" href="?table_info=%s"|args:$table label="Voir la structure"}
		</p>
	</div>

	{$list->getHTMLPagination()|raw}

	{include file="common/dynamic_list_head.tpl"}

		{foreach from=$list->iterate() item="row"}
			<tr>
				{foreach from=$row item="value"}
				<td>
					{if null == $value}
						<em>NULL</em>
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

{else}

<div class="center-block">
	<p class="help block">
		Cette page vous permet de visualiser les données brutes de la base de données.
	</p>

	<form method="post" action="{$self_url}">
		<fieldset>
			<legend>Faire une requête SQL en lecture</legend>
			<dl>
				{input type="textarea" cols="70" rows="3" name="query" default=$query class="full-width"}
			</dl>
			<p>
				{button type="submit" name="run" label="Exécuter" shape="search"}
			</p>
		</fieldset>
	</form>
</div>
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
				<td>{$table.count} lignes</td>
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