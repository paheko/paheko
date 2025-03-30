{include file="_head.tpl" title="Ouvrir une base de données" current=""}

{form_errors}

<p>
	Base de données actuelle : <samp>{$current_db}</samp>
	{linkbutton shape="left" label="Retour" href="!"}
</p>

{if $error}
	<p class="error block">{$error}</p>
{/if}

<style type="text/css">
{literal}
nav.files ul {
	display: flex;
	flex-direction: column;
	gap: .5em;
	border: 2px solid #ccc;
	border-radius: .5em;
	margin: 1em 0;
	padding: 1em;
}
{/literal}
</style>

<nav class="files">
	<ul>
		<li><strong>{$path}</strong></li>
		<li>{linkbutton shape="left" label="Répertoire parent" href="?path="|cat:$parent_uri}</li>
		{foreach from=$list item="item"}
		<li>
			{if $item.dir}
				<strong><a href="?path={$item.uri}">{$item.name}/</a></strong>
			{else}
				<a href="?path={$item.uri}">{$item.name}</a>
			{/if}
		</li>
		{/foreach}
	</ul>
</nav>

{include file="_foot.tpl"}