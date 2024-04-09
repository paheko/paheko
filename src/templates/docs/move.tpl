{include file="_head.tpl" title="Déplacer des fichiers" current="docs"}

{form_errors}

<form method="post" action="{$self_url}" class="dir-picker">
	<nav class="breadcrumbs">
		<ul>
		{foreach from=$breadcrumbs item="name" key="path"}
			<li class="{if $path == $current_path}current{/if}">{button label=$name type="submit" name="current" value=$path}</li>
		{/foreach}
		</ul>
	</nav>

	<nav class="folders">
		<ul>
		{if $parent}
			<li class="parent">
				{button shape="left" label="Retour au dossier parent" type="submit" name="current" value=$parent}
			</li>
		{/if}

		{foreach from=$directories item="dir"}
			<li class="folder">
				{button shape="folder" label=$dir.name type="submit" name="current" value=$dir.path}
			</li>
		{foreachelse}
			<li class="help">Aucun sous-dossier ici.</li>
		{/foreach}

			<li class="select">
				<span class="help">{{%n fichier sélectionné.}{%n fichiers sélectionnés.} n=$count}</span><br />
				{button shape="right" label="Déplacer vers \"%s\""|args:$current_path_name type="submit" name="move" value=$current_path}
			</li>
		</ul>
	</nav>

	{csrf_field key=$csrf_key}

	{foreach from=$check key="key" item="value"}
		<input type="hidden" name="check[]" value="{$value}" />
	{/foreach}

	<input type="hidden" name="action" value="{$_POST.action}" />

</form>

{include file="_foot.tpl"}
