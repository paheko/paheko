{include file="_head.tpl" title="Activités et cotisations" current="users/services"}

{include file="services/_nav.tpl" current="index" service=null fee=null}

{if isset($_GET['CREATE'])}
	<p class="block error">Vous devez déjà créer une activité pour pouvoir utiliser cette fonction.</p>
{/if}

{if $list->count()}
	{include file="common/dynamic_list_head.tpl"}
			{foreach from=$list->iterate() item="row"}
				<tr>
					<th><a href="fees/?id={$row.id}">{$row.label}</a></th>
					<td>
						{if $row.duration}
							{$row.duration} jours
						{elseif $row.start_date}
							{$row.start_date|date_short} au {$row.end_date|date_short}
						{else}
							ponctuelle
						{/if}
					</td>
					<td class="num"><a href="details.php?id={$row.id}&amp;type=active">{$row.nb_users_ok}</a></td>
					<td class="num"><a href="details.php?id={$row.id}&amp;type=expired">{$row.nb_users_expired}</a></td>
					<td class="num"><a href="details.php?id={$row.id}&amp;type=unpaid">{$row.nb_users_unpaid}</a></td>
					<td class="actions">
						{linkbutton shape="menu" label="Tarifs" href="!services/fees/?id=%d"|args:$row.id}
						{linkbutton shape="users" label="Liste des inscrits" href="!services/details.php?id=%d"|args:$row.id}
						{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)}
							{linkbutton shape="edit" label="Modifier" href="!services/edit.php?id=%d"|args:$row.id}
							{linkbutton shape="delete" label="Supprimer" href="!services/delete.php?id=%d"|args:$row.id}
						{/if}
					</td>
				</tr>
			{/foreach}
		</tbody>
	</table>

	{$list->getHTMLPagination()|raw}
{else}
	<p class="block alert">Il n'y a aucune activité enregistrée.</p>
{/if}

{if empty($show_old_services) && $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)}
	{include file="services/_service_form.tpl" legend="Ajouter une activité" service=null period=0}
{/if}

{include file="_foot.tpl"}