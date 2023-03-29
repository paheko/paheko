<?php
use Garradin\Entities\Files\File;
?>
{include file="_head.tpl" title="Documents" current="docs" hide_title=true}

<nav class="tabs">
	<aside class="quota">
		{* We cannot use <meter> here as Firefox sucks :( *}
		<span class="meter" style="--quota-percent: {$quota_percent}">
			<span class="text">{$quota_left|size_in_bytes} libres</span>
			<span class="more">
				{$quota_percent}% utilisé ({$quota_used|size_in_bytes}) sur {$quota_max|size_in_bytes}
			</span>
		</span>
	</aside>
	{include file="./_nav.tpl"}
</nav>

<nav class="tabs">
	<aside>
	{if $parent->canCreateDirHere() || $parent->canCreateHere()}
		{if $gallery}
			{linkbutton shape="menu" label="Afficher en liste" href="?path=%s&gallery=0"|args:$parent_path_uri}
		{else}
			{linkbutton shape="gallery" label="Afficher en galerie" href="?path=%s&gallery=1"|args:$parent_path_uri}
		{/if}
		{linkbutton shape="search" label="Rechercher" href="search.php" target="_dialog"}
		{linkmenu label="Ajouter…" shape="plus" right=true}
			{if $parent->canCreateHere()}
				{linkbutton shape="upload" label="Depuis mon ordinateur" target="_dialog" href="!common/files/upload.php?p=%s"|args:$parent_path_uri}
			{if $parent->canCreateDirHere()}
				{linkbutton shape="folder" label="Répertoire" target="_dialog" href="!docs/new_dir.php?path=%s"|args:$parent_path_uri}
			{/if}
				{linkbutton shape="text" label="Fichier texte" target="_dialog" href="!docs/new_file.php?path=%s"|args:$parent_path_uri}
				{if WOPI_DISCOVERY_URL}
					{linkbutton shape="document" label="Document" target="_dialog" data-dialog-class="fullscreen" href="!docs/new_doc.php?ext=odt&path=%s"|args:$parent_path_uri}
					{linkbutton shape="table" label="Tableur" target="_dialog" data-dialog-class="fullscreen" href="!docs/new_doc.php?ext=ods&path=%s"|args:$parent_path_uri}
					{linkbutton shape="gallery" label="Présentation" target="_dialog" data-dialog-class="fullscreen" href="!docs/new_doc.php?ext=odp&path=%s"|args:$parent_path_uri}
				{/if}
			{/if}
		{/linkmenu}
	{/if}
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
			{elseif $context_ref == 'modules'}
				Code des modules
			{else}
				Code
			{/if}
		{elseif $context == File::CONTEXT_MODULES}
			Code des modules
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

{if $session->canAccess($session::SECTION_DOCUMENTS, $session::ACCESS_WRITE) && !$parent->canCreateDirHere()}
<p class="block alert">
	Il n'est pas possible de créer de répertoire ici.
	{if $context == File::CONTEXT_USER}
		Utiliser le <a href="{"!users/new.php"|local_url}">formulaire de création</a> pour enregistrer un membre.
	{elseif $context == File::CONTEXT_TRANSACTION}
		Utiliser le <a href="{"!acc/transactions/new.php"|local_url}">formulaire de saisie</a> pour créer une nouvelle écriture.
	{/if}
</p>
{/if}

{if count($list)}
<form method="post" action="{"!docs/action.php"|local_url}" target="_dialog">

	{if is_array($list)}

		<table class="list files{if $gallery} gallery{/if}">
			<thead>
				<tr>
					{if $session->canAccess($session::SECTION_DOCUMENTS, $session::ACCESS_WRITE)}
					<td class="check"><input type="checkbox" value="Tout cocher / décocher" id="f_all" /><label for="f_all"></label></td>
					{/if}
					<td></td>
					<th>Nom</th>
					<td class="size">Taille</td>
					<td class="date">Modifié</td>
					<td></td>
				</tr>
			</thead>

			<tbody>

			{foreach from=$list item="file"}
				{if $file->isDir()}
				<tr class="folder">
					{if $file->canDelete()}
					<td class="check">
						{input type="checkbox" name="check[]" value=$file->path}
					</td>
					{/if}
					<td class="icon"><a href="?path={$file->path_uri()}">{icon shape="folder"}</a></td>
					<th colspan="3"><a href="?path={$file->path_uri()}">{$file.name}</a></th>
					<td class="actions">
					{if $parent->canCreateHere() || $file->canDelete()}
						{linkmenu label="Modifier…" shape="edit"}
							{if $file->canRename()}
								{linkbutton href="!common/files/rename.php?p=%s"|args:$file->path_uri() label="Renommer" shape="minus" target="_dialog"}
							{/if}
							{if $file->canDelete()}
								{linkbutton href="!common/files/delete.php?p=%s"|args:$file->path_uri() label="Supprimer" shape="trash" target="_dialog"}
							{/if}
						{/linkmenu}
					{/if}
					</td>
				</tr>
				{else}
				<tr>
				{if $file->canDelete()}
					<td class="check">
						{input type="checkbox" name="check[]" value=$file->path}
					</td>
				{/if}
				{if $gallery && $file->isImage()}
					<td class="preview">{$file->link($session, '150px', false)|raw}</td>
				{else}
					<td class="icon">
						{$file->link($session, 'icon', false)|raw}
					</td>
				{/if}
					<th>
						{$file->link($session, null, false)|raw}
					</th>
					<td class="size">{$file.size|size_in_bytes}</td>
					<td class="date">{$file.modified|relative_date_short:true}</td>
					<td class="actions">
						{linkbutton href=$file->url(true) label="Télécharger" shape="download" title="Télécharger"}
						{if $file->canShare()}
							{linkbutton href="!common/files/share.php?p=%s"|args:$file->path_uri() label="Partager" shape="export" target="_dialog" title="Partager"}
						{/if}
						{if $file->canRename() || $file->canDelete() || ($file->canWrite() && $file->editorType())}
							{linkmenu label="Modifier…" shape="edit" right=true}
								{if $file->canWrite() && $file->editorType()}
									{linkbutton href="!common/files/edit.php?p=%s"|args:$file->path_uri() label="Éditer" shape="edit" target="_dialog" data-dialog-class="fullscreen"}
								{/if}
								{if $file->canRename()}
									{linkbutton href="!common/files/rename.php?p=%s"|args:$file->path_uri() label="Renommer" shape="reload" target="_dialog"}
								{/if}
								{if $file->canDelete()}
									{linkbutton href="!common/files/delete.php?p=%s"|args:$file->path_uri() label="Supprimer" shape="trash" target="_dialog"}
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
							<input type="hidden" name="parent" value="{$parent_path_uri}" />
							<select name="action">
								<option value="">— Choisir une action à effectuer —</option>
								{if $context == File::CONTEXT_DOCUMENTS}
								<option value="move">Déplacer</option>
								{/if}
								<option value="delete">Supprimer</option>
								<option value="zip">Télécharger dans un fichier ZIP</option>
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

		{if $list->count()}

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

			{$list->getHTMLPagination()|raw}

		{else}

			<p class="alert block">Aucun fichier n'existe ici.</p>

		{/if}


	{/if}

</form>
{else}
	<p class="alert block">Il n'y a aucun fichier dans ce répertoire.</p>
{/if}

{include file="_foot.tpl"}