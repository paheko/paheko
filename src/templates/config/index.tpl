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
			<dd>Paheko {$paheko_version}</dd>
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
					{linkbutton shape="export" href=$paheko_website label="Télécharger la mise à jour" target="_blank"}
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
			{input type="email" name="org_email" required=true source=$config label="Adresse e-mail de contact" help="Cette adresse est aussi utilisée comme adresse d'expédition des messages collectifs."}
			{input type="textarea" name="org_address" source=$config label="Adresse postale"}
			{input type="tel" name="org_phone" source=$config label="Numéro de téléphone"}
			{input type="textarea" cols="50" rows="2" name="org_infos" required=false source=$config label="Informations diverses" help="Ce champ sera utilisé sur les reçus. Il peut être utile de faire figurer ici le numéro de SIRET par exemple."}
		</dl>
	</fieldset>

	<fieldset>
		<legend>Site web</legend>
		<p class="help">
			Cette option permet d'activer ou désactiver la visibilité publique du site web intégré à Paheko.<br/>
			En désactivant le site public, les visiteurs seront automatiquement redirigés vers la page de connexion.<br />
			Vous pourrez toujours y publier des informations, mais celles-ci ne seront visibles que pour les membres connectés.
		</p>
		<dl>
			{input type="radio" name="site_disabled" value=0 source=$config label="Activer le site web public"}
			{input type="radio" name="site_disabled" value=1 source=$config label="Désactiver le site web"}
		</dl>
		<div class="external-web">
			<p class="help">Si vous avez déjà un site web à une autre adresse, vous pouvez l'indiquer ici&nbsp;:</p>
			<dl>
				{input type="url" name="org_web" source=$config label="Site web externe"}
			</dl>
		</div>
	</fieldset>

	<fieldset>
		<legend>Localisation</legend>
		<dl>
			{input type="text" name="currency" required=true source=$config label="Monnaie" help="Inscrire ici la devise utilisée : €, CHF, XPF, etc." size="3"}
			{input type="select" name="country" required=true source=$config label="Pays" options=$countries}
		</dl>
	</fieldset>

	{if !FILE_VERSIONING_POLICY}
	<fieldset>
		<legend>Conservation des anciennes versions des documents</legend>
		<p class="help">
			Pour éviter de perdre un travail précieux en cas de maladresse, les anciennes versions des documents peuvent être conservées.<br />
			Lorsqu'un fichier est modifié, l'ancienne version est archivée.<br />
			Note&nbsp;: seuls les documents et fichiers joints aux membres et écritures sont versionnés.
		</p>
		<dl class="minor">
			<dt><strong>Conservation des anciennes versions</strong></dt>
			{foreach from=$versioning_policies key="key" item="policy"}
				{input type="radio-btn" name="file_versioning_policy" value=$key default="" source=$config label=$policy.label help=$policy.help}
			{/foreach}
		</dl>
		<dl class="versions">
		{if FILE_VERSIONING_MAX_SIZE}
			<dd class="help">Note : les fichiers de plus de <?=FILE_VERSIONING_MAX_SIZE?> ne seront pas versionnés.</dd>
		{else}
			{input type="number" name="file_versioning_max_size" min=1 label="Taille maximale des fichiers à versionner" source=$config required=true help="Les fichiers qui sont plus gros que cette taille ne seront pas versionnés." suffix="Mo" max=100 size=3}
		{/if}
		</dl>
	</fieldset>
	{/if}

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
function toggleVersions() {
	g.toggle('.versions', $('#f_file_versioning_policy_none').checked ? false : true);
}
toggleVersions();
$('input[name=file_versioning_policy]').forEach((e) => e.onchange = toggleVersions);

function toggleWebInput() {
	g.toggle('.external-web', $('#f_site_disabled_1').checked);
}
toggleWebInput();
$('#f_site_disabled_0').onchange = toggleWebInput;
$('#f_site_disabled_1').onchange = toggleWebInput;
{/literal}
</script>

{include file="_foot.tpl"}