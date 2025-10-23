{include file="_head.tpl" title=$address current="users/mailing"}

<dl class="describe">
	<dt>Adresse e-mail</dt>
	<dd>{$address}</dd>
	<dt>Statut</dt>
	<dd>
		{if $email.invalid}
			{tag label="Adresse invalide" color="darkred"}<br />
			<span class="help">L'adresse n'existe pas ou plus. Il n'est pas possible de lui envoyer des messages.</span>
		{elseif $email->hasReachedFailLimit()}
			{tag label="Adresse bloquée" color="darkorange"}<br />
			<span class="help">Le fournisseur du destinataire a renvoyé une erreur temporaire plus de {$max_fail_count} fois. Cela arrive par exemple si vos messages sont vus comme du spam trop souvent, ou si la boîte mail destinataire est pleine. Cette adresse ne recevra plus de message.</span>
		{elseif $email.verified}
			{tag label="Adressee vérifiée" color="darkgreen"}<br />
			<span class="help">Cette adresse a été vérifiée par l'envoi d'un message au destinataire contenant un lien à cliquer.</span>
		{else}
			{tag label="Adresse non vérifiée"}
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
	<dt>Journal d'erreurs</dt>
	<dd>{if $email.fail_log}{$email.fail_log|escape|nl2br}{else}<em>(Vide)</em>{/if}</dd>
	<dt>Nombre de messages envoyés</dt>
	<dd>{$email.sent_count}</dd>
	<dt>Nombre d'erreurs temporaires</dt>
	<dd>{$email.fail_count}</dd>
	<dt>Dernier message envoyé</dt>
	<dd>{if $email.last_sent}{$email.last_sent|date_short:true}{else}<em>(Aucun historique)</em>{/if}</dd>
	<dt>Préférences d'envoi</dt>
	<dd>
		{if $email.accepts_messages}{icon shape="check"}{else}{icon shape="uncheck"}{/if} Messages personnels<br />
		{if $email.accepts_reminders}{icon shape="check"}{else}{icon shape="uncheck"}{/if} Rappels de cotisation et d'activité<br />
		{if $email.accepts_mailings}{icon shape="check"}{else}{icon shape="uncheck"}{/if} Messages collectifs<br />
		{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_WRITE)}
			{linkbutton target="_dialog" label="Modifier les préférences d'envoi" href="preferences.php?address=%s"|args:$address shape="settings"}
		{/if}
	</dd>
</dl>

{include file="_foot.tpl"}