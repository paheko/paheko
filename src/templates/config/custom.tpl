{include file="_head.tpl" title="Personnalisation" current="config"}

{include file="config/_menu.tpl" current="custom"}

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
		{if $url = $config->fileURL('logo', '150px')}
		<dd>
			<img src="{$url}" alt="" />
		</dd>
		{/if}
		<dd>
			{linkbutton href="!config/edit_file.php?k=%s"|args:'logo' label="Modifier" shape="edit" target="_dialog"}
		</dd>
		<dd class="help">
			Ce logo sera affiché en haut du menu de l'administration, sur le site web et sur les documents imprimés.
		</dd>
		<dt>Petite icône</dt>
		{if $url = $config->fileURL('favicon')}
		<dd>
			<img src="{$url}" alt="" />
		</dd>
		{/if}
		<dd>
			{linkbutton href="!config/edit_file.php?k=%s"|args:'favicon' label="Modifier" shape="edit" target="_dialog"}
		</dd>
		<dd class="help">
			Cette image sera affichée dans l'onglet du navigateur (favicon).
		</dd>
		<dt>Grande icône</dt>
		{if $url = $config->fileURL('icon', '150px')}
		<dd class="image-preview">
			<img src="{$url}" alt="" />
			<figure class="masked-icon" title="Aperçu de l'icône sur téléphone">
				<span class="icon"><img src="{$url}" alt="" /></span>
				<figcaption>{$config.org_name|truncate:12:'…':true}</figcaption>
			</figure>
		</dd>
		{/if}
		<dd>
			{linkbutton href="!config/edit_file.php?k=%s"|args:'icon' label="Modifier" shape="edit" target="_dialog"}
		</dd>
		<dd class="help">
			Cette image sera utilisée comme icône de l'application mobile (à installer depuis {link href="!" label="la page d'accueil"} et le bouton «&nbsp;Installer comme application sur l'écran d'accueil&nbsp;»).
		</dd>
		<dt>Signature ou tampon de l'association</dt>
		{if $url = $config->fileURL('signature', '150px')}
		<dd>
			<img src="{$url}" alt="" />
		</dd>
		{/if}
		<dd>
			{linkbutton href="!config/edit_file.php?k=%s"|args:'signature' label="Modifier" shape="edit" target="_dialog"}
		</dd>
		<dd class="help">
			Cette image sera utilisée dans les documents générés pour l'association.<br />
			<strong>Attention&nbsp;:</strong> ne pas mettre la vraie signature d'une personne physique, car <strong>tous les membres connectés</strong> ont accès à cette image. Il est conseillé de créer une signature ou un tampon spécifique à l'association.
		</dd>
	</dl>
</fieldset>

<form method="post" action="{$self_url}">
	<fieldset>
		<legend>Interface d'administration</legend>
		<dl>
			{input type="color" pattern="#[a-f0-9]{6}" title="Couleur au format hexadécimal" default=$color1 source=$config name="color1" label="Couleur primaire" placeholder=$color1}
			{input type="color" pattern="#[a-f0-9]{6}" title="Couleur au format hexadécimal" default=$color2 source=$config name="color2" label="Couleur secondaire" placeholder=$color2}
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

{include file="_foot.tpl"}