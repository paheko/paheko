{include file="admin/_head.tpl" title="Nouveau compte" current="acc/charts"}

{include file="acc/charts/accounts/_nav.tpl" current="new"}

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">

	<fieldset>
		<legend>Créer un nouveau compte</legend>
		{include file="acc/charts/accounts/_account_form.tpl" simple=$simple edit_disabled=false}
	</fieldset>

	<p class="submit">
		{csrf_field key="acc_accounts_new"}
		{button type="submit" name="save" label="Créer" shape="right" class="main"}
	</p>

</form>

{include file="admin/_foot.tpl"}