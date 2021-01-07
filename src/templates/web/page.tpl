{include file="admin/_head.tpl" title=$page.title current="web"}

<nav class="tabs">
	{if $page.type == $page::TYPE_CATEGORY}
	<aside>
		{linkbutton shape="plus" label="Nouvelle page" href="new.php?type=%d&parent=%d"|args:$type_page,$page.id}
		{linkbutton shape="plus" label="Nouvelle catégorie" href="new.php?type=%d&parent=%d"|args:$type_category,$page.id}
	</aside>
	{/if}
	<ul>
		<li><a href="{$admin_url}web/?parent={$page.parent_id}">Retour à la liste</a></li>
		{if $session->canAccess($session::SECTION_WEB, Membres::DROIT_ECRITURE)}
			<li><a href="{$admin_url}web/edit.php?id={$page.id}">Modifier</a></li>
		{/if}
		{if $page.status == $page::STATUS_ONLINE && !$config.desactiver_site}
			<li><a href="{$page->url()}">Voir sur le site</a></li>
		{/if}
		{if $session->canAccess($session::SECTION_WEB, Membres::DROIT_ADMIN)}
			<li><a href="{$admin_url}web/delete.php?id={$page.id}">Supprimer</a></li>
		{/if}
	</ul>
</nav>

{if !empty($breadcrumbs)}
<div class="breadCrumbs">
	<ul>
		{foreach from=$breadcrumbs key="id" item="title"}
			<li><a href="?id={$id}">{$title}</a></li>
		{/foreach}
	</ul>
</div>
{/if}

{if !$page}
	<p class="block error">
		Cette page n'existe pas.
	</p>

	{if $can_edit}
	<form method="post" action="{$admin_url}wiki/creer.php">
		<p class="submit">
			{csrf_field key="wiki_create"}
			<input type="hidden" name="titre" value="{$uri}" />
			{button type="submit" name="create" label="Créer cette page" shape="right" class="main"}
		</p>
	</form>
	{/if}
{else}

	{if !empty($children)}
	<div class="wikiChildren">
		<h4>Dans cette rubrique</h4>
		<ul>
		{foreach from=$children item="child"}
			<li><a href="?{$child.uri}">{$child.titre}</a></li>
		{/foreach}
		</ul>
	</div>
	{/if}

	{if !$content}
		<p class="block alert">Cette page est vide, cliquez sur « Modifier » pour commencer à rédiger son contenu.</p>
	{else}
		<div class="wikiContent">
			{$content|raw}
		</div>

		{if !empty($images) || !empty($files)}
		<div class="wikiFiles">
			<h3>Fichiers liés à cette page</h3>

			{if !empty($images)}
			<ul class="gallery">
				{foreach from=$images item="file"}
					<li>
						<figure>
							<a class="internal-image" href="{$file.url}"><img src="{$file.thumb}" alt="" title="{$file.nom}" /></a>
						</figure>
					</li>
				{/foreach}
			</ul>
			{/if}

			{if !empty($files)}
			<ul class="files">
				{foreach from=$files item="file"}
					<li>
						<aside class="fichier" class="internal-file"><a href="{$file.url}">{$file.nom}</a>
						<small>({$file.type}, {$file.taille|format_bytes})</small></aside>
				   </li>
				{/foreach}
			</ul>
			{/if}
		</div>
		{/if}

		<p class="wikiFooter">
			Dernière modification le {$page.modified|date_long}
			par {$auteur}
		</p>
	{/if}
{/if}


{include file="admin/_foot.tpl"}