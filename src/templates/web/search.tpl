{include file="admin/_head.tpl" title="Rechercher dans le site web" current="web"}

<form method="post" action="{$self_url}" data-focus="1">
	<fieldset>
		<legend>Rechercher une page ou catégorie</legend>
		<p class="submit">
			<input type="text" name="q" value="{$query}" size="25" />
			{button type="submit" name="search" label="Chercher" shape="search" class="main"}
		</p>
	</fieldset>
</form>

{if $query}
	<p class="block alert">
		<strong>{$results_count}</strong> pages trouvées pour «&nbsp;{$query}&nbsp;»
	</p>

	<section class="search-results">
	{foreach from=$results item="result"}
		<article>
			<h4>
				<nav class="breadcrumbs">
					<ul>
						{foreach from=$result.breadcrumbs key="id" item="title"}
							<li><a href="{"!web/page.php?p=%s"|local_url|args:$id}" target="_parent">{$title}</a></li>
						{/foreach}
					</ul>
				</nav>
			</h4>
			<h3><a href="{"!web/page.php?p=%s"|local_url|args:$result.path}" target="_parent">{$result.title}</a></h3>
			<p>{$result.snippet|escape|clean_snippet}</p>
		</article>
	{/foreach}
	</section>
{/if}

{include file="admin/_foot.tpl"}