{include file="admin/_head.tpl" title="Comptes favoris" current="acc/accounts"}

{include file="acc/_year_select.tpl"}

<nav class="tabs">
	<ul>
		<li class="current"><a href="{$admin_url}acc/accounts/">Comptes favoris</a></li>
		<li><a href="{$admin_url}acc/reports/trial_balance.php?year={$current_year.id}">Balance générale (tous les comptes)</a></li>
		<li><a href="{$admin_url}acc/search.php?year={$current_year.id}">Recherche</a></li>
		{if $session->canAccess('compta', Membres::DROIT_ADMIN)}
			<li><a href="{$admin_url}acc/charts/accounts/all.php?id={$chart_id}">Plan comptable</a></li>
		{/if}
	</ul>
</nav>

{include file="acc/_simple_help.tpl" link="../reports/trial_balance.php?year=%d"|args:$current_year.id type=null}

<table class="list">
	<thead>
		<tr>
			<td class="num">Numéro</td>
			<th>Compte</th>
			<td class="money">Solde</td>
			<td></td>
			<td></td>
		</tr>
	</thead>
	{foreach from=$grouped_accounts item="group"}
	<tbody>
		<tr>
			<td colspan="5"><h2 class="ruler">{$group.label}</h2></td>
		</tr>
		{foreach from=$group.accounts item="account"}
			<tr>
				<td class="num"><a href="{$admin_url}acc/accounts/journal.php?id={$account.id}&amp;year={$current_year.id}">{$account.code}</a></td>
				<th><a href="{$admin_url}acc/accounts/journal.php?id={$account.id}&amp;year={$current_year.id}">{$account.label}</a></th>
				<td class="money">
					{if $account.sum < 0}<strong class="error">{/if}
					{$account.sum|raw|money_currency:false}
					{if $account.sum < 0}</strong>{/if}
				</td>
				<td>
					{if $account.type == Entities\Accounting\Account::TYPE_THIRD_PARTY}
					<em class="alert">
						{if $account.sum < 0}(Dette)
						{elseif $account.sum > 0}(Créance)
						{/if}
					</em>
					{/if}
				</td>
				<td class="actions">
					{linkbutton label="Journal" shape="menu" href="acc/accounts/journal.php?id=%d&year=%d"|args:$account.id,$current_year.id}
					{if $session->canAccess('compta', Membres::DROIT_ADMIN)}
						{if $account.type == Entities\Accounting\Account::TYPE_BANK}
							{linkbutton label="Rapprochement" shape="check" href="acc/accounts/reconcile.php?id=%d"|args:$account.id}
						{elseif $account.type == Entities\Accounting\Account::TYPE_OUTSTANDING}
							{linkbutton label="Dépôt en banque" shape="check" href="acc/accounts/deposit.php?id=%d"|args:$account.id}
						{/if}
					{/if}
				</td>
			</tr>
		{/foreach}
	</tbody>
	{foreachelse}
	<tbody>
		<tr>
			<td colspan="4">Il n'y a aucun compte favori avec des écritures pour l'exercice sélectionné.</td>
		</tr>
	{/foreach}
</table>

<p class="help">
	Note : n'apparaissent ici que les comptes favoris.
	Pour voir le solde de tous les comptes, se référer à la <a href="{$admin_url}acc/reports/trial_balance.php?year={$current_year.id}">balance générale de l'exercice</a>.
</p>

{include file="admin/_foot.tpl"}