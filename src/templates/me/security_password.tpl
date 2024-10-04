{include file="_head.tpl" title="Changement du mot de passe" current="me"}

{include file="./_nav.tpl" current="security"}

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">
	<fieldset>
		<legend>Changer mon mot de passe</legend>
		{include file="users/_password_form.tpl" required=true}
	</fieldset>
	{include file="./_security_confirm_password.tpl"}
</form>

{include file="_foot.tpl"}
