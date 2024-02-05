{include file="_head.tpl" title="Inscriptions" current="users/services"}

{include file="services/_nav.tpl" current="history" service=null fee=null}

{if $list->count()}
	{include file="common/dynamic_list_head.tpl"}
			{foreach from=$list->iterate() item="row"}
				<tr>
					<td>{link href="!users/details.php?id=%d"|args:$row.id_user label=$row.name}</td>
					<th><a href="details.php?id={$row.id_service}">{$row.service}</a></th>
					<th><a href="fees/?id={$row.id_fee}">{$row.fee}</a></th>
					<td>{if $row.paid}<b class="confirm">Oui</b>{else}<b class="error">Non</b>{/if}</td>
					<td>{$row.left_amount|money_html}</td>
					<td>{$row.date|date_short}</td>
					<td>{$row.expiry_date|date_short}</td>
					<td class="actions">
						{linkbutton href="!users/subscriptions.php?id=%d&only=%d"|args:$row.id_user:$row.id label="Détails" shape="eye"}
					</td>
				</tr>
			{/foreach}
		</tbody>
	</table>

	{$list->getHTMLPagination()|raw}
{else}
	<p class="block alert">Il n'y a aucune inscription enregistrée.</p>
{/if}

{include file="_foot.tpl"}