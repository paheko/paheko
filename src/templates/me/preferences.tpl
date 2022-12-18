{include file="_head.tpl" title="Mes préférences" current="me"}

<nav class="tabs">
	<ul>
		<li><a href="{$admin_url}me/">Mes informations</a></li>
		<li><a href="{$admin_url}me/security.php">Mot de passe et sécurité</a></li>
		<li class="current"><a href="{$admin_url}me/preferences.php">Préférences</a></li>
	</ul>
</nav>

{if $ok !== null}
<p class="confirm block">
	Les modifications ont bien été enregistrées.
</p>
{/if}


<form method="post" action="{$self_url_no_qs}">
	<fieldset>
		<legend>Mes préférences</legend>
		<dl>
			{input type="select" name="folders_gallery" label="Affichage des listes de documents" required=true source=$preferences options=$folders_options default=true}
			{input type="select" name="page_size" label="Nombre d'éléments par page dans les listes" required=true source=$preferences options=$page_size_options default=100 help="Par exemple dans la liste des membres."}
			{input type="select" name="dark_theme" label="Thème" required=true source=$preferences options=$themes_options default=false}
		</dl>
	</fieldset>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
	</p>

</form>

{include file="_foot.tpl"}