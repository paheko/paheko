{include file="_head.tpl" title="%s — Inscriptions aux activités et cotisations"|args:$user_name current="users/services"}

{include file="users/_nav_user.tpl" id=$user_id current="services"}

{form_errors}

{if !$only}
<dl class="cotisation">
	<dt>Statut des inscriptions</dt>
	{foreach from=$services item="service"}
	<dd{if $service.archived} class="disabled"{/if}>
		{$service.label}
		{if $service.archived} <em>(activité passée)</em>{/if}
		{if $service.status == -1 && $service.end_date} — expirée
		{elseif $service.status == -1} — <b class="error">en retard</b>
		{elseif $service.status == 1 && $service.end_date} — <b class="confirm">en cours</b>
		{elseif $service.status == 1} — <b class="confirm">à jour</b>{/if}
		{if $service.status.expiry_date} — expire le {$service.expiry_date|date_short}{/if}
		{if !$service.paid} — <b class="error">À payer&nbsp;!</b>{/if}
	</dd>
	{foreachelse}
	<dd>
		Ce membre n'est inscrit à aucune activité ou cotisation.
	</dd>
	{/foreach}
	<dt>Nombre d'inscriptions pour ce membre</dt>
	<dd>
		{$list->count()}
		{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)}
		<nav class="menu">
			<b data-icon="↷" class="btn">Export</b>
			<span>
				{linkbutton href="?id=%d&export=csv"|args:$user_id shape="export" label="Export CSV"}
				{linkbutton href="?id=%d&export=ods"|args:$user_id shape="export" label="Export tableur"}
				{if CALC_CONVERT_COMMAND}
					{linkbutton href="?id=%d&export=xlsx"|args:$user_id shape="export" label="Export Excel"}
				{/if}
			</span>
		</nav>
		{/if}
	</dd>
</dl>
{/if}

{if $only}
	<p class="alert block">Cette liste ne montre qu'une seule inscription, liée à l'activité <strong>{$only_service.label}</strong><br />{linkbutton shape="right" href="?id=%d"|args:$user_id label="Voir toutes les inscriptions"}</p>
{/if}

{include file="common/dynamic_list_head.tpl"}

	{foreach from=$list->iterate() item="row"}
		<tr>
			<th>{$row.label}</th>
			<td>{$row.date|date_short}</td>
			<td>{$row.expiry|date_short}</td>
			<td>{$row.fee}</td>
			<td>{if $row.paid}<b class="confirm">Oui</b>{else}<b class="error">Non</b>{/if}</td>
			<td>{if $row.expected_amount}{$row.amount|raw|money_currency:false}{/if}</td>
			<td class="actions">
				{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_WRITE) && $row.id_account}
					{linkbutton shape="plus" label="Nouveau règlement" href="payment.php?id=%d"|args:$row.id}
				{/if}
				{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ)}
					{if $row.has_transactions}
						{linkbutton shape="menu" label="Liste des écritures" href="!acc/transactions/service_user.php?id=%d&user=%d"|args:$row.id,$user_id}
					{else}
						{linkbutton shape="check" label="Lier à une écriture" href="link.php?id=%d"|args:$row.id target="_dialog"}
					{/if}
				{/if}
				{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_WRITE)}
					{if $row.paid}
						{linkbutton shape="reset" label="Marquer comme non payé" href="?id=%d&su_id=%d&paid=0"|args:$user_id,$row.id}
					{else}
						{linkbutton shape="check" label="Marquer comme payé" href="?id=%d&su_id=%d&paid=1"|args:$user_id,$row.id}
					{/if}
					{linkbutton shape="edit" label="Modifier" href="edit.php?id=%d"|args:$row.id}
					{linkbutton shape="delete" label="Supprimer" href="delete.php?id=%d"|args:$row.id}
				{/if}
			</td>
		</tr>
	{/foreach}

	</tbody>
</table>

{pagination url=$list->paginationURL() page=$list.page bypage=$list.per_page total=$list->count()}


{include file="_foot.tpl"}