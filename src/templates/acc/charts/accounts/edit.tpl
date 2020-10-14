{include file="admin/_head.tpl" title="Modifier un compte" current="acc/charts" js=1}

{form_errors}

<form method="post" action="{$self_url}">

	{if !$can_edit}
		<p class="alert">
			Il n'est pas possible de modifier le libellé ou la position de ce compte car il est utilisé par des écritures liées à des exercices clôturés.<br />
			Pour pouvoir modifier ce compte pour l'exercice courant, il est conseillé de <a href="{$admin_url}acc/charts/?from={$account.id_chart}">créer un nouveau plan comptable</a> en y recopiant l'ancien plan comptable.
		</p>
	{/if}

	<fieldset>
		<legend>Modifier un compte</legend>
		{include file="acc/charts/accounts/_account_form.tpl" simple=false can_edit=$can_edit}
	</fieldset>

	<p class="submit">
		{csrf_field key="acc_accounts_edit_%s"|args:$account.id}
		<input type="submit" name="edit" value="Enregistrer &rarr;" />
	</p>

</form>

{include file="admin/_foot.tpl"}