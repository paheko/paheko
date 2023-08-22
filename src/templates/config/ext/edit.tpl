{include file="_head.tpl" title="%s — Modifier"|args:$module.label current="config"}

{include file="config/_menu.tpl" current="ext"}

<nav class="tabs">
	<aside>
		{linkbutton shape="help" label="Comment modifier et développer des modules" href="!static/doc/modules.html" target="_dialog"}

		{linkbutton shape="export" label="Exporter ce module" href="?module=%s&export"|args:$module.name}

		{linkmenu label="Ajouter…" shape="plus" right=true}
			{linkbutton shape="upload" label="Depuis mon ordinateur" target="_dialog" href="!common/files/upload.php?p=%s"|args:$parent_path_uri}
			{linkbutton shape="folder" label="Dossier" target="_dialog" href="!docs/new_dir.php?path=%s&no_redir"|args:$parent_path_uri}
			{linkbutton shape="text" label="Fichier texte" target="_dialog" href="!docs/new_file.php?path=%s"|args:$parent_path_uri}
		{/linkmenu}
	</aside>

	<ul class="sub">
		<li class="title">{$module.label}</li>
	</ul>
</nav>

{form_errors}

<table class="list">
	<tbody>
		{if $path}
		<tr>
			<td class="icon">{icon shape="left"}</td>
			<th>{link href="?module=%s"|args:$module.name label="Retour"}</th>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
		</tr>
		{/if}
		{foreach from=$list item="file"}
		<tr>
			<td class="icon">
				{if $file.dir}
					{icon shape="folder"}
				{/if}
			</td>
			<th>
				{if $file.dir}
					{link href="?module=%s&p=%s"|args:$module.name,$file.path label=$file.name}
				{elseif $file.editable}
					{link href=$file.edit_url label=$file.name target="_dialog"}
				{else}
					{link href=$file.open_url label=$file.name target="_dialog" data-mime=$file.type}
				{/if}
			</th>
			<td>
				{if $file.local}
					{$file.modified|relative_date}
				{else}
					(non modifié)
				{/if}
			</td>
			<td class="single-action">
				{if $file.editable}
					{linkbutton label="Éditer" shape="edit" target="_dialog" href=$file.edit_url}
				{/if}
			</td>
			<td class="single-action">
				{if $file.local && $file.dist && $file.editable}
					{linkbutton label="Différences" href="diff.php?module=%s&p=%s"|args:$module.name,$file.path shape="menu" target="_dialog"}
				{/if}
			</td>
			<td class="single-action">
				{if $file.local && $file.dist}
					{linkbutton label="Supprimer les modifications" href="%s&trash=no"|args:$file.delete_url shape="delete" target="_dialog"}
				{elseif $file.local && $file.dir}
					{linkbutton label="Supprimer ce dossier" href=$file.delete_url shape="trash" target="_dialog"}
				{elseif $file.local}
					{linkbutton label="Supprimer ce fichier" href=$file.delete_url shape="trash" target="_dialog"}
				{/if}
			</td>
		</tr>
		{/foreach}
	</tbody>
</table>

{if $module->hasDist() && $module->hasLocal()}
<p class="actions">
	{linkbutton label="Supprimer toutes les modifications" href="delete.php?module=%s&mode=reset"|args:$module.name shape="delete" target="_dialog"}
</p>
{/if}

{include file="_foot.tpl"}