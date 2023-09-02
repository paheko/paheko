{include file="admin/_head.tpl" title="%s — Inscriptions aux activités et cotisations"|args:$user.identite current="membres/services"}

<p>
	{linkbutton href="!membres/fiche.php?id=%d"|args:$user.id label="Retour à la fiche membre" shape="user"}
	{linkbutton href="!services/user/subscribe.php?user=%d"|args:$user.id label="Inscrire à une activité" shape="plus"}
</p>

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
	{if !$only && !$after}
	<dt>Nombre d'inscriptions pour ce membre</dt>
	<dd>
		{$list->count()}
		{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)}
			{exportmenu href="?id=%d"|args:$user.id}
		{/if}
	</dd>
	{/if}
</dl>
{/if}

{if $only}
	<p class="alert block">Cette liste ne montre qu'une seule inscription, liée à l'activité <strong>{$only_service.label}</strong><br />
	{linkbutton shape="right" href="?id=%d"|args:$user.id label="Voir toutes les inscriptions"}</p>
{elseif $after}
	<p class="alert block">Cette liste ne montre que les inscriptions réalisées après le {$after|date_long}.<br />
		{linkbutton shape="right" href="?id=%d"|args:$user.id label="Voir toutes les inscriptions"}
	</p>
{/if}

{include file="common/dynamic_list_head.tpl"}

	{foreach from=$list->iterate() item="row"}
		<tr>
			<th>{$row.label}</th>
			<td>{$row.fee}</td>
			<td>{$row.date|date_short}</td>
			<td>{$row.expiry|date_short}</td>
			<td>{if $row.paid}<b class="confirm">Oui</b>{else}<b class="error">Non</b>{/if}</td>
			<td class="money">{if $row.expected_amount}{$row.amount|raw|money_currency:false}
				{if $row.amount}<br /><small class="help">(sur {$row.expected_amount|raw|money_currency:false})</small>{/if}
				{/if}
			</td>
			<td class="actions">
			{if !$row.paid}
				{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_WRITE) && $row.id_account}
					{linkbutton shape="plus" label="Nouveau règlement" href="payment.php?id=%d"|args:$row.id}
				{/if}
				{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_WRITE) && $row.account_code}
					{linkbutton shape="plus" label="Saisir une écriture liée"
						href="!acc/transactions/new.php?u[%d]=%d&00=%d&t=1&l=Paiement%%20activité&ar=%s&set_year=%d"|args:$user.id:$row.id:$row.expected_amount:$row.account_code:$row.id_year target="_dialog"}
				{/if}
				<br />
			{/if}

			{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_READ)}
				{linkbutton shape="menu" label="Liste des écritures" href="!acc/transactions/service_user.php?id=%d&user=%d"|args:$row.id,$user.id}
			{/if}

			{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_WRITE)}
				{if $row.paid}
					{linkbutton shape="reset" label="Marquer comme non payé" href="?id=%d&su_id=%d&paid=0"|args:$user.id,$row.id}
				{else}
					{linkbutton shape="check" label="Marquer comme payé" href="?id=%d&su_id=%d&paid=1"|args:$user.id,$row.id}
				{/if}
				<br />
				{linkbutton shape="edit" label="Modifier" href="edit.php?id=%d"|args:$row.id}
				{linkbutton shape="delete" label="Supprimer" href="delete.php?id=%d"|args:$row.id}
			{/if}
			</td>
		</tr>
	{foreachelse}
		<tr>
			<td colspan="7">Aucune inscription trouvée.</td>
		</tr>
	{/foreach}

	</tbody>
</table>

{pagination url=$list->paginationURL() page=$list.page bypage=$list.per_page total=$list->count()}


{include file="admin/_foot.tpl"}