<?php
use Paheko\Entities\Web\Page;
?>

{include file="common/dynamic_list_head.tpl"}

	{foreach from=$list->iterate() item="p"}
		<tr>
			<th>{link label=$p.title href="?p=%s"|args:$p.path}</th>
			<td>{$p.published|relative_date}</td>
			<td>{$p.modified|relative_date:true}</td>
			<td class="actions">
				{linkbutton shape="image" label="Lire" href="?p=%s"|args:$p.path}
				{if $session->canAccess($session::SECTION_WEB, $session::ACCESS_WRITE)}
					{linkbutton shape="edit" label="Éditer" href="edit.php?p=%s"|args:$p.path}
					{if $session->canAccess($session::SECTION_WEB, $session::ACCESS_ADMIN)}
						{linkbutton shape="delete" label="Supprimer" target="_dialog" href="delete.php?p=%s"|args:$p.path}
					{/if}
				{/if}
			</td>
		</tr>
	{/foreach}
	</tbody>
</table>

{$list->getHTMLPagination()|raw}