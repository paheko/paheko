{include file="admin/_head.tpl" title="Configuration" current="config"}

{include file="admin/config/_menu.tpl" current="index"}

{if isset($_GET['ok']) && !$form->hasErrors()}
	<p class="block confirm">
		La configuration a bien été enregistrée.
	</p>
{/if}

{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Garradin</legend>
		<dl>
			<dt>Version installée</dt>
			<dd>{$garradin_version}</dd>
			{if !CONTRIBUTOR_LICENSE}
			<dd class="help">
				Le développement et le support de Garradin ne sont possibles que grâce à votre soutien&nbsp;!<br />
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
			{if ENABLE_TECH_DETAILS}
			<dt>Informations système</dt>
			<dd class="help">
				Version PHP&nbsp;: {$php_version}<br />
				Version SQLite&nbsp;: {$sqlite_version}<br />
				Heure du serveur&nbsp;: {$server_time|date}<br />
				Chiffrement GnuPG&nbsp;: {if $has_gpg_support}disponible, module activé{else}non, module PHP gnupg non installé&nbsp;?{/if}<br />
			</dd>
			{/if}
		</dl>
	</fieldset>

	<fieldset>
		<legend>Informations sur l'association</legend>
		<dl>
			{input type="text" name="nom_asso" required=true source=$config label="Nom"}
			{input type="email" name="email_asso" required=true source=$config label="Adresse e-mail de contact"}
			{input type="textarea" name="adresse_asso" source=$config label="Adresse postale"}
			{input type="tel" name="telephone_asso" source=$config label="Numéro de téléphone"}
			{input type="url" name="site_asso" source=$config label="Site web" help="Si vous n'utilisez pas la fonctionnalité site web de Garradin"}
		</dl>
	</fieldset>

	<fieldset>
		<legend>Localisation</legend>
		<dl>
			{input type="text" name="monnaie" required=true source=$config label="Monnaie" help="Inscrire ici la devise utilisée : €, CHF, XPF, etc." size="3"}
			{input type="select" name="pays" required=true source=$config label="Pays" options=$countries}
		</dl>
	</fieldset>

	<fieldset>
		<legend>Membres</legend>
		<dl>
			{input type="select" name="categorie_membres" source=$config options=$membres_cats required=true label="Catégorie par défaut des nouveaux membres"}
			{input type="select" name="champ_identite" source=$config options=$champs required=true label="Champ utilisé pour définir l'identité des membres" help="Ce champ des fiches membres sera utilisé comme identité du membre dans les emails, les fiches, les pages, etc."}
			{input type="select" name="champ_identifiant" source=$config options=$champs required=true label="Champ utilisé comme identifiant de connexion" help="Ce champ des fiches membres sera utilisé comme identifiant pour se connecter à Garradin. Ce champ doit être unique (il ne peut pas contenir deux membres ayant la même valeur dans ce champ)."}
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

{include file="admin/_foot.tpl"}