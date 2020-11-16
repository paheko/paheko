{include file="admin/_head.tpl" title="Écritures liées à %s"|args:$transaction_user.identite current="acc/accounts"}

<p>
	{linkbutton href="!membres/fiche.php?id=%d"|args:$transaction_user.id label="Retour à la fiche membre" shape="user"}
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
			<td class="money">{if $account.sum < 0}{$account.sum|raw|html_money}{/if}</td>
			<td class="money">{if $account.sum > 0}{$account.sum|raw|html_money}{/if}</td>
		</tr>
	{/foreach}
	</tbody>
</table>

{include file="admin/_foot.tpl"}