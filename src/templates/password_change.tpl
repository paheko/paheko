{include file="_head.tpl" title="Changement de mot de passe"}


{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Choisir un nouveau mot de passe</legend>
		{include file="users/_password_form.tpl" required=true}
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="change" label="Modifier mon mot de passe" shape="right" class="main"}
	</p>


</form>


{include file="_foot.tpl"}