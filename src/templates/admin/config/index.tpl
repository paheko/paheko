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
			<dd class="help">{$garradin_version}</dd>
			{if $new_version}
			<dd><p class="block alert">
				Une nouvelle version <strong>{$new_version}</strong> est disponible !<br />
				<a href="{$garradin_website}" target="_blank">Aller télécharger la nouvelle version</a>
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

	<fieldset>
		<legend>Personnalisation</legend>
		<dl>
			<dt>Texte de la page d'accueil</dt>
			<dd>
				{linkbutton href="!config/edit_file.php?k=%s"|args:'admin_homepage' label="Modifier" shape="edit" target="_dialog" data-dialog-height="90%"}
			</dd>
			<dd class="help">
				Ce contenu sera affiché à la connexion d'un membre, ou en cliquant sur l'onglet 'Accueil' du menu de gauche.
			</dd>
			{input type="color" pattern="#[a-f0-9]{6}" title="Couleur au format hexadécimal" default=$color1 source=$config name="couleur1" label="Couleur primaire" placeholder=$color1}
			{input type="color" pattern="#[a-f0-9]{6}" title="Couleur au format hexadécimal" default=$color2 source=$config name="couleur2" label="Couleur secondaire" placeholder=$color2}
			{input type="file" label="Image de fond" name="background" help="Il est conseillé d'utiliser une image en noir et blanc avec un fond blanc pour un meilleur rendu. Dimensions recommandées : 380x200" accept="image/*,*.jpeg,*.jpg,*.png,*.gif"}
			<dt>Personnalisation CSS de l'administration</dt>
			<dd>
				{linkbutton href="!config/edit_file.php?k=%s"|args:'admin_css' label="Modifier" shape="edit" target="_dialog" data-dialog-height="90%"}
			</dd>
			<dd class="help">
				Permet de rajouter des <a href="https://developer.mozilla.org/fr/docs/Learn/CSS/First_steps" target="_blank">règles CSS</a> qui modifieront l'apparence de l'interface d'administration.
			</dd>
		</dl>
		<input type="hidden" name="admin_background" id="f_admin_background" data-current="{$background_image_current}" data-default="{$background_image_default}" value="{$_POST.admin_background}" />
	</fieldset>

	<p class="submit">
		{csrf_field key="config"}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>

</form>

{include file="admin/_foot.tpl"}