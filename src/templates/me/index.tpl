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


{if $user->isChild() || count($children)}
<aside class="describe">
	<dl class="describe">
		{if $user->isChild()}
			<dt>Membre responsable</dt>
			<dd>{$parent_name}</dd>
		{elseif count($children)}
			<dt>Membres rattachés</dt>
			{foreach from=$children item="child"}
				<dd>{$child.name}</dd>
			{/foreach}
		{/if}
	</dl>
</aside>
{/if}

{include file="users/_details.tpl" data=$user show_message_button=false context="user"}

<dl class="describe">
	<dd>{linkbutton href="!me/export.php" label="Télécharger toutes les données détenues sur moi" shape="download"}</dd>
</dl>

{$snippets|raw}

{include file="_foot.tpl"}