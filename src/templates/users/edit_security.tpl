{include file="_head.tpl" title="%s — Options de connexion"|args:$user->name() current="users"}

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">
	{*
	<!-- This is to avoid chrome autofill, Chrome developers you suck -->
	<input type="text" class="hidden" name="{$login_field}" />
	<input type="password" class="hidden" name="password" />
	<fieldset>
		<legend>{if $user.password}Changer le mot de passe{else}Définir un mot de passe{/if}</legend>
		{include file="users/_password_form.tpl"}
		<dl>
		</dl>
	</fieldset>
	*}

	{if $user.password}
	<fieldset>
		<legend>Options de connexion</legend>
		<dl>
		{if $user.otp_secret}
			{input type="checkbox" name="otp_delete" value="1" label="Désactiver l'authentification à double facteur TOTP"}
		{/if}
		{if $user.pgp_key}
			{input type="checkbox" name="pgp_key" value="" label="Supprimer la clé PGP associée au membre"}
		{/if}
		{if $user.password}
			{input type="checkbox" name="password_delete" label="Supprimer le mot de passe de ce membre" value=1}
			<dd class="help">
				Pour pouvoir se reconnecter, il devra utiliser le formulaire "Première connexion" afin de créer un nouveau mot de passe.
			</dd>
		{/if}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>
	{else}
	<div class="alert block">
		<p>Ce membre ne dispose pas de mot de passe.</p>
		<p>Les membres peuvent créer leur mot de passe avec le formulaire "Première connexion" présent sur l'écran de connexion à l'association.</p>
		<p>Un e-mail leur sera envoyé, leur permettant ensuite de choisir un mot de passe.</p>
	</div>
	{/if}
</form>

{include file="_foot.tpl"}