{include file="_head.tpl" title="%sBalance générale"|args:$title current="acc/years" prefer_landscape=true}

{include file="acc/reports/_header.tpl" current="trial_balance" title="Balance générale" allow_filter=true}

<table class="list statement autofilter">
	<thead>
		<tr>
			<td>Numéro</td>
			<td>Compte</td>
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
			<td class="num" data-spreadsheet-type="string">
				{if !empty($year) && !$criterias.project}<a href="{$admin_url}acc/accounts/journal.php?id={$account.id}&amp;year={$year.id}">{$account.code}</a>
				{else}{$account.code}
				{/if}
			</td>
			<th data-spreadsheet-type="string">{$account.label}</th>
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

<p class="help">Toutes les écritures sont libellées en {$config.currency}. Les lignes grisées correspondent aux comptes soldés.</p>

{include file="_foot.tpl"}