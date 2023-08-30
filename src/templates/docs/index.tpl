<?php
use Paheko\Entities\Files\File;
?>
{include file="_head.tpl" title="Documents" current="docs" hide_title=true}

<nav class="tabs">
	{if $session->canAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN)}
		{size_meter
			tag="aside"
			total=$quota.max
			value=$quota.used
			text="%s libres"|args:$quota.left_bytes
			more="%s%% utilisé (%s sur %s)"|args:$quota.percent:$quota.used_bytes:$quota.max_bytes
			href="!config/disk_usage.php"
			title="Cliquer pour les détails de l'espace disque"}
	{else}
		{size_meter
			tag="aside"
			total=$quota.max
			value=$quota.used
			text="%s libres"|args:$quota.left_bytes
			more="%s%% utilisé (%s sur %s)"|args:$quota.percent:$quota.used_bytes:$quota.max_bytes}
	{/if}
	{include file="./_nav.tpl"}
</nav>

<nav class="tabs">
	<aside>
		<form method="post" action="search.php" target="_dialog" data-disable-progress="1">
			{input type="text" name="q" size=25 placeholder="Rechercher un document" title="Rechercher dans les documents"}
			{button shape="search" type="submit" title="Rechercher"}
		</form>
	{if !$context_specific_root}
		{if $gallery}
			{linkbutton shape="menu" label="Afficher en liste" href="?path=%s&gallery=0"|args:$dir_uri}
		{else}
			{linkbutton shape="gallery" label="Afficher en galerie" href="?path=%s&gallery=1"|args:$dir_uri}
		{/if}
	{/if}
	{if $dir->canCreateDirHere() || $dir->canCreateHere()}
		{linkmenu label="Ajouter…" shape="plus" right=true}
			{if $dir->canCreateHere()}
				{linkbutton shape="upload" label="Depuis mon ordinateur" target="_dialog" href="!common/files/upload.php?p=%s"|args:$dir_uri}
			{if $dir->canCreateDirHere()}
				{linkbutton shape="folder" label="Dossier" target="_dialog" href="!docs/new_dir.php?path=%s"|args:$dir_uri}
			{/if}
				{linkbutton shape="text" label="Fichier texte" target="_dialog" href="!docs/new_file.php?path=%s"|args:$dir_uri}
				{if WOPI_DISCOVERY_URL}
					{linkbutton shape="document" label="Document" target="_dialog" href="!docs/new_doc.php?ext=odt&path=%s"|args:$dir_uri}
					{linkbutton shape="table" label="Tableur" target="_dialog" href="!docs/new_doc.php?ext=ods&path=%s"|args:$dir_uri}
					{linkbutton shape="gallery" label="Présentation" target="_dialog" href="!docs/new_doc.php?ext=odp&path=%s"|args:$dir_uri}
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
		{elseif $parent_uri}
			{$dir->name}
		{else}
			Documents
		{/if}
	</h2>
</nav>


{if $parent_uri}
	<nav class="breadcrumbs">
	{if $context_ref}
		{linkbutton href="?path=%s"|args:$parent_uri label="Retour au dossier parent" shape="left"}
		{if $context == File::CONTEXT_TRANSACTION}
			{linkbutton href="!acc/transactions/details.php?id=%d"|args:$context_ref|local_url label="Détails de l'écriture" shape="menu"}
		{elseif $context == File::CONTEXT_USER}
			{linkbutton href="!users/details.php?id=%d"|args:$context_ref|local_url label="Fiche du membre" shape="user"}
		{/if}
	{else}
		<ul>
		{foreach from=$breadcrumbs item="name" key="bc_path"}
			<li><a href="?path={$bc_path}">{$name}</a></li>
		{/foreach}
		</ul>
		{if count($breadcrumbs) > 1}
			{linkbutton href="?path=%s"|args:$parent_uri label="Retour au dossier parent" shape="left"}
		{/if}
	{/if}
	</nav>
{/if}

