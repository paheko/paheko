{include file="_head.tpl" title="Connexion"}

{form_errors}

{if $changed}
	<p class="block confirm">
		Votre mot de passe a bien été modifié.<br />
		Vous pouvez maintenant l'utiliser pour vous reconnecter.
	</p>
{/if}

<p class="block error" style="display: none;" id="old_browser">
	Le navigateur que vous utilisez n'est pas supporté. Des fonctionnalités peuvent ne pas fonctionner.<br />
	Merci d'utiliser un navigateur web moderne comme <a href="https://www.getfirefox.com/" target="_blank">Firefox</a> ou <a href="https://vivaldi.com/fr/" target="_blank">Vivaldi</a>.
</p>

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>
			{if $ssl_enabled}
				<span class="confirm">{icon shape="lock"} Connexion sécurisée</span>
			{else}
				<span class="alert">{icon shape="unlock"} Connexion non-sécurisée</span>
			{/if}
		</legend>
		<dl>
			{input type=$id_field.type label=$id_field.label required=true name="id"}
			{input type="password" name="password" label="Mot de passe" required=true}
			{input type="checkbox" name="permanent" value="1" label="Rester connecté⋅e" help="recommandé seulement sur ordinateur personnel"}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key="login"}
		{button type="submit" name="login" label="Se connecter" shape="right" class="main"}
		{linkbutton href="!password.php" label="Mot de passe perdu ?" shape="help"}
		{linkbutton href="!password.php?new" label="Première connexion ?" shape="user"}
	</p>

</form>

{literal}
<script type="text/javascript" async="async">
if (window.navigator.userAgent.match(/MSIE|Trident\/|Edge\//)) {
	document.getElementById('old_browser').style.display = 'block';
}
</script>
{/literal}

{include file="_foot.tpl"}