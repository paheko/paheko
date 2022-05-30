{include file="admin/_head.tpl" title="Vérification d'adresse" current="membres/message"}

<form method="post" action="{$self_url}">
	<fieldset>
		<legend>Demander la vérification de l'adresse</legend>
		<p class="help">
			Si le membre a cliqué par erreur sur le lien de désinscription, il est possible de rétablir l'envoi des messages.<br />
			Le membre recevra alors un message contenant un autre lien pour se réinscrire.
		</p>
		<p class="alert block">
			Attention, n'utiliser cette procédure qu'à la demande du membre.<br />
			Si vous essayez d'envoyer des messages à une adresse qui ne désire pas recevoir vos messages, vos messages aux autres membres pourront être bloqués par les serveurs destinataires.
		</p>
		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="send" label="Envoyer un message de vérification" shape="right" class="main"}
		</p>
	</fieldset>
</form>

{include file="admin/_foot.tpl"}