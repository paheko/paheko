{include file="admin/_head.tpl" title="Sélectionner un compte" body_id="popup" is_popup=true}

{if empty($grouped_accounts) && empty($accounts)}
	<p class="block alert">Le plan comptable ne comporte aucun compte de ce type. Pour afficher des comptes ici, les <a href="{$www_url}admin/acc/charts/accounts/all.php?id={$chart.id}" target="_blank">modifier dans le plan comptable</a> en sélectionnant le type de compte favori voulu.</td>

{elseif isset($grouped_accounts)}

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

	<h2 class="ruler">
		<input type="text" placeholder="Recherche rapide" id="lookup" />
		<label>{input type="checkbox" name="typed_only" value=0 default=0 default=$all} N'afficher que les comptes favoris</label>
	</h2>

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

<script type="text/javascript" src="{$admin_url}static/scripts/selector.js?{$version_hash}"></script>

{include file="admin/_foot.tpl"}