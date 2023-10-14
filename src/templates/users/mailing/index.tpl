{include file="_head.tpl" title="Messages collectifs" current="users/mailing"}

<nav class="tabs">
	<aside>
		{linkbutton shape="plus" label="Nouveau message" href="new.php" target="_dialog"}
	</aside>
	<ul>
		<li class="current"><a href="{$self_url}">Messages collectifs</a></li>
		<li><a href="rejected.php">Adresses rejetées</a></li>
	</ul>
</nav>

{if $_GET.msg == 'DELETE'}
	<p class="confirm block">Le message a bien été supprimé.</p>
{/if}

{if !$list->count()}
	<p class="alert block">Aucun message collectif n'a été écrit.<br />
		{linkbutton shape="plus" label="Écrire un nouveau message" href="new.php" target="_dialog"}
	</p>
{else}
	{include file="common/dynamic_list_head.tpl"}

	{foreach from=$list->iterate() item="row"}
		<tr>
			<th>{link href="details.php?id=%d"|args:$row.id label=$row.subject}</th>
			<td>{$row.nb_recipients}</td>
			<td>{if $row.sent}{$row.sent|relative_date:true}{else}Brouillon{/if}</td>
			<td class="actions">
				{linkbutton shape="eye" label="Ouvrir" href="details.php?id=%d"|args:$row.id}
				{if !$row.sent}
					{linkbutton shape="edit" label="Modifier" href="write.php?id=%d"|args:$row.id}
				{/if}
				{linkbutton shape="delete" label="Supprimer" href="delete.php?id=%d"|args:$row.id target="_dialog"}
			</td>
		</tr>
	{/foreach}
	</tbody>
	</table>

	{$list->getHTMLPagination()|raw}
{/if}


{include file="_foot.tpl"}