{include file="admin/_head.tpl" title="%s — Inscriptions aux activités et cotisations"|args:$user.identite current="membres/services"}

<p>
	{linkbutton href="!membres/fiche.php?id=%d"|args:$user.id label="Retour à la fiche membre" shape="user"}
	{linkbutton href="!services/save.php?user=%d"|args:$user.id label="Inscrire à une activité" shape="plus"}
</p>

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
			{linkbutton href="?export=csv" shape="export" label="Export CSV"}
			{linkbutton href="?export=ods" shape="export" label="Export tableur"}
		{/if}
	</dd>
</dl>

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
				{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_WRITE)}
					{if $row.paid}
						{linkbutton shape="reset" label="Marquer comme non payé" href="?id=%d&su_id=%d&paid=0"|args:$user.id,$row.id}
					{else}
						{linkbutton shape="check" label="Marquer comme payé" href="?id=%d&su_id=%d&paid=1"|args:$user.id,$row.id}
					{/if}
					{linkbutton shape="delete" label="Supprimer" href="user_delete.php?id=%d"|args:$row.id}
				{/if}
				{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ) && $row.id_account}
					{linkbutton shape="menu" label="Liste des écritures" href="!acc/transactions/service_user.php?id=%d&user=%d"|args:$row.id,$user.id}
				{/if}
				{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_WRITE) && $row.id_account}
					{linkbutton shape="plus" label="Nouveau règlement" href="payment.php?id=%d"|args:$row.id}
				{/if}
			</td>
		</tr>
	{/foreach}

	</tbody>
</table>

{pagination url=$list->paginationURL() page=$list.page bypage=$list.per_page total=$list->count()}


{include file="admin/_foot.tpl"}