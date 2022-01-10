{include file="admin/_head.tpl" title="%s — Inscriptions aux activités et cotisations"|args:$user_name current="users/services"}


<nav class="tabs">
	<aside>
		{linkbutton href="!services/user/subscribe.php?user=%d"|args:$user_id label="Inscrire à une activité" shape="plus"}
	</aside>
	<ul>
		<li>{link href="!users/details.php?id=%d"|args:$user_id label="Fiche membre"}</li>
		<li class="current">{link href="!services/user/?id=%d"|args:$user_id label="Inscriptions aux activités"}</li>
		<li>{link href="!services/reminders/user.php?id=%d"|args:$user_id label="Rappels envoyés"}</li>
	</ul>
</nav>

{form_errors}

<dl class="cotisation">
	<dt>Statut des inscriptions</dt>
	{foreach from=$services item="service"}
	<dd>
		{$service.label}
		{if $service.status == -1 && $service.end_date} — terminée
		{elseif $service.status == -1} — <b class="error">en retard</b>
		{elseif $service.status == 1 && $service.end_date} — <b class="confirm">en cours</b>
		{elseif $service.status == 1} — <b class="confirm">à jour</b>{/if}
		{if $service.status.expiry_date} — expire le {$service.expiry_date|date_short}{/if}
		{if !$service.paid} — <b class="error">À payer&nbsp;!</b>{/if}
	</dd>
	{foreachelse}
	<dd>
		Aucune inscription.
	</dd>
	{/foreach}
	<dt>Nombre d'inscriptions pour ce membre</dt>
	<dd>
		{$list->count()}
		{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)}
			{linkbutton href="?id=%d&export=csv"|args:$user_id shape="export" label="Export CSV"}
			{linkbutton href="?id=%d&export=ods"|args:$user_id shape="export" label="Export tableur"}
		{/if}
	</dd>
</dl>

{if $only}
	<p class="alert block">Cette liste ne montre qu'une seule inscription, liée à une écriture. {link href="?id=%d"|args:$user_id label="Voir toutes les inscriptions"}</p>
{/if}

{include file="common/dynamic_list_head.tpl"}

	{foreach from=$list->iterate() item="row"}
		<tr>
			<th>{$row.label}</th>
			<td>{$row.date|date_short}</td>
			<td>{$row.expiry|date_short}</td>
			<td>{$row.fee}</td>
			<td>{if $row.paid}<b class="confirm">Oui</b>{else}<b class="error">Non</b>{/if}</td>
			<td>{$row.amount|raw|money_currency}</td>
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


{include file="admin/_foot.tpl"}