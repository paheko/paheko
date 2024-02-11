{include file="_head.tpl" title="Message collectif : %s"|args:$mailing.subject current="users/mailing" hide_title=true}

{include file="./_nav.tpl" current="mailing"}

{if $sent}
	<p class="confirm block">L'envoi du message a bien commencé. Il peut prendre quelques minutes avant d'avoir été expédié à tous les destinataires.</p>
{/if}

{form_errors}

<form method="post" action="">
	<dl class="describe">
		{if $mailing.sent}
			<dt>Envoyé le</dt>
			<dd>{$mailing.sent|date_long:true}</dd>
		{else}
			<dt>Statut</dt>
			<dd>
				Brouillon<br />
				{if $mailing.body && $count}
				<br />{button shape="right" label="Envoyer" class="main" name="send" type="submit"}
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
			<dd>{linkbutton shape="eye" label="Prévisualiser le message" href="?id=%d&preview"|args:$mailing.id target="_dialog"}<br />
				<small class="help">(Un destinataire sera choisi au hasard.)</small></dd>
			<dt></dt>
			<dd class="help">Note : la prévisualisation peut différer du rendu final, selon le logiciel utilisé par vos destinataires pour lire leurs messages.</dd>
		{/if}
	</dl>
	{csrf_field key=$csrf_key}
</form>

{include file="_foot.tpl"}