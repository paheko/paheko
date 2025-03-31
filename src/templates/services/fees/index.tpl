{include file="_head.tpl" title="%s — Tarifs"|args:$service.label current="users/services"}

{include file="services/_nav.tpl" current="index" current_service=$service service_page="index"}

{if $service.description}
<p class="help">{$service.description|escape|nl2br}</p>
{/if}

{if $list->count()}
	{include file="common/dynamic_list_head.tpl"}
			{foreach from=$list->iterate() item="row"}
				<tr>
					<th><a href="details.php?id={$row.id}">{$row.label}</a></th>
					<td>
						{if $row.amount === -1}
							Formule
						{elseif $row.amount}
							{$row.amount|money_currency|raw}
						{else}
							-
						{/if}
					</td>
					<td class="num"><a href="details.php?id={$row.id}&amp;type=active">{$row.nb_users_ok}</a></td>
					<td class="num"><a href="details.php?id={$row.id}&amp;type=expired">{$row.nb_users_expired}</a></td>
					<td class="num"><a href="details.php?id={$row.id}&amp;type=unpaid">{$row.nb_users_unpaid}</td>
					<td class="actions">
						{linkbutton shape="users" label="Liste des inscrits" href="!services/fees/details.php?id=%d"|args:$row.id}
						{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)}
							{linkbutton shape="edit" label="Modifier" href="!services/fees/edit.php?id=%d"|args:$row.id}
							{linkbutton shape="delete" label="Supprimer" href="!services/fees/delete.php?id=%d"|args:$row.id}
						{/if}
					</td>
				</tr>
			{/foreach}
		</tbody>
	</table>

	{$list->getHTMLPagination()|raw}
{else}
	<p class="block alert">
		Il n'y a aucun tarif enregistré. Créez un premier tarif pour l'activité «&nbsp;{$service.label}&nbsp;» pour pouvoir y inscrire des membres.
	</p>
{/if}

{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)}
	{include file="services/fees/_fee_form.tpl" legend="Ajouter un tarif" submit_label="Ajouter" csrf_key="fee_add" fee=null amount_type=0 account=null}
{/if}

{include file="_foot.tpl"}