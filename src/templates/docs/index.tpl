<?php
use Garradin\Entities\Files\File;
?>
{include file="_head.tpl" title="Documents" current="docs" hide_title=true}

<nav class="tabs">
	<aside class="quota">
		<meter min="0" max="100" value="{$quota_percent}" high="80" style="--quota-percent: {$quota_percent}">
			<span class="text">{$quota_left|size_in_bytes} libres</span>
			<span class="more">
				{$quota_percent}% utilisé ({$quota_used|size_in_bytes}) sur {$quota_max|size_in_bytes}
			</span>
		</meter>
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

<nav class="tabs">
	<aside>
	{if $parent->canCreateDirHere() || $parent->canCreateHere()}
		{linkmenu label="Ajouter…" shape="plus"}
			{if $parent->canCreateHere()}
				{linkbutton shape="upload" label="Depuis mon ordinateur" target="_dialog" href="!common/files/upload.php?p=%s"|args:$path}
			{if $parent->canCreateDirHere()}
				{linkbutton shape="folder" label="Répertoire" target="_dialog" href="!docs/new_dir.php?path=%s"|args:$path}
			{/if}
				{linkbutton shape="text" label="Fichier texte" target="_dialog" href="!docs/new_file.php?path=%s"|args:$path}
				{if WOPI_DISCOVERY_URL}
					{linkbutton shape="document" label="Document" target="_dialog" data-dialog-class="fullscreen" href="!docs/new_doc.php?ext=odt&path=%s"|args:$path}
					{linkbutton shape="table" label="Tableur" target="_dialog" data-dialog-class="fullscreen" href="!docs/new_doc.php?ext=ods&path=%s"|args:$path}
				{/if}
			{/if}
		{/linkmenu}
	{/if}
		{linkbutton shape="search" label="Rechercher" href="search.php" target="_dialog"}
	</aside>

	<h2>
		{if $context == File::CONTEXT_TRANSACTION}
			{if $context_ref}
				Écriture #{$context_ref}
			{else}
				Fichiers joints aux écritures comptables
			{/if}
		{elseif $context == File::CONTEXT_USER}
			{if $context_ref}
				Fichiers joints à la fiche du membre&nbsp;: {$user_name}
			{else}
				Fichiers joints aux fiches des membres
			{/if}
		{elseif $context == File::CONTEXT_SKELETON}
			{if $context_ref == 'web'}
				Code du site web
			{elseif $context_ref == 'forms'}
				Code des modèles et formulaires
			{else}
				Code
			{/if}
		{elseif $context_ref}
			{$parent->name}
		{else}
			Documents
		{/if}
	</h2>
</nav>


{if $context_ref}
<nav class="breadcrumbs">
	<aside>
	{if count($breadcrumbs) > 1}
		{linkbutton href="?path=%s"|args:$parent_path label="Retour au répertoire parent" shape="left"}
	{/if}
</aside>

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
</nav>
{/if}

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
					<td class="icon">{icon shape="folder"}</td>
					<th><a href="?path={$file.path}">{$file.name}</a></th>
					<td></td>
					<td></td>
					<td class="actions">
					{if $parent->canCreateHere() || $file->canDelete()}
						{linkmenu label="Modifier…" shape="edit"}
							{if $file->canRename()}
								{linkbutton href="!common/files/rename.php?p=%s"|args:$file.path label="Renommer" shape="minus" target="_dialog"}
							{/if}
							{if $file->canDelete()}
								{linkbutton href="!common/files/delete.php?p=%s"|args:$file.path label="Supprimer" shape="delete" target="_dialog"}
							{/if}
						{/linkmenu}
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
					<td class="icon">
						{if $shape = $file->iconShape()}
							{icon shape=$shape}
						{/if}
					</td>
					<th>
						{if $file->canPreview()}
							<a href="{"!common/files/preview.php?p=%s"|local_url|args:$file.path}" target="_dialog" data-mime="{$file.mime}">{$file.name}</a>
						{elseif $file->canWrite() && $file->editorType()}
							{link href="!common/files/edit.php?p=%s"|args:$file.path label=$file.name target="_dialog" data-dialog-class="fullscreen"}
						{else}
							<a href="{$file->url(true)}" target="_blank">{$file.name}</a>
						{/if}
					</th>
					<td>{$file.modified|relative_date}</td>
					<td>{$file.size|size_in_bytes}</td>
					<td class="actions">
						{linkbutton href=$file->url(true) label="Télécharger" shape="download"}
						{if $file->canShare()}
							{linkbutton href="!common/files/share.php?p=%s"|args:$file.path label="Partager" shape="export" target="_dialog"}
						{/if}
						{if $file->canRename() || $file->canDelete() || ($file->canWrite() && $file->editorType())}
							{linkmenu label="Modifier…" shape="edit" right=true}
								{if $file->canWrite() && $file->editorType()}
									{linkbutton href="!common/files/edit.php?p=%s"|args:$file.path label="Éditer" shape="edit" target="_dialog" data-dialog-height="90%"}
								{/if}
								{if $file->canRename()}
									{linkbutton href="!common/files/rename.php?p=%s"|args:$file.path label="Renommer" shape="reload" target="_dialog"}
								{/if}
								{if $file->canDelete()}
									{linkbutton href="!common/files/delete.php?p=%s"|args:$file.path label="Supprimer" shape="delete" target="_dialog"}
								{/if}
							{/linkmenu}
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