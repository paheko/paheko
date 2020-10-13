{include file="admin/_head.tpl" title="Écritures liées à %s"|args:$transaction_user.identite current="acc/accounts"}

{include file="acc/reports/_journal.tpl"}

<h2 class="ruler">Solde des comptes</h2>

<p class="alert">Cette liste représente le solde des comptes uniquement pour les écritures liées à un membre, et ce sur tous les exercices réunis.</p>

<table class="list">
	<thead>
		<tr>
			<td>Numéro</td>
			<th>Compte</th>
			<td class="money">Solde</td>
		</tr>
	</thead>
	<tbody>
	{foreach from=$balance item="account"}
		<tr>
			<td class="num"><a href="{$admin_url}acc/accounts/journal.php?id={$account.id}">{$account.code}</a></td>
			<th>{$account.label}</th>
			<td class="money">{$account.sum|raw|html_money:false}</td>
		</tr>
	{/foreach}
	</tbody>
</table>

{include file="admin/_foot.tpl"}