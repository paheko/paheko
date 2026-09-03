{include file="_head.tpl" title="Accéder aux documents avec une application" current="docs"}

<div class="help block">
	<h3>Utiliser une application NextCloud, ownCloud ou OpenCloud</h3>
	<p>
		Paheko est compatible avec les applications <a href="https://nextcloud.com/fr/installer/" target="_blank">NextCloud Files</a>, <a href="https://owncloud.com/mobile-apps/" target="_blank">ownCloud</a> et <a href="https://opencloud.eu/en/product/download-apps" target="_blank">OpenCloud</a>.
	</p>
	<p>
		Elles permettent de synchroniser les fichier sur votre mobile, tablette ou ordinateur&nbsp;: même si la connexion internet ne fonctionne plus, vous avez toujours accès aux fichiers, le logiciel synchronisera les changements plus tard. C'est équivalent à Dropbox, Google Drive, ou OneDrive.
	</p>
	<p>
		Pour cela il suffit d'indiquer cette adresse au lancement de l'application&nbsp;:<br />
		{copy_button label=$www_url}
	</p>
</div>

<div class="help block">
	<h3>Accéder aux fichiers avec WebDAV</h3>
	<p>
		Le protocole WebDAV vous permet d'accéder aux fichiers directement depuis un ordinateur ou un smartphone, simplement comme s'ils étaient sur une clé USB branchée à l'ordinateur (partage distant).
	</p>
	<p>
		Vous aurez besoin d'indiquer l'adresse suivante dans votre application WebDAV&nbsp;:<br />
		{copy_button label=$dir->webdav_root_url()}
	</p>
	<p>
		{linkbutton shape="help" href=HELP_PATTERN_URL|args:"webdav" label="Aide détaillée sur la mise en place d'une application WebDAV" target="_blank"}
	</p>
</div>

{if $logged_user.otp_secret}
<div class="alert block">
	<h3>Vous avez activé la double authentification</h3>
	<p class="help">Votre mot de passe ne peut pas être utilisé pour accéder aux documents avec une application. Il vous faut créer un mot de passe spécifique à votre application pour continuer à accéder aux fichiers. C'est nécessaire pour toutes les applications, sauf NextCloud.</p>
	<p>
		{linkbutton href="!me/security_apps.php" label="Gérer les mots de passe pour applications" shape="menu"}
	</p>
</div>
{/if}