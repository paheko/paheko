{include file="admin/_head.tpl" title="Nouveau compte" current="acc/charts" js=1}

{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Créer un nouveau compte</legend>
		{include file="acc/charts/accounts/_account_form.tpl" simple=$simple edit_disabled=false}
	</fieldset>

	<p class="submit">
		{csrf_field key="acc_accounts_new"}
		<input type="submit" name="save" value="Créer &rarr;" />
	</p>

</form>

{include file="admin/_foot.tpl"}