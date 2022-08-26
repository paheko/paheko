{include file="_head.tpl" title="Ajouter un membre" current="users/new"}

{form_errors}

<form method="post" action="{$self_url}">

{if $is_duplicate}
	<p class="alert block">
		<strong>Attention :</strong> un membre existe déjà avec ce nom.<br />
		{linkbutton shape="user" href="details.php?id=%d"|args:$is_duplicate label="Voir la fiche du membre existant" target="_dialog"}<br />
		{button shape="right" label="Ce n'est pas un doublon, créer ce membre" name="save" value="anyway" type="submit"}
	</p>
{/if}

	<!-- This is to avoid chrome autofill, Chrome developers you suck -->
	<input type="text" name="email" class="hidden" />
	{if $id_field_name != 'email'}<input type="text" class="hidden" name="{$id_field_name}" />{/if}
	<input type="password" class="hidden" name="password" />

	<fieldset>
		<legend>Informations personnelles</legend>
		<dl>
			{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)}
				{input type="select" name="id_category" label="Catégorie du membre" required=true options=$categories default=$default_category}
			{/if}
			{input type="list" name="id_parent" label="Rattacher à un membre" target="!users/selector.php?no_children=1" help="Permet de regrouper les personnes d'un même foyer par exemple. Sélectionner ici le membre responsable." can_delete=true}
			{foreach from=$fields item="field"}
				{edit_dynamic_field context="new" field=$field user=$user}
			{/foreach}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="save" label="Créer ce membre" shape="right" class="main"}
	</p>

</form>

{include file="_foot.tpl"}