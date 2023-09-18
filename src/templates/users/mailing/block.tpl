{include file="_head.tpl" title="Désinscription d'adresse" current="users/mailing"}

<form method="post" action="{$self_url}">
	<fieldset>
		<legend>Désinscrire une adresse</legend>
		<h3 class="warning">Désinscrire l'adresse {$address} ?</h3>
		<p class="alert block">
			Une fois cette adresse désinscrite, elle ne pourra plus recevoir aucun message de votre association (rappels, notifications, messages collectifs, etc.).
		</p>
		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="send" label="Désinscrire cette adresse" shape="right" class="main"}
		</p>
	</fieldset>
</form>

{include file="_foot.tpl"}