{include file="_head.tpl" title="Requête SQL" current="config"}

{include file="./_nav.tpl" current="query"}

{form_errors}

<form method="get" action="{$self_url}" data-disable-progress="1">
{if $pragma}
	<fieldset>
		<legend>Requête SQL</legend>
		<pre>{$pragma}</pre>
	</fieldset>
{else}
	<fieldset>
		<legend>Requête SQL en lecture</legend>
		<dl>
			{input type="textarea" cols="70" rows="10" name="query" default=$query class="full-width"}
		</dl>
		<p>
			{button type="submit" name="run" label="Exécuter" shape="search"}
		</p>
	</fieldset>
{/if}

{if !empty($result_count)}

	{if !$pragma}
	<p class="actions">
		{exportmenu form=true right=true}
	</p>
	{/if}

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

{include file="_foot.tpl"}
