{include file="_head.tpl" title="Adresses rejetées" current="users/mailing"}

{include file="./_nav.tpl" current="rejected"}

{if isset($_GET['sent'])}
<p class="confirm block">
	Un message de demande de confirmation a bien été envoyé. Le destinataire doit désormais cliquer sur le lien dans ce message.
</p>
{/if}

{if !$list->count()}
	<p class="alert block">Aucune adresse e-mail n'a été rejetée pour le moment. Cette page présentera les adresses e-mail invalides ou qui ont demandé à se désinscrire.</p>
{else}
	{include file="common/dynamic_list_head.tpl"}

		{foreach from=$list->iterate() item="row"}
		<tr{if $_GET.hl == $row.id} class="highlight"{/if} id="e_{$row.id}">
			<th>{link href="!users/details.php?id=%d"|args:$row.user_id label=$row.identity}</th>
			<td>{$row.email}</td>
			<td>{tag label=$labels[$row.status] color=$colors[$row.status]}</td>
			<td class="num">{$row.sent_count}</td>
			<td>{$row.last_sent|date}</td>
			<td>
				{linkbutton target="_dialog" label="Détails" href="!users/email/address.php?id=%d"|args:$row.id shape="eye"}
			</td>
		</tr>

		{/foreach}
	</tbody>
	</table>

	{$list->getHTMLPagination()|raw}

{/if}

{include file="_foot.tpl"}