{include file="admin/_head.tpl" title="Balance générale" current="acc/years"}

{include file="acc/reports/_header.tpl" current="trial_balance" title="Balance générale" sub_current=$simple}

<table class="list">
	<thead>
		<tr>
			<td>Numéro</td>
			<th>Compte</th>
			<td class="money">Total des débits</td>
			<td class="money">Total des crédits</td>
			{if !$simple}
			<td class="money">Solde débiteur</td>
			<td class="money">Solde créditeur</td>
			{else}
			<td class="money">Solde</td>
			{/if}
		</tr>
	</thead>
	<tbody>
	{foreach from=$balance item="account"}
		<tr class="{if $account.balance === 0}disabled{/if}">
			<td class="num">
				{if !empty($year)}<a href="{$admin_url}acc/accounts/journal.php?id={$account.id}&amp;year={$year.id}">{$account.code}</a>
				{else}{$account.code}
				{/if}
			</td>
			<th>{$account.label}</th>
			<td class="money{if !$account.debit} disabled{/if}">{$account.debit|raw|money:false}</td>
			<td class="money{if !$account.credit} disabled{/if}">{$account.credit|raw|money:false}</td>
			{if !$simple}
			<td class="money">{if $account.balance > 0}{$account.balance|abs|escape|money:false}{/if}</td>
			<td class="money">{if $account.balance < 0}{$account.balance|abs|escape|money:false}{/if}</td>
			{else}
			<td class="money">{if $account.balance !== null}<b>{$account.balance|escape|money:false}</b>{/if}</td>
			{/if}
		</tr>
	{/foreach}
	</tbody>
</table>

<p class="help">Toutes les écritures sont libellées en {$config.monnaie}. Les lignes grisées correspondent aux comptes soldés.</p>

{include file="admin/_foot.tpl"}