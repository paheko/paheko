{include file="admin/_head.tpl" title="Tarif : %s — Liste des membres inscrits"|args:$fee.label current="membres/services"}

{include file="services/_nav.tpl" current="index" current_service=$service service_page="index" current_fee=$fee fee_page=$type}

<dl class="cotisation">
	<dt>Nombre de membres trouvés</dt>
	<dd>
		{$list->count()}
		<em class="help">(N'apparaît ici que l'inscription la plus récente de chaque membre.)</em>
		{if $session->canAccess('membres', Membres::DROIT_ADMIN)}
			{linkbutton href="%s&export=csv"|args:$self_url shape="export" label="Export CSV"}
			{linkbutton href="%s&export=ods"|args:$self_url shape="export" label="Export tableur"}
		{/if}
	</dd>
</dl>

{include file="common/dynamic_list_head.tpl"}

	{foreach from=$list->iterate() item="row"}
		<tr>
			<th><a href="../../membres/fiche.php?id={$row.id_user}">{$row.identity}</a></th>
			<td>{if $row.paid}<b class="confirm">Oui</b>{else}<b class="error">Non</b>{/if}</td>
			<td class="money">{$row.paid_amount|raw|money_currency}</td>
			<td>{$row.date|date_short}</td>
			<td class="actions">
				{linkbutton shape="user" label="Toutes les activités de ce membre" href="!services/user.php?id=%d"|args:$row.id_user}
				{* FIXME TODO {linkbutton shape="alert" label="Rappels envoyés" href="!services/reminders/user.php?id=%d"|args:$row.id_user} *}
			</td>
		</tr>
	{/foreach}

	</tbody>
</table>

{pagination url=$list->paginationURL() page=$list.page bypage=$list.per_page total=$list->count()}


{include file="admin/_foot.tpl"}