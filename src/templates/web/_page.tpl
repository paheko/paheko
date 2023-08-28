{if $excerpt && $page->requiresExcerpt() && !isset($_GET['full'])}
	<?php $text = $page->excerpt(); $long = true; ?>
{else}
	<?php $text = $page->render(); $long = false; ?>
{/if}


<section class="web preview{if $excerpt} excerpt{/if}">
	<header>
		<h1 class="ruler">{$page.title}</h1>
		{if $can_edit}
		<p class="actions">
			{linkbutton href="edit.php?id=%s"|args:$page.id label="Éditer" shape="edit"}
			{if $session->canAccess($session::SECTION_WEB, $session::ACCESS_ADMIN)}
				{linkbutton shape="delete" label="Supprimer" target="_dialog" href="delete.php?id=%d"|args:$page.id}
			{/if}
		</p>
		{/if}

		<p class="describe">
			<span>
				{if $page->isCategory()}
					{icon shape="folder"} Catégorie
				{else}
					{icon shape="document"} Page
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

		{if $session->canAccess($session::SECTION_WEB, $session::ACCESS_WRITE)}
		<p class="actions">
			<small>
				{if $session->canAccess($session::SECTION_WEB, $session::ACCESS_ADMIN) && !$page->hasSubPages()}
					{if !$page->isCategory()}
						{linkbutton href="?id=%d&toggle_type"|args:$page.id label="Transformer en catégorie" shape="reset"}
					{else}
						{linkbutton href="?id=%s&toggle_type"|args:$page.id label="Transformer en page simple" shape="reset"}
					{/if}
				{/if}
				{linkbutton shape="history" label="Historique" href="?id=%d&history=list"|args:$page.id}
			</small>
		</p>
		{/if}
	</header>

	{if trim($page.content)}
	<article>
		{$text|raw}
		{if $excerpt && $long}
			<p class="actions">{linkbutton href="?id=%d&full"|args:$page.id label="Lire la suite" shape="right"}</p>
		{/if}
	</article>
	{/if}

	{if !($excerpt && $long)}
		<?php $files = $page->listAttachments(); ?>

		<div class="attachments noprint">
			<h3 class="ruler">Fichiers joints</h3>

			{include file="common/files/_context_list.tpl" files=$files edit=$can_edit path=$page.dir_path use_trash=false}
		</div>
	{/if}

</section>