{include file="_head.tpl" title="Modifier un compte" current="acc/years"}

{include file="acc/charts/accounts/_nav.tpl" current="new"}

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">

	{if !$can_edit}
		<p class="block alert">
			Il n'est pas possible de modifier le libellé, le numéro ou la position de ce compte car il {if $account.user}est utilisé dans des exercices clôturés{else}fait partie du plan comptable officiel{/if}.<br />
			Pour pouvoir modifier ce compte pour un nouvel exercice, il est conseillé de <a href="{$admin_url}acc/charts/?from={$account.id_chart}">créer un nouveau plan comptable</a> en y recopiant l'ancien plan comptable.
		</p>
	{/if}

	<fieldset>
		<legend>Modifier un compte</legend>
		{include file="acc/charts/accounts/_account_form.tpl" create=false}
	</fieldset>

	<p class="submit">
		{csrf_field key="acc_accounts_edit_%s"|args:$account.id}
		{button type="submit" name="edit" label="Enregistrer" shape="right" class="main"}
	</p>

</form>

{include file="_foot.tpl"}