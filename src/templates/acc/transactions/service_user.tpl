{include file="admin/_head.tpl" title="Écritures liées à une inscription" current="acc/accounts"}

<nav class="tabs">
	{linkbutton href="!membres/fiche.php?id=%d"|args:$user_id label="Retour à la fiche membre" shape="user"}
	{linkbutton href="!services/payment.php?id=%d"|args:$service_user_id label="Nouveau règlement" shape="plus"}
</nav>

{include file="acc/reports/_journal.tpl"}

<h2 class="ruler">Solde des comptes</h2>

<table class="list">
	<thead>
		<tr>
			<td>Numéro</td>
			<th>Compte</th>
			<td class="money">Solde débiteur</td>
			<td class="money">Solde créditeur</td>
		</tr>
	</thead>
	<tbody>
	{foreach from=$balance item="account"}
		<tr>
			<td class="num"><a href="{$admin_url}acc/accounts/journal.php?id={$account.id}">{$account.code}</a></td>
			<th>{$account.label}</th>
			<td class="money">{if $account.sum < 0}{$account.sum|raw|money}{/if}</td>
			<td class="money">{if $account.sum > 0}{$account.sum|raw|money}{/if}</td>
		</tr>
	{/foreach}
	</tbody>
</table>

{include file="admin/_foot.tpl"}