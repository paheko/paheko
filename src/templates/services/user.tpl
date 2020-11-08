{include file="admin/_head.tpl" title="%s — Liste des activités"|args:$user.identite current="membres/services"}

<p>
	{linkbutton href="membres/fiche.php?id=%d"|args:$user.id label="Retour à la fiche membre" shape="user"}
	{linkbutton href="services/save.php?user=%d"|args:$user.id label="Enregistrer une activité" shape="plus"}
</p>

{form_errors}

<dl class="cotisation">
	<dt>Nombre d'inscriptions pour ce membre</dt>
	<dd>
		{$list->count()}
	</dd>
</dl>

{include file="common/dynamic_list_head.tpl"}

	{foreach from=$list->iterate() item="row"}
		<tr>
			<th>{$row.label}</th>
			<td>{$row.date|date_short}</td>
			<td>
				{if $row.status == 1}
					<b class="confirm">À jour</b>
				{elseif $row.status == -1}
					<b class="error">En retard</b>
				{else}
					Pas d'expiration
				{/if}
			</td>
			<td>{$row.expiry|date_short}</td>
			<td>{$row.fee}</td>
			<td>{if $row.paid}<b class="confirm">Oui</b>{else}<b class="error">Non</b>{/if}</td>
			<td>{$row.amount|raw|money_currency}</td>
			<td class="actions">
				{if $session->canAccess('membres', Membres::DROIT_ECRITURE)}
					{if $row.paid}
						{linkbutton shape="reset" label="Marquer comme non payé" href="services/user.php?id=%d&su_id=%d&paid=0"|args:$user.id,$row.id}
					{else}
						{linkbutton shape="check" label="Marquer comme payé" href="services/user.php?id=%d&su_id=%d&paid=1"|args:$user.id,$row.id}
					{/if}
				{/if}
				{if $session->canAccess('compta', Membres::DROIT_ACCES) && $row.id_account}
					{linkbutton shape="menu" label="Liste des écritures" href="acc/transactions/service_user.php?id=%d"|args:$row.id}
				{/if}
				{if $session->canAccess('compta', Membres::DROIT_ECRITURE) && $row.id_account}
					{linkbutton shape="plus" label="Nouveau règlement" href="services/payment.php?id=%d"|args:$row.id}
				{/if}
			</td>
		</tr>
	{/foreach}

	</tbody>
</table>

{pagination url=$list->paginationURL() page=$list.page bypage=$list.per_page total=$list->count()}


{include file="admin/_foot.tpl"}