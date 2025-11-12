{include file="_head.tpl" title="Modifier" current="users/mailing" hide_title=true}

{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Modifier les informations du message collectif</legend>
		<dl>
			{input type="text" name="subject" required=true label="Sujet" source=$mailing class="full-width" help="Il est recommandé de ne pas dépasser 60 caractères et 8 mots pour que le message soit lu."}
			{input type="text" name="preheader" required=false label="Extrait" source=$mailing help="Ce texte apparaîtra en dessous ou à côté du sujet dans la liste des messages du destinataire. Longueur maximale : 100 caractères." maxlength=100 class="full-width"}
			{input type="text" name="sender_name" source=$mailing label="Nom de l'expéditeur"}
			{if $forced_sender}
				{input type="email" name="" disabled=true required=true label="Adresse e-mail de l'expéditeur" default=$forced_sender}
				{if MAIL_SENDER_EXPLAIN}
					<dd class="help"><?=MAIL_SENDER_EXPLAIN?></dd>
				{/if}
				{input type="email" name="sender_email" source=$mailing label="Adresse e-mail de réponse" help="Les réponses au message seront envoyées à cette adresse."}
			{else}
				{input type="email" name="sender_email" source=$mailing label="Adresse e-mail de l'expéditeur"}
			{/if}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>

</form>


{include file="_foot.tpl"}