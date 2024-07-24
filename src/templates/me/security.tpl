{include file="_head.tpl" title="Mes informations de connexion et sécurité" current="me"}

{include file="./_nav.tpl" current="security"}

{if $ok}
<p class="block confirm">
	Changements enregistrés.
</p>
{/if}

<dl class="large">
	<dt>Identifiant de connexion</dt>
	<dd class="help">{$id_field.label}</dd>
	<dd>{input type=$id_field.type readonly="readonly" copy=true default=$id name=""}</dd>
	<dt>Mot de passe</dt>
	{if $can_change_password}
		<dd>{linkbutton href="security_password.php" label="Modifier le mot de passe" shape="edit"}</dd>
	{else}
		<dd class="alert">Vous n'avez pas le droit de modifier votre mot de passe. Vous devez contacter un administrateur pour qu'il change votre mot de passe.</dd>
	{/if}
	<dt>Authentification à deux facteurs (TOTP)</dt>
	<dd>
		{if $user.otp_secret}
			<span class="confirm">{icon shape="check"} Activée</span>
			{linkbutton href="security_otp.php" label="Désactiver" shape="delete"}
		{else}
			<span class="alert">Désactivée</span>
			{linkbutton href="security_otp.php" label="Activer" shape="check"}
		{/if}
	</dd>
	<dd class="help">Permet de protéger votre compte en cas de vol de votre mot de passe, en utilisant votre téléphone pour générer un code à usage unique.</dd>
	{if $user.otp_secret}
		<dt>Codes de secours</dt>
		<dd>
			{if $user.otp_recovery_codes}
				<span class="confirm">{icon shape="check"} Oui</span>
				{linkbutton href="security_otp_recovery.php" label="Voir les codes" shape="eye"}
			{else}
				<span class="alert">Non</span>
				{linkbutton href="security_otp_recovery.php?generate" label="Générer les codes" shape="reload"}
			{/if}
		</dd>
		<dd class="help">Les codes de secours peuvent être utilisés pour récupérer l'accès à votre compte si vous n'avez plus accès à votre téléphone pour le second facteur.</dd>
	{/if}
	{if $can_use_pgp}
		<dt>Chiffrer les e-mails qui me sont envoyés avec PGP</dt>
		<dd>
			{if !$user.pgp_key}
				<span class="alert">Désactivé</span>
				{linkbutton href="security_pgp.php" label="Configurer" shape="edit"}
			{else}
				<span class="confirm">{icon shape="check"} Activé</span>
				{linkbutton href="security_pgp.php" label="Modifier" shape="edit"}
			{/if}
		</dd>
		{if $pgp_fingerprint}
			<dd>L'empreinte de votre clé publique est&nbsp;: <code>{$pgp_fingerprint}</code></dd>
		{/if}
		<dd class="help">Permet de chiffrer les messages qui vous sont envoyés par e-mail, notamment les messages de récupération de mot de passe, pour empêcher un attaquant de prendre contrôle de votre compte si votre adresse e-mail est piratée.</dd>
	{/if}
	<dt>Déconnecter toutes mes sessions</dt>
	<dd>{{Vous n'avez actuellement qu'une seule session ouverte (celle-ci).}{Vous avez actuellement %n sessions ouvertes (y compris celle-ci).} n=$sessions_count}</dd>
	<dd>{linkbutton href="!logout.php?all" label="Me déconnecter de toutes les sessions" shape="logout"}</dd>
	<dt>Journal de connexion</dt>
	<dd>Permet de voir les tentatives de connexion, les modifications de mot de passe, etc.</dd>
	<dd>{linkbutton href="!users/log.php" label="Voir mon journal de connexion" shape="menu"}</dd>
</dl>

{include file="_foot.tpl"}