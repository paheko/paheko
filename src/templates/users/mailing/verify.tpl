{include file="_head.tpl" title="Vérification d'adresse" current="users/mailing"}

<form method="post" action="{$self_url}">
	<fieldset>
		<legend>Demander la vérification de l'adresse</legend>
		{if $email.optout}
		<p class="help">
			Si le membre a cliqué par erreur sur le lien de désinscription, il est possible de rétablir l'envoi des messages.<br />
			Le membre recevra alors un message contenant un lien pour se réinscrire.
		</p>
		{elseif $email->hasReachedFailLimit()}
		<p class="help">
			Si l'adresse du membre a rencontré trop d'erreurs (boîte mail pleine par exemple), il est possible de rétablir l'envoi des messages.<br />
			Le membre recevra alors un message contenant un lien pour valider son adresse.
		</p>
		{/if}
		<p class="alert block">
			Attention, n'utiliser cette procédure qu'à la demande du membre.<br />
			En cas d'absence de consentement du membre, les messages aux autres membres pourront être bloqués par les serveurs destinataires.
		</p>
		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="send" label="Envoyer un message de vérification" shape="right" class="main"}
		</p>
	</fieldset>
</form>

{include file="_foot.tpl"}