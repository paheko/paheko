{use Paheko\Entities\Web\Page}
{include file="_head.tpl" title="Toutes les pages du site web" current="web"}

<nav class="tabs">
	{linkbutton shape="left" href="./" label="Retour à la gestion du site"}
</nav>

{if $list->count()}

	<p class="actions">{exportmenu name="_dl_export" class="menu-btn-right"}</p>

	{include file="common/dynamic_list_head.tpl"}

		{foreach from=$list->iterate() item="p"}
			<tr>
				<th scope="row">{link label=$p.title href="./?id=%d"|args:$p.id}</th>
				<td>{$p.path}</td>
				<td><?=Page::STATUS_LIST[$p->status]?></td>
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