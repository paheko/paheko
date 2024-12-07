{include file="_head.tpl" title="Tarif : %s — Liste des membres inscrits"|args:$fee.label current="users/services"}

{include file="services/_nav.tpl" current="index" current_service=$service service_page="index" current_fee=$fee fee_page=$type}

<?php
$can_action = $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN);
?>

<form method="get" action="">
	<input type="hidden" name="id" value="{$fee.id}" />
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

{include file="common/dynamic_list_head.tpl" check=$can_action}

	{foreach from=$list->iterate() item="row"}
		<tr>
			{if $can_action}
			<td class="check">{input type="checkbox" name="selected[]" value=$row.id_user}</td>
			{/if}
			<th>{link href="!users/details.php?id=%d"|args:$row.id_user label=$row.identity}</th>
			<td>{if $row.paid}<b class="confirm">Oui</b>{else}<b class="error">Non</b>{/if}</td>
			<td class="money">{if null === $row.paid_amount}<em title="Aucune écriture n'est liée à cette inscription">—</em>{else}{$row.paid_amount|raw|money_currency}{/if}</td>
			<td>{$row.date|date_short}</td>
			<td class="actions">
				{linkbutton shape="user" label="Toutes les activités de ce membre" href="!services/user/?id=%d"|args:$row.id_user}
				{linkbutton shape="alert" label="Rappels envoyés" href="!services/reminders/user.php?id=%d"|args:$row.id_user}
			</td>
		</tr>
	{/foreach}

	</tbody>

	{if $can_action}
		{include file="users/_list_actions.tpl" colspan=5 export=false hide_delete=true}
	{/if}

</table>

{if $can_action}
</form>
{/if}

{$list->getHTMLPagination()|raw}

<p class="help">
	Les lignes indiquant <em title="Aucune écriture n'est liée à cette inscription">—</em> comme montant payé signifient qu'aucune écriture comptable n'a été associée à cette inscription. De ce fait, le montant restant à payer ne peut être calculé.
</p>


{include file="_foot.tpl"}