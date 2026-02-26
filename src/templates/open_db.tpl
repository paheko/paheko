{include file="_head.tpl" title="Ouvrir une base de données" current=""}

{form_errors}

<p style="border: 2px solid #666; border-radius: .5em; background: #fff; padding: .5em">
	{linkbutton shape="left" label="Retour" href="!" style="float: right"}
	Base de données actuelle :<br />
	<samp style="background: #eee; padding: .2em">{$current_db}</samp>
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
}
nav.files li {
	padding: .2em 1em;
}
nav.files li:nth-child(even) {
	background: #eee;
}
nav.files .path {
	font-size: 1.2em;
}
{/literal}
</style>

<nav class="files">
	<ul>
		<li>
			{linkbutton shape="left" label="Répertoire parent" href="?path="|cat:$parent_uri}
			{linkbutton shape="plus" label="Créer une nouvelle base de données ici" href="create_db.php?path="|cat:$path_uri style="float: right"}
		</li>
		<li class="path"><strong>{$path}</strong></li>
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