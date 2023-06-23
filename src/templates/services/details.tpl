{include file="_head.tpl" title="%s — Liste des membres inscrits"|args:$service.label current="users/services"}

{include file="services/_nav.tpl" current="index" current_service=$service service_page=$type}

<?php
$can_action = $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN);
?>

<form method="get" action="">
	<input type="hidden" name="id" value="{$service.id}" />
	<input type="hidden" name="type" value="{$type}" />

<dl class="cotisation">
	<dt>Nombre de membres trouvés</dt>
	<dd>
		{$list->count()}
		<em class="help">(N'apparaît ici que l'inscription la plus récente de chaque membre.)</em>
		{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)}
			{exportmenu}
		{/if}
	</dd>
	{if $can_action}
	<dt>Membres des catégories cachées</dt>
	{input type="checkbox" label="Afficher aussi les inscriptions des membres appartenant à des catégories cachées" name="hidden" value="1" onchange="this.form.submit()" default=$include_hidden_categories role="button"}
	{/if}
</dl>
</form>

{if $can_action}
	<form method="post" action="{"!users/action.php"|local_url}">
{/if}

{if !$list->count()}
	<p class="alert block">Il n'y a aucun résultat.</p>
{else}
	{include file="common/dynamic_list_head.tpl" check=$can_action}

	{foreach from=$list->iterate() item="row"}
		<tr>
			{if $can_action}
			<td class="check">{input type="checkbox" name="selected[]" value=$row.id_user}</td>
			{/if}

			<th><a href="../users/details.php?id={$row.id_user}">{$row.identity}</a></th>
			<td>
				{if $row.status == 1 && $row.end_date}
					En cours
				{elseif $row.status == 1}
					<b class="confirm">À jour</b>
				{elseif $row.status == -1 && $row.end_date}
					Terminée
				{elseif $row.status == -1}
					<b class="error">En retard</b>
				{else}
					Pas d'expiration
				{/if}
			</td>
			<td>{if $row.paid}<b class="confirm">Oui</b>{else}<b class="error">Non</b>{/if}</td>
			<td>{$row.expiry|date_short}</td>
			<td>{$row.fee}</td>
			<td>{$row.date|date_short}</td>
			<td class="actions">
				{linkbutton shape="user" label="Toutes les activités de ce membre" href="!services/user/?id=%d"|args:$row.id_user}
				{linkbutton shape="alert" label="Rappels envoyés" href="!services/reminders/user.php?id=%d"|args:$row.id_user}
			</td>
		</tr>
	{/foreach}

	</tbody>
	{if $can_action}
		{include file="users/_list_actions.tpl" colspan=7 export=false hide_delete=true}
	{/if}

	</table>

	{$list->getHTMLPagination()|raw}
{/if}

{if $can_action}
</form>
{/if}

{include file="_foot.tpl"}