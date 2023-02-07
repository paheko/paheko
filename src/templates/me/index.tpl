{include file="_head.tpl" title="Mes informations personnelles" current="me"}

{include file="./_nav.tpl" current="me"}

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

{$snippets|raw}

{include file="_foot.tpl"}