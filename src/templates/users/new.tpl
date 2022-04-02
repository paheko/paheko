{include file="admin/_head.tpl" title="Ajouter un membre" current="users/add"}

{form_errors}

<form method="post" action="{$self_url}">
	<!-- This is to avoid chrome autofill, Chrome developers you suck -->
	<input type="text" style="display: none;" name="email" />
	{if $id_field_name != 'email'}<input type="text" style="display: none;" name="{$id_field_name}" />{/if}
	<input type="password" style="display: none;" name="password" />

	<fieldset>
		<legend>Informations personnelles</legend>
		<dl>
			{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)}
				{input type="select" name="id_category" label="Catégorie du membre" required=true options=$categories default=$default_category}
			{/if}
			{foreach from=$fields item="field"}
				{edit_dynamic_field context="new" field=$field user=$user}
			{/foreach}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>

</form>

{include file="admin/_foot.tpl"}