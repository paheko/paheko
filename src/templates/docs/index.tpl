<?php
use Garradin\Entities\Files\File;
?>
{include file="admin/_head.tpl" title="Documents" current="docs"}

<nav class="tabs">
	<aside>
		{linkbutton shape="search" label="Rechercher" href="search.php"}
		{linkbutton shape="plus" label="Nouveau répertoire" href="new_dir.php?c=%s&parent=%s"|args:$context,$parent}
	</aside>
	<ul>
		<li class="current"><a href="./">Documents</a></li>
		{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
			<li><a href="./?c=<?=File::CONTEXT_TRANSACTION?>">Fichiers des écritures</a></li>
		{/if}
		{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)}
			<li><a href="./?c=<?=File::CONTEXT_USER?>">Fichiers des membres</a></li>
		{/if}
	</ul>
</nav>

<table class="list">
	<thead>
		<tr>
			<td class="check"><input type="checkbox" value="Tout cocher / décocher" id="f_all" /><label for="f_all"></label></td>
			<th>Nom</th>
			<td>Modifié</td>
			<td>Type</td>
			<td>Taille</td>
			<td></td>
		</tr>
	</thead>

	<tbody>

	{foreach from=$files item="file"}
		{if !is_object($file)}
		<tr>
			{if $can_delete}
			<td class="check"></td>
			{/if}
			<th><a href="?c={$context}&amp;p={$file}">{$file}</a></th>
			<td colspan="4"></td>
		</tr>
		{else}
		</tr>
			{if $can_delete}
			<td class="check">
				{input type="checkbox" name="check[]" value=$file.id}
			</td>
			{/if}
			<th><a href="{if $file->canPreview()}{$admin_url}common/files/preview.php?id={$file.id}{else}{$file->url(true)}{/if}" target="_dialog">{$file.name}</th>
			<td>{$file.modified|date}</td>
			<td>{$file.type}</td>
			<td>{$file.size|size_in_bytes}</td>
			<td class="actions">
				{if $file->canPreview()}
					{linkbutton href="!common/files/preview.php?id=%d"|args:$file.id label="Voir" shape="eye" target="_dialog"}
				{/if}
				{linkbutton href=$file->url(true) label="Télécharger" shape="download"}
				{if $can_write && $file->getEditor()}
					{linkbutton href="!common/files/edit.php?id=%d"|args:$file.id label="Modifier" shape="edit" target="_dialog"}
				{/if}
				{linkbutton href="!docs/delete.php?id=%d"|args:$file.id label="Supprimer" shape="delete" target="_dialog"}
			</td>
		</tr>
		{/if}
	{/foreach}

	</tbody>
	{if $can_delete}
	<tfoot>
		<tr>
			<td class="check"><input type="checkbox" value="Tout cocher / décocher" id="f_all2" /><label for="f_all2"></label></td>
			<td class="actions" colspan="5">
				<em>Pour les fichiers cochés :</em>
					{csrf_field key="files"}
					<select name="action">
						<option value="">— Choisir une action à effectuer —</option>
						<option value="move">Déplacer</option>
						<option value="delete">Supprimer</option>
					</select>
					<noscript>
						{button type="submit" value="OK" shape="right" label="Valider"}
					</noscript>
				{/if}
			</td>
		</tr>
	</tfoot>
</table>

{literal}
<script type="text/javascript">
// Open preview in dialog
$('[target="_dialog"]').forEach((e) => {
	e.onclick = () => { g.openFrameDialog(e.href + '&dialog'); return false; };
});
</script>
{/literal}

{include file="admin/_foot.tpl"}