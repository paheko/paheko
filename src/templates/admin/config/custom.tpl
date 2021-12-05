{include file="admin/_head.tpl" title="Personnalisation" current="config"}

{include file="admin/config/_menu.tpl" current="custom"}

{if isset($_GET['ok']) && !$form->hasErrors()}
	<p class="block confirm">
		La configuration a bien été enregistrée.
	</p>
{/if}

{form_errors}

<fieldset>
	<legend>Association et site web</legend>
	<dl>
		<dt>Logo</dt>
		{if $files.logo}
		<dd>
			<img src="{$files.logo->thumb_url()}" alt="" />
		</dd>
		{/if}
		<dd>
			{linkbutton href="!config/edit_file.php?k=%s"|args:'logo' label="Modifier" shape="edit" target="_dialog"}
		</dd>
		<dd class="help">
			Ce logo sera affiché en haut du menu de l'administration, sur le site web, et comme icône sur l'écran d'accueil des téléphones portables.
		</dd>
		<dt>Icône de favori (favicon)</dt>
		{if $files.favicon}
		<dd>
			<img src="{$files.favicon->url()}" alt="" />
		</dd>
		{/if}
		<dd>
			{linkbutton href="!config/edit_file.php?k=%s"|args:'favicon' label="Modifier" shape="edit" target="_dialog"}
		</dd>
		<dd class="help">
			Cette image sera affichée dans l'onglet du navigateur.
		</dd>
	</dl>
</fieldset>

<form method="post" action="{$self_url}">
	<fieldset>
		<legend>Interface d'administration</legend>
		<dl>
			{input type="color" pattern="#[a-f0-9]{6}" title="Couleur au format hexadécimal" default=$color1 source=$config name="couleur1" label="Couleur primaire" placeholder=$color1}
			{input type="color" pattern="#[a-f0-9]{6}" title="Couleur au format hexadécimal" default=$color2 source=$config name="couleur2" label="Couleur secondaire" placeholder=$color2}
			{input type="file" label="Image de fond" name="background" help="Il est conseillé d'utiliser une image en noir et blanc avec un fond blanc pour un meilleur rendu. Dimensions recommandées : 380x200" accept="image/*,*.jpeg,*.jpg,*.png,*.gif"}
			<dt>Texte de la page d'accueil</dt>
			<dd>
				{linkbutton href="!config/edit_file.php?k=%s"|args:'admin_homepage' label="Modifier" shape="edit" target="_dialog" data-dialog-height="90%"}
			</dd>
			<dd class="help">
				Ce contenu sera affiché à la connexion d'un membre, ou en cliquant sur l'onglet 'Accueil' du menu de gauche.
			</dd>
			<dt>Personnalisation CSS de l'administration</dt>
			<dd>
				{linkbutton href="!config/edit_file.php?k=%s"|args:'admin_css' label="Modifier" shape="edit" target="_dialog" data-dialog-height="90%"}
			</dd>
			<dd class="help">
				Permet de rajouter des <a href="https://developer.mozilla.org/fr/docs/Learn/CSS/First_steps" target="_blank">règles CSS</a> qui modifieront l'apparence de l'interface d'administration.
			</dd>		</dl>
		<input type="hidden" name="admin_background" id="f_admin_background" data-current="{$background_image_current}" data-default="{$background_image_default}" value="{$_POST.admin_background}" />

		<p class="submit">
			{csrf_field key="config_custom"}
			{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
		</p>
	</fieldset>


</form>

{include file="admin/_foot.tpl"}