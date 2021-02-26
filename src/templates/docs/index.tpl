<?php
use Garradin\Entities\Files\File;
?>
{include file="admin/_head.tpl" title="Documents" current="docs"}

<nav class="tabs">
	<aside>
		{linkbutton shape="search" label="Rechercher" href="search.php"}
	{if $context == File::CONTEXT_DOCUMENTS}
		{linkbutton shape="plus" label="Nouveau répertoire" target="_dialog" href="!docs/new_dir.php?parent=%s"|args:$path}
		{linkbutton shape="plus" label="Nouveau fichier texte" target="_dialog" href="!docs/new_file.php?parent=%s"|args:$path}
		{linkbutton shape="upload" label="Ajouter un fichier" target="_dialog" href="!common/files/upload.php?p=%s"|args:$path}
	{/if}
	</aside>
	<ul>
		<li{if $context == File::CONTEXT_DOCUMENTS} class="current"{/if}><a href="./">Documents</a></li>
		{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
			<li{if $context == File::CONTEXT_TRANSACTION} class="current"{/if}><a href="./?p=<?=File::CONTEXT_TRANSACTION?>">Fichiers des écritures</a></li>
		{/if}
		{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)}
			<li{if $context == File::CONTEXT_USER} class="current"{/if}><a href="./?p=<?=File::CONTEXT_USER?>">Fichiers des membres</a></li>
		{/if}
	</ul>
</nav>

{if count($files)}
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
		{if $file.type == $file::TYPE_DIRECTORY}
		<tr>
			{if $can_delete}
			<td class="check">
				{input type="checkbox" name="check[]" value=$file.id}
			</td>
			{/if}
			<th><a href="?p={$file->path()}">{$file.name}</a></th>
			<td></td>
			<td>Répertoire</td>
			<td></td>
			<td class="actions">{linkbutton href="!common/files/delete.php?p=%s"|args:$file->pathname() label="Supprimer" shape="delete" target="_dialog"}</td>
		</tr>
		{else}
		</tr>
			{if $can_delete}
			<td class="check">
				{input type="checkbox" name="check[]" value=$file.id}
			</td>
			{/if}
			<th>
				{if $file->canPreview()}
					<a href="{"!common/files/preview.php?p=%s"|local_url|args:$file->pathname()}" target="_dialog" data-mime="{$file.mime}">{$file.name}</a>
				{else}
					<a href="{$file->url(true)}" target="_blank">{$file.name}</a>
				{/if}
			</th>
			<td>{$file.modified|date}</td>
			<td>{$file.mime}</td>
			<td>{$file.size|size_in_bytes}</td>
			<td class="actions">
				{if $can_write && $file->getEditor()}
					{linkbutton href="!common/files/edit.php?p=%s"|args:$file->pathname() label="Modifier" shape="edit" target="_dialog" data-dialog-height="90%"}
				{/if}
				{if $file->canPreview()}
					{linkbutton href="!common/files/preview.php?p=%s"|args:$file->pathname() label="Voir" shape="eye" target="_dialog" data-mime=$file.mime}
				{/if}
				{linkbutton href=$file->url(true) label="Télécharger" shape="download"}
				{linkbutton href="!common/files/delete.php?p=%s"|args:$file->pathname() label="Supprimer" shape="delete" target="_dialog"}
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
						{*<option value="move">Déplacer</option>*}
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
{/if}

{include file="admin/_foot.tpl"}