{if $excerpt && $page->requiresExcerpt() && !isset($_GET['full'])}
	<?php $text = $page->excerpt(); $long = true; ?>
{else}
	<?php $text = $page->render(ADMIN_URL . 'web/?uri='); $long = false; ?>
{/if}


<section class="web preview{if $excerpt} excerpt{/if}">
	<header>
		<h1 class="ruler">{$page.title}</h1>
		{if $session->canAccess($session::SECTION_WEB, $session::ACCESS_WRITE)}
		<p class="actions">
			{linkbutton href="edit.php?p=%s"|args:$page.path label="Éditer" shape="edit"}
			{if $session->canAccess($session::SECTION_WEB, $session::ACCESS_ADMIN)}
				{linkbutton shape="delete" label="Supprimer" target="_dialog" href="delete.php?p=%s"|args:$page.path}
				<br /><small>
					{if !$page->isCategory()}
						{linkbutton href="?p=%s&toggle_type"|args:$page.path label="Transformer en catégorie" shape="reset"}
					{else}
						{linkbutton href="?p=%s&toggle_type"|args:$page.path label="Transformer en page simple" shape="reset"}
					{/if}
					</small>
			{/if}
		</p>
		{/if}

		<p class="describe">
			<span>
				{if $page->isCategory()}
					Catégorie
				{else}
					Page
				{/if}
			</span>
			{if $page.status == $page::STATUS_ONLINE}
				<strong>{icon shape="eye"} En ligne</strong>
			{else}
				<em>{icon shape="eye-off"} Brouillon</em>
			{/if}
			<span>Publié&nbsp;: {$page.published|relative_date:true}</span>
			<span>Modifié&nbsp;: {$page.modified|relative_date:true}</span>
			{if $page->isOnline()}
			<br /><tt>{link href=$page->url() label=$page->url() target="_blank"}</tt>
			{/if}
		</p>
	</header>

	{if $page.content}
	<article>
		{$text|raw}
		{if $excerpt && $long}
			<p class="actions">{linkbutton href="?p=%s&full"|args:$page.path label="Lire la suite" shape="image"}</p>
		{/if}
	</article>
	{/if}

	{assign var="images" value=$page->getImageGallery(true)}
	{assign var="files" value=$page->getAttachmentsGallery(true)}

	{if count($images) || count($files)}
	<div class="web-files">
		<h3>Fichiers liés à cette page</h3>

		{if count($images)}
		<ul class="gallery">
			{foreach from=$images item="file"}
				<li>
					<figure>
						<a class="internal-image" href="{$file->url()}"><img src="{$file->thumb_url()}" alt="" title="{$file.name}" /></a>
					</figure>
				</li>
			{/foreach}
		</ul>
		{/if}

		{if count($files)}
		<ul class="files">
			{foreach from=$files item="file"}
				<li>
					<aside class="fichier" class="internal-file"><a href="{$file->url()}">{$file.name}</a>
					<small>({$file.mime}, {$file.size|size_in_bytes})</small></aside>
			   </li>
			{/foreach}
		</ul>
		{/if}
	</div>
	{/if}

</section>