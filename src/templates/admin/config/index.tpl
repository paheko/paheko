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
				Heure du serveur&nbsp;: {$server_time|date_fr}<br />
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
			{input type="url" name="site_asso" source=$config label="Site web" help="Si vous n'utilisez pas la fonctionnalité site web de Garradin"}
		</dl>
	</fieldset>

	<fieldset>
		<legend>Localisation</legend>
		<dl>
			{input type="text" name="monnaie" required=true source=$config label="Monnaie" help="Inscrire ici la devise utilisée : €, CHF, XPF, etc."}
			{input type="select" name="pays" required=true source=$config label="Pays" options=$countries}
		</dl>
	</fieldset>

	<fieldset>
		<legend>Membres</legend>
		<dl>
			<dt><label for="f_categorie_membres">Catégorie par défaut des nouveaux membres</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
			<dd>
				<select name="categorie_membres" required="required" id="f_categorie_membres">
				{foreach from=$membres_cats key="id" item="nom"}
					<option value="{$id}"{if $config.categorie_membres == $id} selected="selected"{/if}>{$nom}</option>
				{/foreach}
				</select>
			</dd>
			<dt><label for="f_champ_identite">Champ utilisé pour définir l'identité des membres</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
			<dd class="help">Ce champ des fiches membres sera utilisé comme identité du membre dans les emails, les fiches, les pages, etc.</dd>
			<dd>
				<select name="champ_identite" required="required" id="f_champ_identite">
					{foreach from=$champs key="c" item="champ"}
						<option value="{$c}" {form_field selected=$c name="champ_identite" source=$config}>{$champ.title}</option>
					{/foreach}
				</select>
			</dd>
			<dt><label for="f_champ_identifiant">Champ utilisé comme identifiant de connexion</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
			<dd class="help">Ce champ des fiches membres sera utilisé en guise d'identifiant pour se connecter à Garradin. Pour cela le champ doit être unique (pas de doublons).</dd>
			<dd>
				<select name="champ_identifiant" required="required" id="f_champ_identifiant">
					{foreach from=$champs key="c" item="champ"}
						<option value="{$c}" {form_field selected=$c name="champ_identifiant" source=$config}>{$champ.title}</option>
					{/foreach}
				</select>
			</dd>

		</dl>
	</fieldset>

	<fieldset>
		<legend>Personnalisation</legend>
		<dl>
			{*input type="file_editor" name="admin_homepage" source=$config label="Texte de la page d'accueil" help="Ce contenu sera affiché à la connexion d'un membre, ou en cliquant sur l'onglet 'Accueil' du menu de gauche"*}
			{input type="color" pattern="#[a-f0-9]{6}" title="Couleur au format hexadécimal" default=ADMIN_COLOR1 source=$config name="couleur1" label="Couleur primaire" placeholder=ADMIN_COLOR1}
			{input type="color" pattern="#[a-f0-9]{6}" title="Couleur au format hexadécimal" default=ADMIN_COLOR2 source=$config name="couleur2" label="Couleur secondaire" placeholder=ADMIN_COLOR2}
			{input type="file" label="Image de fond" name="background" help="Il est conseillé d'utiliser une image en noir et blanc avec un fond blanc pour un meilleur rendu. Dimensions recommandées : 380x200" accept="image/*,*.jpeg,*.jpg,*.png,*.gif"}
		</dl>
		<input type="hidden" name="image_fond" id="f_image_fond" data-source="{$background_image_source}" data-default="{$background_image_default}" value="{$background_image_current}" />
	</fieldset>

	<p class="submit">
		{csrf_field key="config"}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>

</form>

{include file="admin/_foot.tpl"}