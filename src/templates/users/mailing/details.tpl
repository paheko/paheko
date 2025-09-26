{include file="_head.tpl" title="Message collectif : %s"|args:$mailing.subject current="users/mailing"}

<nav class="tabs">
	<aside>
		{linkbutton shape="plus" label="Nouveau message" href="new.php" target="_dialog"}
	</aside>
	<ul>
		<li><a href="./">Messages collectifs</a></li>
		<li><a href="rejected.php">Adresses rejetées</a></li>
	</ul>
</nav>

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
			{linkbutton shape="edit" label="Modifier" href="write.php?id=%d"|args:$mailing.id}
			{linkbutton shape="delete" label="Supprimer" href="delete.php?id=%d"|args:$mailing.id}
			{if $mailing.body}
			{linkbutton shape="right" label="Envoyer" href="send.php?id=%d"|args:$mailing.id target="_dialog"}
			{/if}
		</dd>
		<dt>Expéditeur</dt>
		<dd>
			{$mailing->getFrom()}<br/>
		</dd>
	{/if}
	<dt>Destinataires</dt>
	<dd>
		{{%n destinataire}{%n destinataires} n=$mailing->countRecipients()}<br />
		{linkbutton shape="users" label="Voir la liste des destinataires" href="recipients.php?id=%d"|args:$mailing.id}
	</dd>
	<dt>Sujet</dt>
	<dd><strong>{$mailing.subject}</strong></dd>
	<dt>Message</dt>
	<dd><pre class="preview"><code>{$mailing.body}</code></pre></dd>
	<dt>Prévisualisation</dt>
	{if $mailing.sent && $mailing->isTemplate()}
		<dd>La prévisualisation est indisponible pour ce message car il a été envoyé.</dd>
	{else}
		<dd>{linkbutton shape="eye" label="Prévisualiser le message" href="?id=%d&preview"|args:$mailing.id target="_dialog"}<br />
		 <small class="help">(Un destinataire sera choisi au hasard.)</small></dd>
		 <dt></dt>
		 <dd class="help">Note : la prévisualisation peut différer du rendu final, selon le logiciel utilisé par vos destinataires pour lire leurs messages.</dd>
	{/if}
</dl>

{include file="_foot.tpl"}