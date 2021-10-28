{include file="admin/_head.tpl" title="Rechercher dans les fichiers" current="docs"}

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
	{foreach from=$results item="result"}
		<article>
			<h4><a href="{"!docs/?parent=%s"|local_url|args:$result.parent}" target="_parent">{$result.path}</a></h4>
			<h3><a href="{"!docs/?parent=%s"|local_url|args:$result.parent}" target="_parent">{$result.title}</a></h3>
			<p>{$result.snippet|escape|clean_snippet}</p>
		</article>
	{/foreach}
	</section>
{/if}

{include file="admin/_foot.tpl"}