{include file="_head.tpl" title="%s — Modifier le mot de passe"|args:$user->name() current="users"}

{form_errors}

<form method="post" action="{$self_url}" data-focus="1">
	<!-- This is to avoid chrome autofill, Chrome developers you suck -->
	<input type="text" class="hidden" name="{$login_field}" />
	<input type="password" class="hidden" name="password" />

	<fieldset>
		<legend>{if $user.password}Changer le mot de passe{else}Définir un mot de passe{/if}</legend>
		{include file="users/_password_form.tpl"}
		<dl>
		{if $user.password}
			{input type="checkbox" name="password_delete" label="Supprimer le mot de passe de ce membre" value=1}
		{/if}
		</dl>
	</fieldset>

	{if $user.otp_secret || $user.pgp_key}
	<fieldset>
		<legend>Options de sécurité</legend>
		<dl>
		{if $user.otp_secret}
			{input type="checkbox" name="otp_delete" value="1" label="Désactiver l'authentification à double facteur TOTP"}
		{/if}
		{if $user.pgp_key}
			{input type="checkbox" name="pgp_key" value="" label="Supprimer la clé PGP associée au membre"}
		{/if}
		</dl>
	</fieldset>
	{/if}

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>
</form>

{include file="_foot.tpl"}