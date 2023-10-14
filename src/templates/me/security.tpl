{include file="_head.tpl" title="Mes informations de connexion et sécurité" current="me"}

{include file="./_nav.tpl" current="security"}

{if $ok}
<p class="block confirm">
	Changements enregistrés.
</p>
{/if}

{form_errors}

{if $edit}
	<form method="post" action="{$self_url}" data-focus="1">

	{if $edit == 'password'}
		<fieldset>
			<legend>Changer mon mot de passe</legend>
			{include file="users/_password_form.tpl" required=true}
		</fieldset>
	{elseif $edit == 'otp'}
		<p class="block alert">
			Confirmez l'activation de l'authentification à double facteur TOTP en l'utilisant une première fois.
		</p>

		<p class="help">Pour renforcer la sécurité de votre connexion en cas de vol de votre mot de passe, vous pouvez activer l'authentification à double facteur. Cela nécessite d'installer une application comme <a href="https://getaegis.app/" target="_blank">Aegis</a> ou Google Authenticator sur votre téléphone.</p>

		<fieldset>
			<legend>Confirmer l'activation de l'authentification à double facteur (2FA)</legend>
			<img class="qrcode" src="{$otp.qrcode}" alt="" />
			<dl>
				<dt>Votre clé secrète est&nbsp;:</dt>
				<dd class="help">{input name="otp_secret" default=$otp.secret_display type="text" readonly="readonly" copy=true onclick="this.select();"}</code></dd>
				<dd class="help">Recopiez la clé secrète ou scannez le QR code pour configurer votre application TOTP, puis utilisez celle-ci pour générer un code d'accès et confirmer l'activation.</dd>
				{input name="otp_code" type="text" class="otp" minlength=6 maxlength=6 label="Code TOTP" help="Entrez ici le code donné par l'application d'authentification double facteur." required=true}
			</dl>
		</fieldset>
	{elseif $edit == 'otp_disable'}
		<p class="block alert">
			Confirmez la désactivation de l'authentification à double facteur TOTP.
		</p>
		<input type="hidden" name="otp_disable" value="1" />
	{elseif $edit == 'pgp_key'}
		<fieldset>
			<legend>Chiffrer les e-mails qui me sont envoyés avec PGP/GnuPG</legend>
			<p class="help">En inscrivant ici votre clé publique, tous les e-mails qui vous seront envoyés seront chiffrés (cryptés) avec cette clé&nbsp;: messages collectifs, messages envoyés par les membres, rappels de cotisation, procédure de récupération de mot de passe, etc.</p>
			<dl>
				{input name="pgp_key" source=$user label="Ma clé publique PGP" type="textarea" cols=90 rows=5 required=true help="Laisser vide pour désactiver le chiffrement."}
				{if $pgp_fingerprint}<dd class="help">L'empreinte de la clé est&nbsp;: <code>{$pgp_fingerprint}</code></dd>{/if}
			</dl>
			<p class="block alert">
				Attention&nbsp;: en inscrivant ici votre clé PGP, les emails de récupération de mot de passe perdu vous seront envoyés chiffrés
				et ne pourront donc être lus si vous n'avez pas le le mot de passe protégeant la clé privée correspondante.
			</p>
		</fieldset>
	{/if}

	<fieldset>
		<legend>Confirmation</legend>
		<dl>
			{input type="password" name="password_check" label="Mot de passe actuel" help="Entrez votre mot de passe actuel pour confirmer les changements." autocomplete="current-password" required=true}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="confirm" label="Confirmer" shape="right" class="main"}
	</p>

	</form>
{else}
	<dl class="large">
		<dt>Identifiant de connexion</dt>
		<dd class="help">{$id_field.label}</dd>
		<dd>{input type=$id_field.type readonly="readonly" copy=true default=$id name=""}</dd>
		<dt>Mot de passe</dt>
		{if $can_change_password}
			<dd>{linkbutton href="?edit=password" label="Modifier le mot de passe" shape="edit"}</dd>
		{else}
			<dd class="alert">Vous n'avez pas le droit de modifier votre mot de passe. Vous devez contacter un administrateur pour qu'il change votre mot de passe.</dd>
		{/if}
		<dt>Authentification à deux facteurs</dt>
			<dd>
			{if $user.otp_secret}
				<span class="confirm">{icon shape="check"} Activée</span>
				{linkbutton href="?edit=otp_disable" label="Désactiver" shape="delete"}
			{else}
				<span class="alert">Désactivée</span>
				{linkbutton href="?edit=otp" label="Activer" shape="check"}
			{/if}
			</dd>
		<dd class="help">Permet de protéger votre compte en cas de vol de votre mot de passe, en utilisant votre téléphone pour générer un code à usage unique.</dd>
		{if $can_use_pgp}
			<dt>Chiffrer les e-mails qui me sont envoyés avec PGP</dt>
			<dd>
				{if !$user.pgp_key}
					<span class="alert">Désactivé</span>
					{linkbutton href="?edit=pgp_key" label="Configurer" shape="edit"}
				{else}
					<span class="confirm">{icon shape="check"} Activé</span>
					{linkbutton href="?edit=pgp_key" label="Modifier" shape="edit"}
				{/if}
			</dd>
			<dd class="help">Permet de chiffrer les messages qui vous sont envoyés par e-mail, notamment les messages de récupération de mot de passe, pour empêcher un attaquant de prendre contrôle de votre compte si votre adresse e-mail est piratée.</dd>
		{/if}
		<dt>Déconnecter toutes mes sessions</dt>
		<dd>{{Vous n'avez actuellement qu'une seule session ouverte (celle-ci).}{Vous avez actuellement %n sessions ouvertes (y compris celle-ci).} n=$sessions_count}</dd>
		<dd>{linkbutton href="!logout.php?all" label="Me déconnecter de toutes les sessions" shape="logout"}</dd>
		<dt>Journal de connexion</dt>
		<dd>Permet de voir les tentatives de connexion, les modifications de mot de passe, etc.</dd>
		<dd>{linkbutton href="!users/log.php" label="Voir mon journal de connexion" shape="menu"}</dd>
	</dl>
{/if}


{include file="_foot.tpl"}