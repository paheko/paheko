{include file="_head.tpl" title="Contacter un membre" current="membres"}

{form_errors}

<form method="post" action="{$self_url}">
	<fieldset class="message">
		<legend>Message</legend>
		<dl>
		{if $is_admin}
			{input type="radio-btn" name="sender" value="org" default="org" required=true label=$config.org_name prefix_title="Nom de l'expéditeur"}
			{if $can_email}
				{input type="radio-btn" name="sender" value="self" required=true label=$self->name()}
			{/if}
			<?php $sender_email = MAIL_SENDER ?? $config->org_email; ?>
			{input type="text" name="" default=$sender_email disabled=true label="Adresse de l'expéditeur" class="full-width" required=true}
			{if MAIL_SENDER_EXPLAIN}
				<dd class="help"><?=MAIL_SENDER_EXPLAIN?></dd>
			{/if}
		{else}
			<dd>{$self->name()}</dd>
		{/if}
			<dt>Destinataire</dt>
			<dd><strong>{$recipient->name()}</strong></dd>
			{input type="text" name="subject" required=true label="Sujet" class="full-width"}
			{input type="textarea" name="message" required=true label="Message" rows=15 class="full-width"}
			{input type="checkbox" name="send_copy" value=1 label="Recevoir par e-mail une copie du message envoyé"}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="send" label="Envoyer" shape="mail" class="main"}
	</p>
</form>


{include file="_foot.tpl"}