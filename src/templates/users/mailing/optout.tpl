{include file="_head.tpl" title="Désinscriptions" current="users/mailing"}

{include file="./_nav.tpl" current="optout"}

{if isset($_GET['sent'])}
<p class="confirm block">
	Un message de demande de confirmation a bien été envoyé. Le destinataire doit désormais cliquer sur le lien dans ce message.
</p>
{/if}

{if !$list->count()}
	<p class="alert block">Aucune adresse e-mail n'a demandé à être désinscrite pour le moment.</p>
{else}
	{include file="common/dynamic_list_head.tpl"}

		{foreach from=$list->iterate() item="row"}
		<tr{if $_GET.hl == $row.id} class="highlight"{/if} id="e_{$row.id}">
			<th>{link href="!users/details.php?id=%d"|args:$row.user_id label=$row.identity}</th>
			<td>{$row.email}</td>
			<td><b class="error">{$row.status}</b></td>
			<td class="num">{$row.sent_count}</td>
			<td>{$row.last_sent|date}</td>
			<td>
				{if $row.email && $row.optout}
					{linkbutton target="_dialog" label="Rétablir" href="!users/mailing/verify.php?address=%s"|args:$row.email shape="check"}
				{elseif $row.email && $row.target_type}
					{linkbutton target="_dialog" label="Supprimer" href="!users/mailing/optout_delete.php?address=%s"|args:$row.email shape="delete"}
				{/if}
			</td>
		</tr>

		{/foreach}
	</tbody>
	</table>

	{$list->getHTMLPagination()|raw}

{/if}

{include file="_foot.tpl"}