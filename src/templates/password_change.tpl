{include file="admin/_head.tpl" title="Changement de mot de passe"}


{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Choisir un nouveau mot de passe</legend>
		<dl>
			{include file="users/_password_form.tpl" required=true}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key="changePassword"}
		{button type="submit" name="change" label="Modifier mon mot de passe" shape="right" class="main"}
	</p>


</form>


{include file="admin/_foot.tpl"}