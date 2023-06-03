{include file="_head.tpl" title="Écritures liées à %s"|args:$transaction_user->name() current="acc/accounts"}

{if !$dialog}
<p>
	{linkbutton href="!users/details.php?id=%d"|args:$transaction_user.id label="Retour à la fiche membre" shape="user"}
</p>
{/if}

<p class="help">
	De la plus récente à la plus ancienne.
</p>

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
	<thead>
		<tr>
			<td class="num">Numéro</td>
			<th>Compte</th>
			<td class="money">Solde</td>
		</tr>
	</thead>
	<tbody>
	{foreach from=$balance item="account"}
		<tr>
			<td class="num"><a href="{$admin_url}acc/accounts/journal.php?id={$account.id}">{$account.code}</a></td>
			<th>{$account.label}</th>
			<td class="money">{$account.balance|raw|money}</td>
		</tr>
	{/foreach}
	</tbody>
</table>

{include file="_foot.tpl"}