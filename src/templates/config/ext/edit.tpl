{include file="_head.tpl" title="%s — Modifier"|args:$module.label current="config"}

{include file="config/_menu.tpl" current="ext"}

<nav class="tabs">
	<aside>
		{linkbutton shape="help" label="Comment modifier et développer des modules" href="!static/doc/modules.html" target="_dialog"}
	</aside>

	<ul class="sub">
		<li><strong>{$module.label}</strong></li>
	</ul>
</nav>

{form_errors}

<form method="post" action="">
	<table class="list">
		<thead>
			<td class="check"><input type="checkbox" title="Tout cocher / décocher" id="f_all" /><label for="f_all"></label></td>
			<td class="icon"></td>
			<th>Nom du fichier</th>
			<td></td>
			<td class="actions"></td>
			<td class="actions"></td>
		</thead>
		<tbody>
			{if $path}
			<tr>
				<td></td>
				<td class="icon">{icon shape="left"}</td>
				<th>{link href="?module=%s"|args:$module.name label="Retour"}</th>
				<td></td>
				<td></td>
				<td></td>
			</tr>
			{/if}
			{foreach from=$list item="file"}
			<tr>
				<td class="check">{if $file.dist && $file.local}{input type="checkbox" name="check[%s]"|args:$file.path value=1}{/if}</td>
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
						{link href=$file.open_url label=$file.name target="_blank"}
					{/if}
				</th>
				<td>
					{if $file.local}
						{$file.modified|relative_date}
					{else}
						(non modifié)
					{/if}
				</td>
				<td class="actions">
					{if $file.local && $file.dist}
						{if $file.editable}
							{linkbutton label="Diff" href=$file.edit_url shape="menu"}
						{/if}
					{elseif $file.local}
						{linkbutton label="Supprimer" href=$file.delete_url shape="delete" target="_dialog"}
					{/if}
				</td>
				<td class="actions">
					{if $file.editable}
						{linkbutton label="Éditer" shape="edit" target="_dialog" href=$file.edit_url}
					{/if}
				</td>
			</tr>
			{/foreach}
		</tbody>
	</table>
	<p class="actions">
		{button shape="reset" label="Remettre à zéro les fichiers sélectionnés"}
	</p>
	{csrf_field key=$csrf_key}
</form>

{include file="_foot.tpl"}