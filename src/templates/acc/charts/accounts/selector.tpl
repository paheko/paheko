{include file="_head.tpl" title="Sélectionner un compte"}

<div class="selector">

	<nav class="tabs">
	{if !$chart.archived && $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
		<aside>
			{linkbutton href=$new_url label="Ajouter un compte" shape="plus"}
			{linkbutton label="Modifier les comptes" href=$edit_url shape="edit"}
		</aside>
	{/if}

	{if $filter !== 'no_bookmarks'}
		<ul>
			<li{if $filter === 'bookmarks'} class="current"{/if}><a href="{$filter_bookmarks_url}">Comptes favoris et usuels</a></li>
			<li{if $filter !== 'bookmarks'} class="current"{/if}><a href="{$filter_all_url}">Tous les comptes</a></li>
		</ul>
	{/if}
	</nav>

	<header>
		<h2 class="quick-search">
			<input type="text" placeholder="Recherche rapide…" title="Filtrer la liste" />{button shape="delete" type="reset" title="Effacer la recherche"}
			{* We can't use input type="search" because Firefox sucks *}
		</h2>
	</header>
{if empty($grouped_accounts) && empty($all_accounts)}
	<p class="block alert">
		{if !empty($types_names)}
			Il n'existe aucun compte dans la catégorie «&nbsp;{$types_names}&nbsp;» dans le plan comptable.
		{else}
			Il n'existe aucun compte correspondant au critère demandé.
		{/if}
	</p>
		{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
		<p class="help">
			Il faut <a href="{$edit_url}">modifier le plan comptable</a> pour ajouter un compte de cette catégorie et pouvoir le sélectionner ensuite.
		</p>
		{/if}
	</p>

{elseif $filter === 'bookmarks'}
	<?php $index = 1; ?>
	{foreach from=$grouped_accounts item="group"}
	<section class="accounts-group">
		<h2 class="ruler">{$group.label}</h2>

		{if count($group.accounts)}
		<table class="list">
			<tbody>
			{foreach from=$group.accounts item="account"}
				<tr data-idx="{$index}" class="account" data-search-code="{$account.code|tolower}" data-search-label="{$account|make_label_searchable:'label':'description'}">
					<td class="bookmark">{if $account.bookmark}{icon shape="star" title="Compte favori"}{/if}</td>
					<td class="num">{$account.code}</td>
					<th>{$account.label}</th>
					<td class="desc">{$account.description}</td>
					<td class="actions">
						<?php $v = $account->$key; ?>
						{button shape="right" value=$v data-label="%s — %s"|args:$account.code:$account.label label="Sélectionner"}
					</td>
				</tr>
				<?php $index++; ?>
			{/foreach}
			</tbody>
		</table>
		{else}
			<p class="help">Le plan comptable ne comporte aucun compte de ce type.</p>
		{/if}
	</section>
	{/foreach}
	{if $index == 1 && $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
	<p class="help">
		Il faut <a href="{$edit_url}">modifier le plan comptable</a> pour ajouter un compte dans une catégorie et pouvoir le sélectionner ensuite.
	</p>
	{/if}


{else}

	<table class="list">
		<tbody>
		{foreach from=$all_accounts item="account"}
			<tr data-idx="{$iteration}" class="account account-level-{$account->level()}" data-search-code="{$account.code|tolower}" data-search-label="{$account|make_label_searchable:'label':'description'}">
				<td class="bookmark">{if $account.bookmark}{icon shape="star" title="Compte favori"}{/if}</td>
				<td class="num">{$account.code}</td>
				<th>{$account.label}</th>
				<td class="desc" width="25%">{$account.description}</td>
				<td class="actions">
					<?php $v = $account->$key; ?>
					{button shape="right" value=$v data-label="%s — %s"|args:$account.code:$account.label label="Sélectionner"}
				</td>
			</tr>
		{/foreach}
		</tbody>
	</table>

{/if}

<p class="alert block no-results hidden">Aucun compte ne correspond à la recherche.</p>

</div>

<script type="text/javascript" src="{$admin_url}static/scripts/selector.js?{$version_hash}"></script>

{include file="_foot.tpl"}