{include file="admin/_head.tpl" title="Comptes" current="acc/accounts"}

{include file="acc/_year_select.tpl"}

<nav class="tabs">
	<ul>
		<li class="current"><a href="{$admin_url}acc/accounts/">Comptes</a></li>
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
				<td class="money">{$account.sum|raw|html_money:false}&nbsp;{$config.monnaie}</td>
				<td class="actions">
					{linkbutton label="Journal" shape="menu" href="acc/accounts/journal.php?id=%d&year=%d"|args:$account.id,$current_year.id}
					{if $account.type == Entities\Accounting\Account::TYPE_BANK && $session->canAccess('compta', Membres::DROIT_ADMIN)}
						{linkbutton label="Rapprocher" shape="check" href="acc/accounts/reconcile.php?id=%d"|args:$account.id}
					{/if}
				</td>
			</tr>
		{/foreach}
	</tbody>
	{/foreach}
</table>

{include file="admin/_foot.tpl"}