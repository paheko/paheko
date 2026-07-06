{include file="_head.tpl" title="Configuration" current="config"}

{include file="config/_menu.tpl" current="index" sub_current="index"}

{if isset($_GET['ok']) && !$form->hasErrors()}
	<p class="block confirm">
		La configuration a bien été enregistrée.
	</p>
{/if}

{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Informations</legend>
		<dl>
			<dt>Version installée</dt>
			<dd>Paheko {$paheko_version}</dd>
			{if $donate_url}
			<dd class="help">
				Le développement et le support de Paheko ne sont possibles que grâce à votre soutien&nbsp;!<br />
				{linkbutton href=$donate_url label="Faire un don pour soutenir le développement" target="_blank" shape="export"} :-)
			</dd>
			{/if}
			{if $new_version}
			<dd><p class="block alert">
				Une nouvelle version <strong>{$new_version}</strong> est disponible !<br />
				{if ENABLE_UPGRADES}
					{linkbutton shape="export" href="upgrade.php" label="Mettre à jour"}
				{else}
					{linkbutton shape="export" href=$paheko_website label="Télécharger la mise à jour" target="_blank"}
				{/if}
			</p></dd>
			{/if}
			{if PDF_COMMAND == 'prince'}
			<dd class="help">
				Les PDF sont générés à l'aide du génial logiciel <a href="https://www.princexml.com/" target="_blank">Prince</a>. Merci à eux.
			</dd>
			{/if}
			<dt>Informations système</dt>
			<dd>
				Heure du serveur&nbsp;: {$server_time|date}
				<small class="help">(Fuseau horaire : {$server_tz})</small>
			{if ENABLE_TECH_DETAILS}
				<br />Version PHP&nbsp;: {$php_version}<br />
				Version SQLite&nbsp;: {$sqlite_version}<br />
				Chiffrement GnuPG&nbsp;: {if $has_gpg_support}disponible, module activé{else}non, module PHP gnupg non installé&nbsp;?{/if}<br />
				{linkbutton shape="settings" label="Configuration du serveur" href="server/"}
			{/if}
			</dd>
			<dt>Espace disque</dt>
			<dd>
				{linkbutton shape="gallery" label="Voir l'espace disque utilisé" href="disk_usage.php"}
			</dd>
		</dl>
	</fieldset>

	<fieldset>
		<legend>Informations sur l'association</legend>
		<dl>
			{input type="text" name="org_name" required=true source=$config label="Nom"}
			{input type="email" name="org_email" required=true source=$config label="Adresse e-mail de contact" help="Cette adresse est aussi utilisée comme adresse d'expédition des messages collectifs."}
			{input type="textarea" name="org_address" source=$config label="Adresse postale"}
			{input type="textarea" name="org_address_public" source=$config label="Adresse publique" help="Si renseignée, sera utilisée à la place de l'adresse postale sur le site web et dans les e-mails.\nUtile si le lieu d'activité est différent du siège de l'association."}
			{input type="tel" name="org_phone" source=$config label="Numéro de téléphone"}
			{input type="textarea" cols="50" rows="2" name="org_infos" required=false source=$config label="Informations diverses" help="Ce champ sera utilisé sur les reçus. Il peut être utile de faire figurer ici le numéro de SIRET par exemple."}
		</dl>
	</fieldset>

	<fieldset>
		<legend>Localisation</legend>
		<dl>
			{input type="text" name="currency" required=true source=$config label="Devise" pattern="[A-Z]{3}" minlength=3 maxlength=3 help="Inscrire ici la devise utilisée : EUR, CHF, XPF, etc." size="3"}
			{input type="select" name="country" required=true source=$config label="Pays" options=$countries}
			{input type="select" name="timezone" required=true source=$config label="Fuseau horaire" options=$timezones default="Europe/Paris"}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key="config"}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>

</form>

<script type="text/javascript" async="async">
{if ENABLE_TECH_DETAILS}
	fetch(g.admin_url + 'config/?check_version');
{/if}


{literal}
var c = $('#f_country');
c.onchange = () => {
	var s = $('#f_timezone');
	fetch(g.admin_url + 'config/?tzlist=' + c.value).then(r => r.json()).then(j => {
		s.innerHTML = '';
		for (const [key, value] of Object.entries(j.list)) {
			s.appendChild(new Option(value, key));
		}

		if (j.default) {
			s.value = j.default;
		}
		else {
			s.selectedIndex = 0;
		}
	});
};
{/literal}
</script>

{include file="_foot.tpl"}