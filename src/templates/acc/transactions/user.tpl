{include file="_head.tpl" title="Écritures liées à %s"|args:$transaction_user->name() current="acc/accounts"}

<nav class="tabs">
	<aside>{exportmenu right=true table=true}</aside>
</nav>

{if !$dialog}
<p>
	{linkbutton href="!users/details.php?id=%d"|args:$transaction_user.id label="Retour à la fiche membre" shape="left"}
</p>
{/if}

<h2 class="ruler">Toutes les écritures liées</h2>

{include file="acc/reports/_journal.tpl"}

<h2 class="ruler">Solde des comptes</h2>

<form method="get" action="{$self_url_no_qs}">
	<fieldset>
		<legend>Exercice</legend>
		<dl>
			{input type="select" name="year" options=$years onchange="this.form.submit();" default=$year}
		</dl>
		<input type="hidden" name="id" value="{$transaction_user.id}" />
		<noscript>
			<input type="submit" value="OK" />
		</noscript>
	</fieldset>
</form>

<p class="block help">Cette liste représente le solde des comptes uniquement pour les écritures liées à ce membre.</p>

<table class="list">
	<caption>Solde des comptes</caption>
	<thead>
		<tr>
			<td class="num">Numéro</td>
			<th>Compte</th>
			{if $simple}
				<td class="money">Solde</td>
			{else}
				<td class="money">Débits</td>
				<td class="money">Crédits</td>
				<td class="money">Solde débiteur</td>
				<td class="money">Solde créditeur</td>
			{/if}
		</tr>
	</thead>
	<tbody>
	{foreach from=$balance item="account"}
		<tr class="{if $account.balance === 0}disabled{/if}">
			<td class="num"><a href="{$admin_url}acc/accounts/journal.php?id={$account.id}">{$account.code}</a></td>
			<th>{$account.label}</th>
			{if $simple}
				<td class="money">{show_balance account=$account}</td>
			{else}
				<td class="money{if !$account.debit} disabled{/if}">{$account.debit|raw|money:false}</td>
				<td class="money{if !$account.credit} disabled{/if}">{$account.credit|raw|money:false}</td>
				<td class="money">{if $account.balance > 0}{$account.balance|abs|escape|money:false}{/if}</td>
				<td class="money">{if $account.balance < 0}{$account.balance|abs|escape|money:false}{/if}</td>
			{/if}
		</tr>
	{/foreach}
	</tbody>
</table>

<p class="actions">
	{linkbutton shape="menu" href="!acc/reports/ledger.php?year=%d&user=%d"|args:$year:$transaction_user.id label="Grand livre de ce membre"}
</p>

{include file="_foot.tpl"}