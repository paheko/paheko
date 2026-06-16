{if $user->isOTPRequired()}
	{include file="_head.tpl" title="Activer la double authentification" current="me" layout="public" hide_title=true}
{else}
	{include file="_head.tpl" title="Double authentification" current="me"}
	{include file="./_nav.tpl" current="security"}
{/if}


{form_errors}

<form method="post" action="{$self_url}" data-focus="1">
{if !$user.otp_secret}
	{if $user->isOTPRequired()}
		<p class="block alert">
			Vous devez configurer l'authentification à double facteur pour pouvoir vous connecter.
		</p>
	{else}
		<p class="block alert">
			Confirmez l'activation de la double authentification en l'utilisant une première fois.
		</p>
	{/if}

	<div class="help block">
		<p>La double authentification (aussi appelée second facteur, double facteur, OTP, ou 2FA) permet d'empêcher la connexion en cas de vol de votre mot de passe.</p>
		<p>Vous aurez besoin d'installer une application comme <a href="https://getaegis.app/" target="_blank">Aegis</a> sur votre téléphone pour générer des codes à usage unique.</p>
	</div>

	<fieldset>
		<legend>Confirmer l'activation de la double authentification</legend>
		<img class="qrcode" src="{$otp.qrcode}" alt="" width="256" />
		<dl>
			<dt>Votre clé secrète est&nbsp;:</dt>
			<dd class="help">{input name="otp_secret" default=$otp.secret_display type="text" readonly="readonly" copy=true onclick="this.select();"}</code></dd>
			<dd class="help">Recopiez la clé secrète ou scannez le QR code pour configurer votre application TOTP, puis utilisez celle-ci pour générer un code d'accès et confirmer l'activation.</dd>
			{input name="otp_code" type="text" class="otp" minlength=6 maxlength=6 label="Code TOTP" help="Entrez ici le code donné par l'application de double authentification." required=true pattern="\d{6}" inputmode="numeric" autocomplete="off"}
		</dl>
	</fieldset>

	{if $user->isOTPRequired()}
		<p class="submit">
			{csrf_field key=$csrf_key}
			{button type="submit" name="enable" label="Confirmer" shape="right" class="main"}
		</p>
	{else}
		{include file="./_security_confirm_password.tpl" name="enable"}
	{/if}
{else}
	{include file="./_security_confirm_password.tpl" warning="Confirmez la désactivation de la double authentification" name="disable"}
{/if}
</form>

{include file="_foot.tpl"}