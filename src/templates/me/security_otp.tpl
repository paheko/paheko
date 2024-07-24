{include file="_head.tpl" title="Double authentification" current="me"}

{include file="./_nav.tpl" current="security"}

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">
{if !$user.otp_secret}
	<p class="block alert">
		Confirmez l'activation de la double authentification en l'utilisant une première fois.
	</p>

	<p class="help block">Pour renforcer la sécurité de votre connexion en cas de vol de votre mot de passe, vous pouvez activer la double authentification (aussi appelée second facteur, double facteur, OTP, ou 2FA). Cela nécessite d'installer une application comme <a href="https://getaegis.app/" target="_blank">Aegis</a> sur votre téléphone pour générer des codes à usage unique.</p>

	<fieldset>
		<legend>Confirmer l'activation de la double authentification</legend>
		<img class="qrcode" src="{$otp.qrcode}" alt="" />
		<dl>
			<dt>Votre clé secrète est&nbsp;:</dt>
			<dd class="help">{input name="otp_secret" default=$otp.secret_display type="text" readonly="readonly" copy=true onclick="this.select();"}</code></dd>
			<dd class="help">Recopiez la clé secrète ou scannez le QR code pour configurer votre application TOTP, puis utilisez celle-ci pour générer un code d'accès et confirmer l'activation.</dd>
			{input name="otp_code" type="text" class="otp" minlength=6 maxlength=6 label="Code TOTP" help="Entrez ici le code donné par l'application d'authentification double facteur." required=true}
		</dl>
	</fieldset>

	{include file="./_security_confirm_password.tpl"}
{else}
	<p class="block alert">
		Confirmez la désactivation de l'authentification à double facteur TOTP.
	</p>
	{include file="./_security_confirm_password.tpl"}
{/if}
</form>

{include file="_foot.tpl"}