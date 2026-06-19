{include file="_head.tpl" title="Rechercher dans les fichiers" current="docs"}

<form method="post" action="{$self_url}" data-focus="1">
	<fieldset>
		<legend>Rechercher un fichier</legend>
		<p class="submit">
			<input type="text" name="q" value="{$query}" size="25" />
			{button type="submit" name="search" label="Chercher" shape="search" class="main"}
		</p>
	</fieldset>
</form>

{if $query}
	<p class="block alert">
		<strong>{$results_count}</strong> fichiers trouvés pour «&nbsp;{$query}&nbsp;»
	</p>

	<section class="search-results">
	{foreach from=$results item="r"}
		<article>
			<h4><a href="{"!docs/?id=%s"|local_url|args:$r.file.hash_id}" target="_parent">{$r.result.breadcrumbs|escape|highlight_search_snippet}</a></h4>
			<h3>
				<a href="{"!docs/?id=%s"|local_url|args:$r.file.hash_id}" target="_parent">{$r.result.name_snippet|escape|highlight_search_snippet}</a>
			</h3>
			<p>
				{if $r.file->isDir()}
					<em>(répertoire)</em>
				{else}
					{$r.result.snippet|escape|highlight_search_snippet}
				{/if}
			</p>
		</article>
	{/foreach}
	</section>
{/if}

{include file="_foot.tpl"}