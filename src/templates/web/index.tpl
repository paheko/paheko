{include file="admin/_head.tpl" title=$title current="web"}

<nav class="tabs">
	<aside>
		{linkbutton shape="plus" label="Nouvelle page" href="new.php?type=%d&parent=%d"|args:$type_page,$parent}
		{linkbutton shape="plus" label="Nouvelle catégorie" href="new.php?type=%d&parent=%d"|args:$type_category,$parent}
	</aside>
	<ul>
		<li class="current"><a href="./">Gestion du site web</a></li>
		{if $session->canAccess($session::SECTION_WEB, Membres::DROIT_ADMIN)}
			{*<li><a href="theme.php">Thèmes</a></li>*}
			<li><a href="config.php">Configuration</a></li>
		{/if}
	</ul>
</nav>

{if count($categories)}
	<h2 class="ruler">Catégories</h2>
	<table class="list">
		<tbody>
			{foreach from=$categories item="p"}
			<tr>
				<th><a href="?parent={$p.id}">{$p.title}</a></th>
				<td>{if $p.status == $p::STATUS_ONLINE}En ligne{else}<em>Brouillon</em>{/if}</td>
				<td class="actions">
					{if $p.status == $p::STATUS_ONLINE && !$config.desactiver_site}
						{linkbutton shape="eye" label="Voir sur le site" href=$p->url() target="_blank"}
					{/if}
					{linkbutton shape="menu" label="Sous-catégories et pages" href="?parent=%d"|args:$p.id}
					{linkbutton shape="image" label="Prévisualiser" href="page.php?id=%d"|args:$p.id}
					{linkbutton shape="edit" label="Modifier" href="edit.php?id=%d"|args:$p.id}
					{linkbutton shape="delete" label="Supprimer" href="delete.php?id=%d"|args:$p.id}
				</td>
			</tr>
			{/foreach}
		</tbody>
	</table>
{else}
	<p class="submit">
		{linkbutton class="main" shape="plus" label="Nouvelle catégorie" href="new.php?type=%d&parent=%d"|args:$type_category,$parent}
	</p>
{/if}

{if count($pages)}
	<h2 class="ruler">Pages</h2>
	<p>
		{if !$order_date}
			{linkbutton shape="down" label="Trier par date" href="?parent=%d"|args:$parent}
		{else}
			{linkbutton shape="up" label="Trier par titre" href="?parent=%d&order_title"|args:$parent}
		{/if}
	</p>
	<table class="list">
		<tbody>
			{foreach from=$pages item="p"}
			<tr>
				<th>{$p.title}</th>
				<td>{if $p.status == $p::STATUS_ONLINE}En ligne{else}<em>Brouillon</em>{/if}</td>
				<td>{$p->created()|date_short}</td>
				<td>Modifié {$p.modified|relative_date:true}</td>
				<td class="actions">
					{if $p.status == $p::STATUS_ONLINE}
						{linkbutton shape="eye" label="Voir sur le site" href=$p->url() target="_blank"}
					{/if}
					{linkbutton shape="image" label="Prévisualiser" href="page.php?id=%d"|args:$p.id}
					{linkbutton shape="edit" label="Modifier" href="edit.php?id=%d"|args:$p.id}
					{linkbutton shape="delete" label="Supprimer" href="delete.php?id=%d"|args:$p.id}
				</td>
			</tr>
			{/foreach}
		</tbody>
	</table>
{else}
	<p class="submit">
		{linkbutton shape="plus" class="main" label="Nouvelle page" href="new.php?type=%d&parent=%d"|args:$type_page,$parent}
	</p>
{/if}


{include file="admin/_foot.tpl"}