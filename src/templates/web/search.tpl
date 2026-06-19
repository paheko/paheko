{include file="_head.tpl" title="Rechercher dans le site web" current="web"}

<form method="post" action="{$self_url}">
	<fieldset>
		<legend>Rechercher une page ou catégorie</legend>
		<p class="submit">
			<input type="text" name="q" value="{$query}" size="25" />
			{button type="submit" name="search" label="Chercher" shape="search" class="main"}
		</p>
	</fieldset>
</form>

{if $query}
	<p class="help">
		{{%n résultat trouvé.}{%n résultats trouvés.} n=$results_count}
	</p>

	<section class="search-results">
	{foreach from=$results item="result"}
		<article>
			<h3><a href="{"!web/?id=%d"|local_url|args:$result.id}" target="_parent">{$result.title_snippet|escape|highlight_search_snippet}</a></h3>
			<p>{$result.snippet|escape|highlight_search_snippet}</p>
		</article>
	{/foreach}
	</section>
{/if}

{include file="_foot.tpl"}