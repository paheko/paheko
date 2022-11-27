{include file="_head.tpl" title="Connexion — double facteur" current="login"}

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">

	<fieldset>
		<legend>Authentification à double facteur</legend>
		<dl>
			{input type="text" class="otp" minlength=6 maxlength=6 label="Code TOTP" name="code" help="Entrez ici le code donné par l'application d'authentification double facteur." required=true}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="login" label="Se connecter" shape="right" class="main"}
	</p>

</form>

{include file="_foot.tpl"}