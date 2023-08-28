<?php
use Paheko\Entities\Web\Page;
?>

{include file="common/dynamic_list_head.tpl"}

	{foreach from=$list->iterate() item="p"}
		<tr>
			<th>{link label=$p.title href="?id=%d"|args:$p.id}</th>
			<td>{$p.published|relative_date}</td>
			<td>{$p.modified|relative_date:true}</td>
			<td class="actions">
				{linkbutton shape="image" label="Lire" href="?id=%d"|args:$p.id}
				{if $session->canAccess($session::SECTION_WEB, $session::ACCESS_WRITE)}
					{linkbutton shape="edit" label="Éditer" href="edit.php?id=%d"|args:$p.id}
					{if $session->canAccess($session::SECTION_WEB, $session::ACCESS_ADMIN)}
						{linkbutton shape="delete" label="Supprimer" target="_dialog" href="delete.php?id=%d"|args:$p.id}
					{/if}
				{/if}
			</td>
		</tr>
	{/foreach}
	</tbody>
</table>

{$list->getHTMLPagination()|raw}