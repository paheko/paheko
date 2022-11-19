<?php
use Garradin\Entities\Files\File;
?>
{include file="_head.tpl" title="Documents" current="docs"}

<nav class="tabs">
	<aside>
		{linkbutton shape="search" label="Rechercher" href="search.php" target="_dialog"}
	{if $parent->canCreateDirHere()}
		{linkbutton shape="plus" label="Nouveau répertoire" target="_dialog" href="!docs/new_dir.php?path=%s"|args:$path}
	{/if}
	{if $parent->canCreateHere()}
		{linkbutton shape="plus" label="Nouveau fichier texte" target="_dialog" href="!docs/new_file.php?path=%s"|args:$path}
		{linkbutton shape="upload" label="Ajouter un fichier" target="_dialog" href="!common/files/upload.php?p=%s"|args:$path}
	{/if}
	</aside>
	<ul>
		<li{if $context == File::CONTEXT_DOCUMENTS} class="current"{/if}><a href="./">Documents</a></li>
		{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ)}
			<li{if $context == File::CONTEXT_TRANSACTION} class="current"{/if}><a href="./?path=<?=File::CONTEXT_TRANSACTION?>">Fichiers des écritures</a></li>
		{/if}
		{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_READ)}
			<li{if $context == File::CONTEXT_USER} class="current"{/if}><a href="./?path=<?=File::CONTEXT_USER?>">Fichiers des membres</a></li>
		{/if}
		{if $session->canAccess($session::SECTION_WEB, $session::ACCESS_ADMIN)}
			<li{if $context == File::CONTEXT_SKELETON} class="current"{/if}><a href="./?path=<?=File::CONTEXT_SKELETON?>">Squelettes du site web</a></li>
		{/if}
	</ul>

</nav>

<nav class="breadcrumbs">
	{if count($breadcrumbs) > 1}
		{linkbutton href="?path=%s"|args:$parent_path label="Retour au répertoire parent" shape="left"}
	{/if}

{if $context == File::CONTEXT_TRANSACTION}
	{if $context_ref}
		{linkbutton href="!acc/transactions/details.php?id=%d"|args:$context_ref|local_url label="Détails de l'écriture" shape="menu"}
	{/if}
{elseif $context == File::CONTEXT_USER}
	{if $context_ref}
		{linkbutton href="!users/details.php?id=%d"|args:$context_ref|local_url label="Fiche du membre" shape="user"}
	{/if}
{else}
	<ul>
	{foreach from=$breadcrumbs item="name" key="bc_path"}
		<li><a href="?path={$bc_path}">{$name}</a></li>
	{/foreach}
	</ul>
{/if}

	<aside class="quota">
		<h4><b>{$quota_left|size_in_bytes}</b> libres sur <i>{$quota_max|size_in_bytes}</i></h4>
		<span class="meter"><span style="width: {$quota_percent}%"></span></span>
	</aside>
</nav>

{if !$parent->canCreateDirHere()}
<p class="block alert">
	Il n'est pas possible de créer de répertoire ici.
	{if $context == File::CONTEXT_USER}
		Utiliser le <a href="{"!users/new.php"|local_url}">formulaire de création</a> pour enregistrer un membre.
	{else}
		Utiliser le <a href="{"!acc/transactions/new.php"|local_url}">formulaire de saisie</a> pour créer une nouvelle écriture.
	{/if}
</p>
{/if}

