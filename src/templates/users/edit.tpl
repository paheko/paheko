{include file="_head.tpl" title="%s — Modifier le membre"|args:$user->name() current="users"}

<nav class="tabs">
	{linkbutton href="details.php?id=%d"|args:$user.id label="Retour à la fiche membre" shape="left"}
</nav>

{form_errors}

<form method="post" action="{$self_url}" data-focus="dl [name]">
	<!-- This is to avoid chrome autofill, Chrome developers you suck -->
	<input type="text" style="display: none;" name="{$login_field}" />
	<input type="password" style="display: none;" name="password" />

	<fieldset>
		<legend>Informations personnelles</legend>
		<dl>
			{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)}
					{input type="select" name="id_category" label="Catégorie du membre" required=true source=$user options=$categories}
			{/if}

			{if !$user->is_parent}
				{input type="list" name="id_parent" label="Rattacher à un membre" target="!users/selector.php?no_children=1" help="Permet de regrouper les personnes d'un même foyer par exemple. Sélectionner ici le membre responsable." default=$user->getParentSelector() can_delete=true}
			{/if}

			{foreach from=$fields item="field"}
				{edit_dynamic_field field=$field user=$user context="edit"}
			{/foreach}
		</dl>
	</fieldset>

	<fieldset>
		<legend>{if $user.password}Changer le mot de passe{else}Choisir un mot de passe{/if}</legend>
		<p class="help">
		{if $user.password}
			Ce membre a déjà un mot de passe, mais vous pouvez le changer si besoin.
		{else}
			Ce membre n'a pas encore de mot de passe et ne peut donc se connecter.
		{/if}
		</p>
		<dl>
		{include file="users/_password_form.tpl"}
		{if $user.password}
			{input type="checkbox" name="delete_password" label="Supprimer le mot de passe de ce membre" value=1}
		{/if}
		</dl>
	</fieldset>

	{if $user.otp_secret || $user.pgp_key}
	<fieldset>
		<legend>Options de sécurité</legend>
		<dl>
		{if $user.otp_secret}
			{input type="checkbox" name="clear_otp" value="1" label="Désactiver l'authentification à double facteur TOTP"}
		{/if}
		{if $user.pgp_key}
			{input type="checkbox" name="clear_pgp" value="1" label="Supprimer la clé PGP associée au membre"}
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