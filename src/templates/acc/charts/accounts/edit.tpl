{include file="admin/_head.tpl" title="Modifier un compte" current="acc/charts" js=1}

{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Modifier un compte</legend>
		{include file="acc/charts/accounts/_account_form.tpl" simple=false}
	</fieldset>

	<p class="submit">
		{csrf_field key="acc_accounts_edit_%s"|args:$account.id}
		<input type="submit" name="edit" value="Enregistrer &rarr;" />
	</p>

</form>

{include file="admin/_foot.tpl"}