{if count($list)}
<form method="post" action="{"!docs/action.php"|local_url}" target="_dialog">

	{if is_array($list)}

		<table class="list">
			<thead>
				<tr>
					<td class="check"><input type="checkbox" value="Tout cocher / décocher" id="f_all" /><label for="f_all"></label></td>
					<td></td>
					<th>Nom</th>
					<td>Modifié</td>
					<td>Taille</td>
					<td></td>
				</tr>
			</thead>

			<tbody>

			{foreach from=$list item="file"}
				{if $file->isDir()}
				<tr class="folder">
					{if $file->canDelete()}
					<td class="check">
						{input type="checkbox" name="check[]" value=$file.path}
					</td>
					{/if}
					<td class="file-icon">{icon shape="folder"}</td>
					<th><a href="?path={$file.path}">{$file.name}</a></th>
					<td></td>
					<td></td>
					<td class="actions">
					{if $parent->canCreateHere()}
						{linkbutton href="!common/files/rename.php?p=%s"|args:$file.path label="Renommer" shape="minus" target="_dialog"}
					{/if}
					{if $file->canDelete()}
						{linkbutton href="!common/files/delete.php?p=%s"|args:$file.path label="Supprimer" shape="delete" target="_dialog"}
					{/if}
					</td>
				</tr>
				{else}
				</tr>
					{if $file->canDelete()}
					<td class="check">
						{input type="checkbox" name="check[]" value=$file.path}
					</td>
					{/if}
					<td class="icon">{if $file->isImage()}{icon shape="image"}{/if}</td>
					<th>
						{if $file->canPreview()}
							<a href="{"!common/files/preview.php?p=%s"|local_url|args:$file.path}" target="_dialog" data-mime="{$file.mime}">{$file.name}</a>
						{else}
							<a href="{$file->url(true)}" target="_blank">{$file.name}</a>
						{/if}
					</th>
					<td>{$file.modified|relative_date}</td>
					<td>{$file.size|size_in_bytes}</td>
					<td class="actions">
						{if $file->canWrite() && $file->editorType()}
							{linkbutton href="!common/files/edit.php?p=%s"|args:$file.path label="Modifier" shape="edit" target="_dialog" data-dialog-height="90%"}
						{/if}
						{if $file->canPreview()}
							{linkbutton href="!common/files/preview.php?p=%s"|args:$file.path label="Voir" shape="eye" target="_dialog" data-mime=$file.mime}
						{/if}
						{linkbutton href=$file->url(true) label="Télécharger" shape="download"}
						{if $parent->canCreateHere()}
							{linkbutton href="!common/files/rename.php?p=%s"|args:$file.path label="Renommer" shape="reload" target="_dialog"}
						{/if}
						{if $file->canDelete()}
							{linkbutton href="!common/files/delete.php?p=%s"|args:$file.path label="Supprimer" shape="delete" target="_dialog"}
						{/if}
						{if $file->canShare()}
							{linkbutton href="!common/files/share.php?p=%s"|args:$file.path label="Partager" shape="export" target="_dialog"}
						{/if}
					</td>
				</tr>
				{/if}
			{/foreach}

			</tbody>

			{if $file->canDelete()}
			<tfoot>
				<tr>
					<td class="check"><input type="checkbox" value="Tout cocher / décocher" id="f_all2" /><label for="f_all2"></label></td>
					<td class="actions" colspan="6">
						<em>Pour les fichiers sélectionnés&nbsp;:</em>
							<input type="hidden" name="parent" value="{$path}" />
							<select name="action">
								<option value="">— Choisir une action à effectuer —</option>
								{if $context == File::CONTEXT_DOCUMENTS}
								<option value="move">Déplacer</option>
								{/if}
								<option value="delete">Supprimer</option>
							</select>
							<noscript>
								{button type="submit" value="OK" shape="right" label="Valider"}
							</noscript>
					</td>
				</tr>
			</tfoot>
			{/if}
		</table>
	{elseif $list instanceof \Garradin\DynamicList}

		{include file="common/dynamic_list_head.tpl" check=false}


		{foreach from=$list->iterate() item="item"}
			<tr>
				{if $context == File::CONTEXT_TRANSACTION}
					<td class="num"><a href="{$admin_url}acc/transactions/details.php?id={$item.id}">#{$item.id}</a></td>
					<th><a href="?path={$item.path}">{$item.label}</a></th>
					<td>{$item.date|date_short}</td>
					<td>{$item.reference}</td>
					<td>{$item.year}</td>
					<td class="actions">
						{linkbutton href="!docs/?path=%s"|args:$item.path label="Fichiers" shape="menu"}
						{linkbutton href="!acc/transactions/details.php?id=%d"|args:$item.id label="Écriture" shape="search"}
					</td>
				{else}
					<td class="num"><a href="{$admin_url}users/details.php?id={$item.id}">#{$item.number}</a></td>
					<th><a href="?path={$item.path}">{$item.identity}</a></th>
					<td class="actions">
						{linkbutton href="!docs/?path=%s"|args:$item.path label="Fichiers" shape="menu"}
						{linkbutton href="!users/details.php?id=%d"|args:$item.id label="Fiche membre" shape="user"}
					</td>
				{/if}
			</tr>
		{/foreach}
		</tbody>
		</table>

		{pagination url=$list->paginationURL() page=$list.page bypage=$list.per_page total=$list->count()}

	{/if}

	<p class="actions">
		{linkbutton href="!docs/zip.php?path=%s"|args:$path label="Télécharger ce répertoire (ZIP)" shape="download"}
	</p>

</form>
{else}
	<p class="alert block">Il n'y a aucun fichier dans ce répertoire.</p>
{/if}

{include file="_foot.tpl"}