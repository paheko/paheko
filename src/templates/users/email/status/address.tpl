{include file="_head.tpl" title="Adresse e-mail" current="users/mailing"}

{if $_GET.msg === 'VERIFICATION_SENT'}
<p class="confirm block">
	Un message de demande de confirmation a bien été envoyé.<br />
	Le destinataire doit désormais cliquer sur le lien dans ce message pour valider son adresse.
</p>
{/if}

<div class="describe">
	<dt>Adresse e-mail</dt>
	<dd>{if $raw_address}{$raw_address}{else}<em>anonymisée</em>{/if}</dd>
	<dt>Statut</dt>
	<dd>{tag label=$address->getStatusLabel() color=$address->getStatusColor()}</dd>
	<dt>Description du statut</dt>
	<dd>
		{if $address.status === $address::STATUS_VERIFIED}
			L'adresse a déjà reçu un message et a été vérifiée manuellement par le destinataire.
		{elseif $address.status === $address::STATUS_INVALID}
			Cette adresse a une erreur de syntaxe, ou le serveur n'existe pas.
		{elseif $address.status === $address::STATUS_HARD_BOUNCE}
			Le serveur existe, mais l'adresse n'existe pas, ou bloque vos messages définitivement.
		{elseif $address.status === $address::STATUS_SOFT_BOUNCE_LIMIT_REACHED}
			L'adresse existe, mais a rencontré plus de {$max_fail_count} erreurs temporaires.<br />
			Cela arrive par exemple si vos messages sont vus comme du spam trop souvent, ou si la boîte mail destinataire est pleine.<br />
			Cette adresse ne recevra plus de message.
		{elseif $address.status === $address::STATUS_OPTOUT}
			L'adresse existe, mais le destinataire a demandé à ne recevoir de messages de votre part.
		{else}
			Cette adresse n'a pas rencontré de problème jusque là.
		{/if}
	</dd>
	<dt>Nombre de messages envoyés</dt>
	<dd>{$address.sent_count}</dd>
	<dt>Nombre d'erreurs temporaires</dt>
	<dd>{$address.bounce_count}</dd>
</div>

{include file="_foot.tpl"}