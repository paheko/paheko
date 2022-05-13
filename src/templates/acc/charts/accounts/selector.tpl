{include file="admin/_head.tpl" title="Sélectionner un compte"}

{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
	<?php $page = isset($grouped_accounts) ? '' : 'all.php'; ?>
	<nav class="tabs">
		<aside>{linkbutton label="Modifier les comptes" href="!acc/charts/accounts/%s?id=%d"|args:$page,$chart.id shape="edit"}</aside>
	</nav>
{/if}

{if empty($grouped_accounts) && empty($accounts)}
	<p class="block alert">Le plan comptable ne comporte aucun compte de ce type.<br />
		{linkbutton href="!acc/charts/accounts/new.php?id=%s&type=%s"|args:$chart.id,$targets[0] label="Créer un compte" shape="plus"}
	</p>

{else}

	<h2 class="ruler">
		<input type="text" placeholder="Recherche rapide" id="lookup" />
		{if !isset($grouped_accounts)}
		<label>{input type="checkbox" name="typed_only" value=0 default=0 default=$all} N'afficher que les comptes favoris</label>
		{/if}
	</h2>


	{if isset($grouped_accounts)}
		<?php $index = 1; ?>
		{foreach from=$grouped_accounts item="group"}
			<h2 class="ruler">{$group.label}</h2>

			<table class="list">
				<tbody>
				{foreach from=$group.accounts item="account"}
					<tr data-idx="{$index}">
						<td>{$account.code}</td>
						<th>{$account.label}</th>
						<td class="desc">{$account.description}</td>
						<td class="actions">
							<button class="icn-btn" value="{$account.id}" data-label="{$account.code} — {$account.label}" data-icon="&rarr;">Sélectionner</button>
						</td>
					</tr>
					<?php $index++; ?>
				{/foreach}
				</tbody>
			</table>
		{/foreach}

	{else}

		<table class="accounts">
			<tbody>
			{foreach from=$accounts item="account"}
				<tr data-idx="{$iteration}" class="account-level-{$account.code|strlen}">
					<td>{$account.code}</td>
					<th>{$account.label}</th>
					<td>
					{if $account.type}
						{icon shape="star"} <?=Entities\Accounting\Account::TYPES_NAMES[$account->type]?>
					{/if}
					</td>
					<td class="actions">
						<button class="icn-btn" value="{$account.id}" data-label="{$account.code} — {$account.label}" data-icon="&rarr;">Sélectionner</button>
					</td>
				</tr>
			{/foreach}
			</tbody>
		</table>

	{/if}
{/if}

<script type="text/javascript" src="{$admin_url}static/scripts/selector.js?{$version_hash}"></script>

{include file="admin/_foot.tpl"}