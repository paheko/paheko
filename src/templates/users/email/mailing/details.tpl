{include file="_head.tpl" title="Message collectif : %s"|args:$mailing.subject current="users/mailing" hide_title=true}

{include file="../_nav.tpl" current="mailing"}

{if $sent}
	<p class="confirm block">L'envoi du message a bien commencé. Il peut prendre quelques minutes avant d'avoir été expédié à tous les destinataires.</p>
{elseif !empty($hints)}
	<div class="alert block">
		<h3>Il y a des problèmes dans ce message&nbsp;:</h3>
		<ul>
		{foreach from=$hints item="message"}
			<li>{$message}</li>
		{/foreach}
		</ul>
		<p>Ces problèmes peuvent mener à ce que ce message termine dans le dossier <em>Indésirables</em> de vos destinataires, ou même à ce que le message soit refusé ou supprimé.<br /><strong>Cela peut aussi mener au blocage de vos futurs envois.</strong></p>
	</div>
{/if}

{form_errors}

<dl class="describe">
	{if $mailing.sent}
		<dt>Envoyé le</dt>
		<dd>{$mailing.sent|date_long:true}</dd>
	{else}
		<dt>Statut</dt>
		<dd>
			Brouillon<br />
			{if $mailing.body && $count}
			{linkbutton shape="right" label="Envoyer" href="send.php?id=%d"|args:$mailing.id target="_dialog"}
			{/if}
		</dd>
		<dt>Expéditeur</dt>
		<dd>
			{$mailing->getFrom()}<br/>
		</dd>
	{/if}
	{if $mailing.target_type}
	<dt>Cible</dt>
	<dd>
		{$mailing->getTargetTypeLabel()} — {$mailing.target_label}
	</dd>
	{/if}
	<dt>Destinataires</dt>
	<dd>
	{if $mailing.count}
		{{%n destinataire}{%n destinataires} n=$count}<br />
		{linkbutton shape="users" label="Voir la liste des destinataires" href="recipients.php?id=%d"|args:$mailing.id}
		{linkbutton shape="plus" label="Ajouter des destinataires" href="populate.php?id=%d"|args:$mailing.id}
	{else}
		{linkbutton class="main" shape="plus" label="Ajouter des destinataires" href="populate.php?id=%d"|args:$mailing.id}
	{/if}
	</dd>

	<dt>Sujet</dt>
	<dd><strong>{$mailing.subject}</strong></dd>
	<dt>Message</dt>
	<dd><pre class="preview"><code>{$mailing.body}</code></pre></dd>
	{if $count}
		<dt>Prévisualisation</dt>
		{if $mailing->isTemplate() && $mailing.sent}
			<dd class="help">La prévisualisation est indisponible pour ce message, car il utilise des balises de données liées aux destinataires. Une fois le message envoyé, les données personnelles des destinataires sont supprimées, en conformité avec le RGPD, il n'est donc plus possible de prévisualiser le message.</dd>
		{else}
			<dd>{linkbutton shape="eye" label="Prévisualiser le message" href="?id=%d&preview"|args:$mailing.id target="_dialog"}<br />
			 <small class="help">(Un destinataire sera choisi au hasard.)</small></dd>
			 <dt></dt>
			 <dd class="help">Note : la prévisualisation peut différer du rendu final, selon le logiciel utilisé par vos destinataires pour lire leurs messages.</dd>
		{/if}
	{/if}
</dl>

{include file="_foot.tpl"}