{if $list->count()}
	<form method="post" action="{"!docs/action.php"|local_url}" target="_dialog">

		<?php
		$class = $gallery && !$context_specific_root ? 'files gallery' : 'files';

		if ($context_specific_root) {
			$can_check = false;
		}
		elseif ($context === File::CONTEXT_USER || $context === File::CONTEXT_TRANSACTION) {
			$can_check = $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN);
		}
		else {
			$can_check = $session->canAccess($session::SECTION_DOCUMENTS, $session::ACCESS_WRITE);
		}
		?>

		{include file="common/dynamic_list_head.tpl" check=$can_check class=$class}

		{foreach from=$list->iterate() item="item"}
			{if !$context_ref && $context === File::CONTEXT_TRANSACTION}
			<tr>
				{if $can_check}
					<td class="check">
						{input type="checkbox" name="check[]" value=$item.path}
					</td>
				{/if}
				<td class="num"><a href="{$admin_url}acc/transactions/details.php?id={$item.id}">#{$item.id}</a></td>
				<th><a href="?path={$item.path}">{$item.label}</a></th>
				<td>{$item.date|date_short}</td>
				<td>{$item.reference}</td>
				<td>{$item.year}</td>
				<td class="actions">
					{linkbutton href="!docs/?path=%s"|args:$item.path label="Fichiers" shape="menu"}
					{linkbutton href="!acc/transactions/details.php?id=%d"|args:$item.id label="Écriture" shape="search"}
				</td>
			</tr>
			{elseif !$context_ref && $context === File::CONTEXT_USER}
			<tr>
				{if $can_check}
					<td class="check">
						{input type="checkbox" name="check[]" value=$item.path}
					</td>
				{/if}
				<td class="num"><a href="{$admin_url}users/details.php?id={$item.id}">{$item.number}</a></td>
				<th><a href="?path={$item.path}">{$item.identity}</a></th>
				<td class="actions">
					{linkbutton href="!docs/?path=%s"|args:$item.path label="Fichiers" shape="menu"}
					{linkbutton href="!users/details.php?id=%d"|args:$item.id label="Fiche membre" shape="user"}
				</td>
			</tr>
			{else}
				{if $item->isDir()}
					<tr class="folder">
						{if $can_check && $item->canDelete()}
							<td class="check">
								{input type="checkbox" name="check[]" value=$item.path}
							</td>
						{/if}
						<td class="icon"><a href="?path={$item->path_uri()}">{icon shape="folder"}</a></td>
						<th colspan="3"><a href="?path={$item->path_uri()}">{$item.name}</a></th>
						<td class="actions">
						{if $dir->canCreateHere() || $item->canDelete()}
							{linkmenu label="Modifier…" shape="edit"}
								{if $item->canRename()}
									{linkbutton href="!common/files/rename.php?p=%s"|args:$item->path_uri() label="Renommer" shape="minus" target="_dialog"}
								{/if}
								{if $item->canDelete()}
									{linkbutton href="!common/files/delete.php?p=%s"|args:$item->path_uri() label="Supprimer" shape="trash" target="_dialog"}
								{/if}
							{/linkmenu}
						{/if}
						</td>
					</tr>
				{else}
					<tr{if $highlight == $item.name} class="highlight"{/if}>
					{if $item->canDelete()}
						<td class="check">
							{input type="checkbox" name="check[]" value=$item.path}
						</td>
					{/if}
					{if $gallery && $item->hasThumbnail()}
						<td class="preview">{$item->link($session, '150px', false)|raw}</td>
					{else}
						<td class="icon">
							{$item->link($session, 'icon', false)|raw}
						</td>
					{/if}
						<th>
							{$item->link($session, null, false)|raw}
						</th>
						<td class="size">{$item.size|size_in_bytes}</td>
						<td class="date">{$item.modified|relative_date_short:true}</td>
						<td class="actions">
							{linkbutton href=$item->url(true) label="Télécharger" shape="download" title="Télécharger"}
							{if $item->canShare()}
								{linkbutton href="!common/files/share.php?p=%s"|args:$item->path_uri() label="Partager" shape="export" target="_dialog" title="Partager"}
							{/if}
							{if $item->canRename() || $item->canDelete() || ($item->canWrite() && $item->editorType())}
								{linkmenu label="Modifier…" shape="edit" right=true}
									{assign var="can_write" value=$item->canWrite()}
									{if $can_write && $item->editorType()}
										{linkbutton href="!common/files/edit.php?p=%s"|args:$item->path_uri() label="Éditer" shape="edit" target="_dialog" data-dialog-class="fullscreen"}
									{/if}
									{if $item->canRename()}
										{linkbutton href="!common/files/rename.php?p=%s"|args:$item->path_uri() label="Renommer" shape="reload" target="_dialog"}
									{/if}
									{if $item->canDelete()}
										{linkbutton href="!common/files/delete.php?p=%s"|args:$item->path_uri() label="Supprimer" shape="trash" target="_dialog"}
									{/if}
									{if !(FILE_VERSIONING_POLICY === 'none' || $config.file_versioning_policy === 'none') && $can_write}
										{linkbutton shape="history" href="!common/files/history.php?p=%s"|args:$item->path_uri() label="Historique" target="_dialog"}
									{/if}
								{/linkmenu}
							{/if}
						</td>
					</tr>
				{/if}
			{/if}
		{/foreach}

		</tbody>

		{if $can_check}
		<tfoot>
			<tr>
				<td class="check"><input type="checkbox" title="Tout cocher / décocher" id="f_all2" /><label title="Tout cocher / décocher" for="f_all2"></label></td>
				<td class="actions" colspan="6">
					<em>Pour les fichiers sélectionnés&nbsp;:</em>
					<input type="hidden" name="parent" value="{$dir.path}" />
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

	{$list->getHTMLPagination()|raw}
</form>

{else}
	<p class="alert block">Il n'y a aucun fichier dans ce dossier.</p>
{/if}

{if $dir->path == $dir->context()}
<div class="help flex">
	<p>
		Adresse WebDAV&nbsp;:
		{copy_button label=$dir->webdav_root_url()}
	</p>
	<p>
		{linkbutton shape="help" href=HELP_PATTERN_URL|args:"webdav" label="Accéder aux documents avec WebDAV" target="_dialog"}
	</p>
</div>
{/if}

{include file="_foot.tpl"}