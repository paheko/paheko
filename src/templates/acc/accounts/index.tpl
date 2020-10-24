{include file="admin/_head.tpl" title="Comptes" current="acc/accounts"}

{include file="acc/_year_select.tpl"}

<nav class="tabs">
	<ul>
		<li class="current"><a href="{$admin_url}acc/accounts/">Comptes</a></li>
		<li><a href="{$admin_url}acc/search.php?year={$current_year.id}">Recherche</a></li>
		{if $session->canAccess('compta', Membres::DROIT_ADMIN)}
			<li><a href="{$admin_url}acc/charts/accounts/?id={$chart_id}">Gestion des comptes</a></li>
		{/if}
	</ul>
</nav>

<p class="alert">
	Attention&nbsp;: en comptabilité, les comptes de banque, de caisse, et de tiers apparaissent inversés par rapport aux relevés (<em>la banque doit de l'argent à l'association, donc les sommes placées sur le compte bancaires apparaissent au débit</em>).
</p>

<table class="list">
	<thead>
		<tr>
			<td>Numéro</td>
			<th>Compte</th>
			<td class="money">Solde</td>
			<td></td>
		</tr>
	</thead>
	{foreach from=$grouped_accounts item="group"}
	<tbody>
		<tr>
			<td colspan="4"><h2 class="ruler">{$group.label}</h2></td>
		</tr>
		{foreach from=$group.accounts item="account"}
			<tr>
				<td class="num"><a href="{$admin_url}acc/accounts/journal.php?id={$account.id}&amp;year={$current_year.id}">{$account.code}</a></td>
				<th>{$account.label}</th>
				<td class="money">
					{if in_array($account.type, [Entities\Accounting\Account::TYPE_REVENUE, Entities\Accounting\Account::TYPE_EXPENSE])}
						{$account.sum|abs|raw|html_money:false}&nbsp;{$config.monnaie}
					{else}
						{$account.sum|raw|html_money:false}&nbsp;{$config.monnaie}
					{/if}
				</td>
				<td class="actions">
					{linkbutton label="Journal" shape="menu" href="acc/accounts/journal.php?id=%d&year=%d"|args:$account.id,$current_year.id}
					{if $session->canAccess('compta', Membres::DROIT_ADMIN)}
						{if $account.type == Entities\Accounting\Account::TYPE_BANK}
							{linkbutton label="Rapprocher" shape="check" href="acc/accounts/reconcile.php?id=%d"|args:$account.id}
						{elseif $account.type == Entities\Accounting\Account::TYPE_OUTSTANDING}
							{linkbutton label="Dépôt en banque" shape="check" href="acc/accounts/deposit.php?id=%d"|args:$account.id}
						{/if}
					{/if}
				</td>
			</tr>
		{/foreach}
	</tbody>
	{/foreach}
</table>

<p class="help">
	Note : n'apparaissent ici que les comptes favoris.
	Pour voir le solde de tous les comptes, se référer à la <a href="{$admin_url}acc/reports/trial_balance.php?year={$current_year.id}">balance générale de l'exercice</a>.
</p>

{include file="admin/_foot.tpl"}