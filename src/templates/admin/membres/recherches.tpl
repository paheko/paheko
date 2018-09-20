{include file="admin/_head.tpl" title="Recherches enregistrées" current="membres"}

{include file="admin/membres/_nav.tpl" current="recherches"}

{if count($liste) == 0}
	<p class="alert">Aucune recherche enregistrée. <a href="{$admin_url}membres/recherche.php">Faire une nouvelle recherche</a></p>
{/if}

{include file="admin/_foot.tpl"}