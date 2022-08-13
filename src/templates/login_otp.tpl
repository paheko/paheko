{include file="admin/_head.tpl" title="Connexion — double facteur"}

{form_errors}
{show_error if=$fail message="Code incorrect. Vérifiez que votre téléphone est à l'heure."}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Authentification à double facteur</legend>
		<dl>
			{input type="text" minlength=6 maxlength=6 label="Code TOTP" name="code" help="Entrez ici le code donné par l'application d'authentification double facteur." required=true}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="login" label="Se connecter" shape="right" class="main"}
	</p>

</form>

{include file="admin/_foot.tpl"}