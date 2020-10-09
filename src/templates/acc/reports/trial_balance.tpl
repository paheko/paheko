{include file="admin/_head.tpl" title="Balance générale" current="acc/years"}

{include file="acc/reports/_header.tpl" current="trial_balance"}

<table class="list">
	<thead>
		<tr>
			<td>Numéro</td>
			<th>Compte</th>
			<td class="money">Total des débits</td>
			<td class="money">Total des crédits</td>
			<td class="money">Solde débiteur</td>
			<td class="money">Solde créditeur</td>
		</tr>
	</thead>
	{foreach from=$balance item="account"}
	<tbody>
		<tr>
			<td class="num"><a href="{$admin_url}acc/accounts/journal.php?id={$account.id}&amp;year={$year.id}">{$account.code}</a></td>
			<th>{$account.label}</th>
			<td class="money">{$account.debit|raw|html_money}</td>
			<td class="money">{$account.credit|raw|html_money}</td>
			<td class="money">{if $account.sum < 0}{$account.debit|escape|html_money}{/if}</td>
			<td class="money">{if $account.sum > 0}{$account.credit|escape|html_money}{/if}</td>
		</tr>
	</tbody>
	{/foreach}
</table>

<p class="help">Toutes les opérations sont libellées en {$config.monnaie}.</p>

{include file="admin/_foot.tpl"}