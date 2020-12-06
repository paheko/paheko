{include file="admin/_head.tpl" title="Changement de mot de passe"}


{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Modifier mon mot de passe</legend>
		<dl>
			<dt><label for="f_passe_membre">Mot de passe</label> (minimum {$password_length} caractères) <b title="(Champ obligatoire)">obligatoire</b></dt>
			<dd class="help">
				Astuce : un mot de passe de quatre mots choisis au hasard dans le dictionnaire est plus sûr 
				et plus simple à retenir qu'un mot de passe composé de 10 lettres et chiffres.
			</dd>
			<dd class="help">
				Pas d'idée&nbsp;? Voici une suggestion choisie au hasard :
				<input type="text" readonly="readonly" title="Cliquer pour utiliser cette suggestion comme mot de passe" id="pw_suggest" value="{$passphrase}" autocomplete="off" />
			</dd>
			<dd><input type="password" name="passe" id="f_passe_membre" value="{form_field name=passe}" pattern="{$password_pattern}" required="required" autocomplete="new-password" /></dd>
			<dt><label for="f_repasse_membre">Encore le mot de passe</label> (vérification) <b title="(Champ obligatoire)">obligatoire</b></dt>
			<dd><input type="password" name="passe_confirmed" id="f_repasse_membre" value="{form_field name=passe_confirmed}" pattern="{$password_pattern}" required="required" autocomplete="new-password" /></dd>
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key="changePassword"}
		{button type="submit" name="change" label="Modifier le mot de passe" shape="right" class="main"}
	</p>


	<script type="text/javascript">
	{literal}
	g.script('scripts/password.js', () => {
		initPasswordField('pw_suggest', 'f_passe', 'f_repasse');
	});
	{/literal}
	</script>
</form>


{include file="admin/_foot.tpl"}