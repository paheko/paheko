{include file="_head.tpl" title="Configuration de cet ordinateur" current="config"}

{include file="config/_menu.tpl" current="desktop" sub_current=null}

{if isset($_GET['ok']) && !$form->hasErrors()}
	<p class="block confirm">
		La configuration a bien été enregistrée.
	</p>
{/if}


{form_errors}

<form method="post" action="{$self_url}">

	<p class="help">
		Cette installation de Paheko fonctionne sur un ordinateur de bureau.<br />
		Ces réglages n'affectent que cet ordinateur. Si la base de données est transférée sur un serveur, ils ne s'appliqueront pas.
	</p>

	<fieldset>
		<legend>Base de données</legend>
		<dl>
			<dt>Fichier de base de données utilisé</dt>
			<dd><samp>{$constants.DB_FILE}</samp></dd>
			<dd>{linkbutton shape="reset" label="Ouvrir une autre base de données" href="!open_db.php"}</dd>
		</dl>
	</fieldset>

	<fieldset>
		<legend>Connexion par mot de passe</legend>
		{if !$can_configure_local_login}
			<p class="alert block">
				Aucun membre administrateur ne possède de mot de passe, il n'est donc pas possible d'activer la connexion par mot de passe.<br />
				Choisissez un mot de passe pour au moins un membre administrateur pour pouvoir modifier cette option.
			</p>
		{else}
			<dl>
				{input type="radio" name="LOCAL_LOGIN" value=-1 label="Désactiver la connexion par mot de passe" source=$constants}
				<dd class="help">Dans ce cas le premier membre administrateur ({$first_admin_user_name}) sera toujours connecté. Il ne sera pas possible de se connecter avec un autre compte de membre.</dd>
				{input type="radio" name="LOCAL_LOGIN" value=0 label="Activer la connexion par mot de passe" source=$constants}
				<dd class="help">Si cette option est sélectionnée, vous devrez indiquer un identifiant et un mot de passe pour vous connecter à chaque fois.<br />Utile si plusieurs personnes utilisent cet ordinateur.</dd>
			</dl>
		{/if}
	</fieldset>

	<fieldset>
		<legend>Logiciels externes</legend>
		<dl>
			{input type="select" name="PDF_COMMAND" label="Logiciel de génération de PDF" options=$pdf_commands source=$constants default_empty="— Désactiver la génération de PDF —"}
			{if empty($pdf_commands)}
				<dd class="help">Aucun programme de génération de PDF n'est installé.</dd>
			{/if}
			<dt>Logiciels utilisés pour la conversion de fichiers, et la génération d'images miniatures</dt>
			{foreach from=$available_conversion_commands item="cmd"}
				<?php $checked = in_array($cmd, $constants['CONVERSION_TOOLS'] ?? []); ?>
				{input type="checkbox" name="CONVERSION_TOOLS[%s]"|args:$cmd value=$cmd label=$cmd default=$checked}
			{foreachelse}
				<dd class="help">Aucun programme n'est installé sur cet ordinateur.</dd>
			{/foreach}
		</dl>
	</fieldset>

	<fieldset>
		<legend>Configuration envoi d'e-mails</legend>
		<dl>
			{input type="select" name="email" options=$email_options default=$current_email_option label="Envoi d'e-mails" default_empty="— Désactivé —"}
		</dl>
		<dl class="email-smtp">
			{input type="text" name="SMTP_HOST" label="Adresse du serveur SMTP" source=$constants required=true}
			{input type="number" name="SMTP_PORT" label="Port du serveur SMTP" source=$constants required=true size=3}
			{input type="text" name="SMTP_USER" label="Nom d'utilisateur du serveur SMTP" source=$constants autocomplete="off"}
			{input type="password" name="SMTP_PASSWORD" label="Mot de passe du serveur SMTP" autocomplete="off"}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>

</form>

<script type="text/javascript">
{literal}
var e = document.querySelector('#f_email');
function changeEmailOption() {
	g.toggle('.email-smtp', e.value === 'smtp');
}
e.onchange = changeEmailOption;
changeEmailOption();
{/literal}
</script>

{include file="_foot.tpl"}