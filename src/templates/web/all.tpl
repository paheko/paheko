{include file="_head.tpl" title="Toutes les pages du site web" current="web"}

<nav class="tabs">
	{linkbutton shape="left" href="./" label="Retour à la gestion du site"}
</nav>

{if $list->count()}

	{include file="common/dynamic_list_head.tpl"}

		{foreach from=$list->iterate() item="p"}
			<tr>
				<th>{link label=$p.title href="./?id=%d"|args:$p.id}</th>
				<td>{$p.path}</td>
				<td>{if $p.draft}<em>Brouillon</em>{/if}</td>
				<td>{$p.published|relative_date}</td>
				<td>{$p.modified|relative_date:true}</td>
				<td class="actions">
					{if $can_edit}
						{linkbutton shape="edit" label="Éditer" href="edit.php?id=%d"|args:$p.id}
					{/if}
				</td>
			</tr>
		{/foreach}
		</tbody>
	</table>

	{$list->getHTMLPagination()|raw}
{else}
	<p class="help">Il n'y a aucune page dans le site.</p>
{/if}

{include file="_foot.tpl"}