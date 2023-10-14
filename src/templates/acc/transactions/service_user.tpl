{include file="_head.tpl" title="Écritures liées à une inscription" current="acc/accounts"}

<nav class="tabs">
	{linkbutton href="!users/details.php?id=%d"|args:$user_id label="Retour à la fiche membre" shape="user"}
	{linkbutton href="!services/user/payment.php?id=%d"|args:$service_user_id label="Nouveau règlement" shape="plus" target="_dialog"}
	{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_WRITE)}
	{linkbutton href="!services/user/link.php?id=%d"|args:$service_user_id label="Lier à une écriture" shape="check" target="_dialog"}
	{/if}
</nav>

{if empty($balance)}
	<p class="alert block">Aucune écriture n'est liée à cette inscription.</p>
{else}
	{include file="acc/reports/_journal.tpl"}

	<h2 class="ruler">Solde des comptes</h2>

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
				<td class="money">{$account.balance|raw|money:false}</td>
			</tr>
		{/foreach}
		</tbody>
	</table>
{/if}

{include file="_foot.tpl"}