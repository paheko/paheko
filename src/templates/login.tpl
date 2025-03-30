{include file="_head.tpl" title="Connexion" current="login"}

{if DESKTOP_CONFIG_FILE}
<nav class="tabs">
	{linkbutton shape="reset" label="Ouvrir une autre base de données" href="!open_db.php"}
</nav>
{/if}

{form_errors}

{if $changed}
	<p class="block confirm">
		Votre mot de passe a bien été modifié.<br />
		Vous pouvez maintenant l'utiliser pour vous reconnecter.
	</p>
{elseif isset($_GET['logout'])}
	<p class="block confirm">
		Vous avez bien été déconnecté.
	</p>
{/if}

<p class="block error" style="display: none;" id="old_browser">
	Le navigateur que vous utilisez n'est pas supporté. Des fonctionnalités peuvent ne pas fonctionner.<br />
	Merci d'utiliser un navigateur web moderne comme <a href="https://www.getfirefox.com/" target="_blank">Firefox</a> ou <a href="https://vivaldi.com/fr/" target="_blank">Vivaldi</a>.
</p>

<form method="post" action="{$self_url}" data-focus="{if $_POST}2{else}1{/if}">
	{if $app_token}
		<p class="alert block">Une application tiers demande à accéder aux fichiers de l'association.<br />Connectez-vous pour pouvoir confirmer l'accès.</p>
	{elseif $redirect}
		<p class="alert block">Connectez-vous pour accéder à la page demandée.</p>
	{/if}

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
			{if !$app_token}
			{input type="checkbox" name="permanent" value="1" label="Rester connecté⋅e" help="recommandé seulement sur ordinateur personnel"}
			{/if}
		</dl>
	</fieldset>

	{if $captcha}
	<fieldset>
		<legend>Vérification de sécurité</legend>
		<input type="hidden" name="c_hash" value="{$captcha.hash}" />
		<dl>
			<dt><label for="f_c_answer">Merci de recopier en chiffres (par exemple <em>1234</em>) le nombre suivant :<b>(obligatoire)</b></label></dt>
			<dd><tt>{$captcha.spellout}</tt></dd>
			<dd>{input name="c_answer" type="text" maxlength=4 label=null required=true}</dd>
			<dd class="help">Cette vérification est demandée après plusieurs tentatives de connexion infructueuses.</dd>
		</dl>
	</fieldset>
	{/if}

	<p class="submit">
		{csrf_field key="login"}
		{button type="submit" name="login" label="Se connecter" shape="right" class="main"}
		{if !DISABLE_EMAIL && !$app_token}
			{linkbutton href="!password.php" label="Mot de passe perdu ?" shape="help"}
			{linkbutton href="!password.php?new" label="Première connexion ?" shape="user"}
		{/if}
	</p>

	<p class="help">
		Suggestion : mettez cette page dans vos favoris pour la retrouver facilement :-)<br />
		<small>(Sur ordinateur appuyez sur <tt>Ctrl</tt> + <tt>D</tt>. Aide&nbsp;: <a href="https://support.mozilla.org/fr/kb/marque-pages-firefox#w_marquer-une-page" target="_blank">Firefox</a>, <a href="https://support.google.com/chrome/answer/188842?hl=fr&co=GENIE.Platform%3DDesktop&oco=0" target="_blank">Chrome</a>)</small>
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