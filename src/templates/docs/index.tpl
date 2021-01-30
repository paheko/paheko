<?php
use Garradin\Entities\Files\File;
?>
{include file="admin/_head.tpl" title="Documents" current="docs"}

<nav class="tabs">
	<aside>
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
			<th>{$file.name}</th>
			<td>{$file.modified|date}</td>
			<td>{$file.type}</td>
			<td>{$file.size|size_in_bytes}</td>
			<td class="actions">
				{linkbutton href="!docs/preview.php?id=%d"|args:$file.id label="Ouvrir" shape="eye"}
				{linkbutton href="!docs/download.php?id=%d"|args:$file.id label="Télécharger" shape="download"}
				{linkbutton href="!docs/delete.php?id=%d"|args:$file.id label="Supprimer" shape="delete"}
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

{include file="admin/_foot.tpl"}