{include file="_head.tpl" title="Contacter un membre" current="membres"}

{form_errors}

<form method="post" action="{$self_url}">
	<fieldset class="message">
		<legend>Message</legend>
		<dl>
			<dt>Expéditeur</dt>
		{if $is_admin}
			{input type="radio" name="sender" value="org" default="org" required=true label='Association : "%s" <%s>'|args:$config.org_name:$config.org_email}
			{if $can_email}
				{input type="radio" name="sender" value="self" required=true label="Membre : %s"|args:$self->getNameAndEmail()}
			{/if}
		{else}
			<dd>{$self->getNameAndEmail()}</dd>
		{/if}
			<dt>Destinataire</dt>
			<dd>{$recipient->getNameAndEmail()}</dd>
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