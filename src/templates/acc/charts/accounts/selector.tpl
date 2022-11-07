{include file="_head.tpl" title="Sélectionner un compte"}

<div class="selector">

{if empty($grouped_accounts) && empty($accounts)}
	<p class="block alert">Le plan comptable ne comporte aucun compte de ce type.<br />
	</p>

	{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
		<p class="edit">{linkbutton label="Modifier les comptes" href=$edit_url shape="edit"}</p>
	{/if}

{else}

	<header>
		<h2 class="quick-search">
			<input type="text" placeholder="Recherche rapide…" title="Filtrer la liste" />{button shape="delete" type="reset" title="Effacer la recherche"}
			{* We can't use input type="search" because Firefox sucks *}
		</h2>

		{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
			<p class="edit">{linkbutton label="Modifier les comptes" href=$edit_url shape="edit"}</p>
		{/if}

		<p>{input type="select" name="filter" options=$filter_options default=$filter}</p>
	</header>

	{if isset($grouped_accounts)}
		<?php $index = 1; ?>
		{foreach from=$grouped_accounts item="group"}
			<h2 class="ruler">{$group.label}</h2>

			<table class="list">
				<tbody>
				{foreach from=$group.accounts item="account"}
					<tr data-idx="{$index}" class="account">
						<td class="bookmark">{if $account.bookmark}{icon shape="star" title="Compte favori"}{/if}</td>
						<td class="num">{$account.code}</td>
						<th>{$account.label}</th>
						<td class="desc">{$account.description}</td>
						<td class="actions">
							{button shape="right" value=$account.id data-label="%s — %s"|args:$account.code,$account.label label="Sélectionner"}
						</td>
					</tr>
					<?php $index++; ?>
				{/foreach}
				</tbody>
			</table>
		{/foreach}

	{else}

		<table class="list">
			<tbody>
			{foreach from=$accounts item="account"}
				<tr data-idx="{$iteration}" class="account account-level-{$account->level()}">
					<td class="bookmark">{if $account.bookmark}{icon shape="star" title="Compte favori"}{/if}</td>
					<td class="num">{$account.code}</td>
					<th>{$account.label}</th>
					<td class="actions">
						{button shape="right" value=$account.id data-label="%s — %s"|args:$account.code,$account.label label="Sélectionner"}
					</td>
				</tr>
			{/foreach}
			</tbody>
		</table>

	{/if}
{/if}

</div>

<script type="text/javascript" src="{$admin_url}static/scripts/selector.js?{$version_hash}"></script>

{include file="_foot.tpl"}