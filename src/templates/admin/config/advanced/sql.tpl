{include file="admin/_head.tpl" title="SQL" current="config" custom_css=["styles/config.css"]}

{include file="admin/config/_menu.tpl" current="advanced" sub_current="sql"}

{form_errors}

{if $query}
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

	{if !empty($result)}

		<p class="help">{$result|count} résultats trouvés pour cette requête.</p>
		<table class="list search">
			<thead>
				<tr>
					{foreach from=$result_header item="label"}
						<td>{$label}</td>
					{/foreach}
					<td></td>
				</tr>
			</thead>
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

	{elseif $result !== null}

		<p class="block alert">
			Aucun résultat trouvé.
		</p>

	{/if}

{elseif $table}
	<h2 class="ruler">Table : {$table}</h2>

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

	{pagination url=$list->paginationURL() page=$list.page bypage=$list.per_page total=$list->count()}

{else}

	<p class="help block">
		Cette page vous permet de visualiser les données brutes de la base de données.
	</p>

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

	<h2 class="ruler">Liste des tables</h2>

	<dl class="describe">
		{foreach from=$tables_list key="name" item="table"}
			<dt><a href="?table={$name}">{$name}</a></dt>
			<dd><em>{$table.count} lignes</em></dd>
			<dd><pre>{$table.sql}</pre></dd>
		{/foreach}
	</dl>

	<h2 class="ruler">Liste des index</h2>

	<dl class="describe">
		{foreach from=$index_list key="name" item="sql"}
			<dt>{$name}</dt>
			<dd><pre>{$sql}</pre></dd>
		{/foreach}
	</dl>

	<h2 class="ruler">Liste des triggers</h2>

	<dl class="describe">
		{foreach from=$triggers_list key="name" item="sql"}
			<dt>{$name}</dt>
			<dd><pre>{$sql}</pre></dd>
		{/foreach}
	</dl>

{/if}

{include file="admin/_foot.tpl"}