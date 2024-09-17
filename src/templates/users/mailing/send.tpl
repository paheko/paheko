{include file="_head.tpl" title="Message collectif : %s"|args:$mailing.subject current="users/mailing"}

<form method="post" action="">
	<fieldset>
		<legend>Envoyer un message collectif</legend>
		<h3>Envoyer le message "{$mailing.subject}" à {$mailing->countRecipients()} destinataires ?</h3>
		{csrf_field key=$csrf_key}
		<p class="submit">{button class="main" type="submit" name="send" label="Envoyer" shape="right"}</p>
	</fieldset>
</form>

{include file="_foot.tpl"}