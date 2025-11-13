{include file="_head.tpl" title="Préférences de réception de messages" current="users/mailing"}

<form method="post" action="{$self_url}">
	<fieldset>
		<legend>Préférences de l'adresse {$address}</legend>
		<dl>
			<?php $disabled = !$email->accepts_messages; $help = $disabled ? '*' : null; ?>
			{input type="checkbox" disabled=$disabled name="accepts_messages" source=$email value=1 label="Messages personnels et notifications" prefix_title="Ce destinataire accepte les messages suivants" prefix_required=true help=$help}
			<?php $disabled = !$email->accepts_reminders; $help = $disabled ? '*' : null; ?>
			{input type="checkbox" disabled=$disabled source=$email name="accepts_reminders" value=1 label="Rappels de cotisation" help=$help}
			<?php $disabled = !$email->accepts_mailings; $help = $disabled ? '*' : null; ?>
			{input type="checkbox" disabled=$disabled source=$email name="accepts_mailings" value=1 label="Messages collectifs (lettres d'information)" help=$help}
		</dl>
		<p class="help">(*) Les cases décochées ne peuvent être ré-activées que par la personne destinataire, conformément au RGPD.<br />
			Voici le lien à transmettre au destinataire pour qu'iel puisse se réinscrire aux envois :<br />
			{input type="text" readonly=true copy=true default=$user_prefs_url name=""}
		</p>
		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="send" label="Enregistrer" shape="right" class="main"}
		</p>
	</fieldset>
</form>

{include file="_foot.tpl"}