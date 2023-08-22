{include file="_head.tpl" title="Ajouter/supprimer des écritures à un projet" current="acc/accounts"}

{form_errors}

<form method="post" action="{$self_url}" class="dir-picker">
	<nav class="breadcrumbs">
		<ul>
		{foreach from=$breadcrumbs item="name" key="path"}
			<li class="{if $path == $current_path}current{/if}">{button label=$name type="submit" name="current" value=$path}</li>
		{/foreach}
		</ul>
	</nav>

	<table>
		{if $parent}
		<tr class="parent">
			<td colspan="2">{button shape="left" label="Retour au répertoire parent" type="submit" name="current" value=$parent}</td>
		</tr>
		{/if}

		{foreach from=$directories item="dir"}
		<tr>
			<th>{button shape="folder" label=$dir.name type="submit" name="current" value=$dir.path}</th>
			<td class="actions">{button shape="right" label="Déplacer vers ce répertoire" type="submit" name="move" value=$dir.path}</td>
		</tr>
		{foreachelse}
		<tr>
			<td colspan="2" class="help">Aucun sous-répertoire ici.</td>
		</tr>
		{/foreach}
		<tr>
			<td colspan="2" class="select">
				<span class="help">{{%n fichier sélectionné.}{%n fichiers sélectionnés.} n=$count}</span><br />
				{button shape="right" label="Déplacer vers \"%s\""|args:$current_path_name type="submit" name="move" value=$current_path}
			</td>
		</tr>
	</table>

	{csrf_field key=$csrf_key}

	{if isset($extra)}
		{foreach from=$extra key="key" item="value"}
			{if is_array($value)}
				{foreach from=$value key="subkey" item="subvalue"}
					<input type="hidden" name="{$key}[{$subkey}]" value="{$subvalue}" />
				{/foreach}
			{else}
				<input type="hidden" name="{$key}" value="{$value}" />
			{/if}
		{/foreach}
	{/if}

</form>

{include file="_foot.tpl"}
