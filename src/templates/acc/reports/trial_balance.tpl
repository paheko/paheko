{include file="admin/_head.tpl" title="Balance générale" current="acc/years"}

{include file="acc/reports/_header.tpl" current="trial_balance" title="Balance générale"}

<p class="help block noprint">
	Attention&nbsp;: cette vue présente le solde selon les normes comptables.<br />
	Si le montant est <strong>positif</strong> c'est que le compte est <strong>créditeur</strong>.<br />Si le montant est <strong>négatif</strong> c'est que le compte est <strong>débiteur</strong>.
</p>

<table class="list">
	<thead>
		<tr>
			<td>Numéro</td>
			<th>Compte</th>
			<td class="money">Total des débits</td>
			<td class="money">Total des crédits</td>
			<td class="money">Solde</td>
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
			<td class="money">{if $account.balance !== null}<b>{$account.balance|escape|money:false}</b>{/if}</td>
		</tr>
	{/foreach}
	</tbody>
</table>

<p class="help">Toutes les écritures sont libellées en {$config.monnaie}. Les lignes grisées correspondent aux comptes soldés.</p>

{include file="admin/_foot.tpl"}