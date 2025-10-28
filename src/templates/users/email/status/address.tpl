{include file="_head.tpl" title="Adresse e-mail" current="users/mailing"}

{if $_GET.msg === 'VERIFICATION_SENT'}
<p class="confirm block">
	Un message de demande de confirmation a bien été envoyé.<br />
	Le destinataire doit désormais cliquer sur le lien dans ce message pour valider son adresse.
</p>
{/if}

<div class="describe">
	<dt>Adresse e-mail</dt>
	<dd>{$raw_address}</dd>
	<dt>Statut</dt>
	<dd>{tag label=$address->getStatusLabel() color=$address->getStatusColor()}</dd>
	<dt>Description du statut</dt>
	<dd>
		{if $address.status === $address::STATUS_VERIFIED}
			L'adresse a déjà reçu un message et a été vérifiée manuellement par le destinataire.
		{elseif $address.status === $address::STATUS_INVALID}
			Cette adresse a une erreur de syntaxe, ou le serveur destinataire n'existe pas.
		{elseif $address.status === $address::STATUS_HARD_BOUNCE}
			Le serveur destinataire existe, mais l'adresse n'existe pas, ou bloque vos messages définitivement.
		{elseif $address.status === $address::STATUS_SOFT_BOUNCE_LIMIT_REACHED}
			L'adresse existe, mais a rencontré plus de {$max_fail_count} erreurs temporaires.<br />
			Cela arrive par exemple si vos messages sont vus comme du spam trop souvent, ou si la boîte mail destinataire est pleine.<br />
			Cette adresse ne recevra plus de message.
		{else}
			Cette adresse n'a pas rencontré de problème jusque là.
		{/if}
	</dd>
	{if !$email->canSend()}
	<dt>Vérification</dt>
	<dd>
		{if $email->canSendVerificationAfterFail()}
			{linkbutton target="_dialog" label="Vérifier l'adresse" href="verify.php?address=%s"|args:$address shape="check"}<br />
			<span class="help">Un message de vérification sera envoyé à cette adresse. Si le destinataire clique sur le lien dans ce message, l'adresse sera vérifiée et pourra à nouveau recevoir des messages.</span>
		{else}
			<p class="alert block">Il faut attendre 15 jours après le dernier envoi pour pouvoir envoyer un message de vérification à cette adresse.</p>
		{/if}
	</dd>
	{/if}
	<dt>Nombre de messages envoyés</dt>
	<dd>{$address.sent_count}</dd>
	<dt>Nombre d'erreurs temporaires</dt>
	<dd>{$address.bounce_count}</dd>
	<dt>Journal d'erreurs</dt>
	<dd>{if $email.fail_log}{$email.fail_log|escape|nl2br}{else}<em>(Vide)</em>{/if}</dd>
	<dt>Dernier message envoyé</dt>
	<dd>{if $email.last_sent}{$email.last_sent|date_short:true}{else}<em>(Aucun historique)</em>{/if}</dd>
	<dt>Préférences de réception</dt>
	<dd>
		{if $email.accepts_messages}{icon shape="check"}{else}{icon shape="uncheck"}{/if} Messages personnels<br />
		{if $email.accepts_reminders}{icon shape="check"}{else}{icon shape="uncheck"}{/if} Rappels de cotisation et d'activité<br />
		{if $email.accepts_mailings}{icon shape="check"}{else}{icon shape="uncheck"}{/if} Messages collectifs<br />
		{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_WRITE)}
			{linkbutton target="_dialog" label="Modifier les préférences d'envoi" href="preferences.php?address=%s"|args:$address shape="settings"}
		{/if}
	</dd>
</div>

{include file="_foot.tpl"}