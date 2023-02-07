{include file="_head.tpl" title="Configuration" current="config"}

{include file="config/_menu.tpl" current="index"}

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
			<dd>Paheko {$garradin_version}</dd>
			{if CONTRIBUTOR_LICENSE === null}
			<dd class="help">
				Le développement et le support de Paheko ne sont possibles que grâce à votre soutien&nbsp;!<br />
				{linkbutton href="https://kd2.org/soutien.html" label="Faire un don pour soutenir le développement" target="_blank" shape="export"} :-)
			</dd>
			{/if}
			{if $new_version}
			<dd><p class="block alert">
				Une nouvelle version <strong>{$new_version}</strong> est disponible !<br />
				{if ENABLE_UPGRADES}
					{linkbutton shape="export" href="upgrade.php" label="Mettre à jour"}
				{else}
					{linkbutton shape="export" href=$garradin_website label="Télécharger la mise à jour" target="_blank"}
				{/if}
			</p></dd>
			{/if}
			{if PDF_COMMAND == 'prince'}
			<dd class="help">
				Les PDF sont générés à l'aide du génial logiciel <a href="https://www.princexml.com/" target="_blank">Prince</a>. Merci à eux.
			</dd>
			{/if}
			{if ENABLE_TECH_DETAILS}
			<dt>Informations système</dt>
			<dd class="help">
				Version PHP&nbsp;: {$php_version}<br />
				Version SQLite&nbsp;: {$sqlite_version}<br />
				Heure du serveur&nbsp;: {$server_time|date}<br />
				Chiffrement GnuPG&nbsp;: {if $has_gpg_support}disponible, module activé{else}non, module PHP gnupg non installé&nbsp;?{/if}<br />
			</dd>
			{/if}
			<dt>Espace disque utilisé</dt>
			<dd class="help">
				Base de données et sauvegardes&nbsp;: {$backups_size|size_in_bytes}<br />
				Documents&nbsp;: {$quota_used|size_in_bytes} (sur {$quota_max|size_in_bytes} autorisés)
			</dd>
		</dl>
	</fieldset>

	<fieldset>
		<legend>Informations sur l'association</legend>
		<dl>
			{input type="text" name="org_name" required=true source=$config label="Nom"}
			{input type="email" name="org_email" required=true source=$config label="Adresse e-mail de contact"}
			{input type="textarea" name="org_address" source=$config label="Adresse postale"}
			{input type="tel" name="org_phone" source=$config label="Numéro de téléphone"}
			{if $config.site_disabled}
			{input type="url" name="org_web" source=$config label="Site web"}
			{/if}
			{input type="textarea" cols="50" rows="2" name="org_infos" required=false source=$config label="Informations diverses" help="Ce champ sera utilisé sur les reçus. Il peut être utile de faire figurer ici le numéro de SIRET par exemple."}
		</dl>
	</fieldset>

	<fieldset>
		<legend>Localisation</legend>
		<dl>
			{input type="text" name="currency" required=true source=$config label="Monnaie" help="Inscrire ici la devise utilisée : €, CHF, XPF, etc." size="3"}
			{input type="select" name="country" required=true source=$config label="Pays" options=$countries}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key="config"}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>

</form>

{if ENABLE_TECH_DETAILS}
	<script type="text/javascript" async="async">
	fetch(g.admin_url + 'config/?check_version');
	</script>
{/if}

{include file="_foot.tpl"}