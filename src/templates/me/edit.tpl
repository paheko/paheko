{include file="admin/_head.tpl" title="Mes informations personnelles" current="me"}

<nav class="tabs">
	<ul>
		<li class="current"><a href="{$admin_url}me/">Mes informations</a></li>
		<li><a href="{$admin_url}me/security.php">Mot de passe et options de sécurité</a></li>
	</ul>
</nav>

{form_errors}

<form method="post" action="{$self_url}">

	<fieldset>
		<legend>Informations personnelles</legend>
		<dl>
			{foreach from=$fields item="field"}
				{edit_dynamic_field field=$field user=$user context="user_edit"}
			{/foreach}
		</dl>
	</fieldset>

	<fieldset>
		<legend>Changer mon mot de passe</legend>
		<p>{link href="!me/security.php" label="Modifier mon mot de passe ou autres informations de sécurité"}</p>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>

</form>

{include file="admin/_foot.tpl"}