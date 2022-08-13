{include file="admin/_head.tpl" title="Mes informations personnelles" current="me"}

<nav class="tabs">
	<ul>
		<li class="current"><a href="{$admin_url}me/">Mes informations</a></li>
		<li><a href="{$admin_url}me/security.php">Mot de passe et options de sécurité</a></li>
	</ul>
</nav>

{if $ok !== null}
<p class="confirm block">
	Les modifications ont bien été enregistrées.
</p>
{/if}


<dl class="describe">
	<dd>{linkbutton href="!me/edit.php" label="Modifier mes informations" shape="edit"}</dd>
</dl>

{include file="users/_details.tpl" data=$user show_message_button=false mode="user"}

<dl class="describe">
	<dd>{linkbutton href="!me/export.php" label="Télécharger toutes les données détenues sur moi" shape="download"}</dd>
</dl>

{include file="admin/_foot.tpl"}