{include file="_head.tpl" title="Configuration des règles d'import bancaire"}

<nav class="tabs">
	<aside>
		{linkbutton shape="plus" href="edit.php" target="_dialog" label="Nouvelle règle"}
	</aside>
</nav>

<div class="block help">
	<p>Vous pouvez configurer ici des règles permettant de simplifier l'import de relevés bancaires.</p>
	<p>Cela permet par exemple de déterminer automatiquement le compte destinataire, ou de modifier le libellé.</p>
</div>

{if !$list->count()}
	<p class="alert block">Il n'y a aucune règle d'import.</p>
{else}
	{include file="common/dynamic_list_head.tpl"}
		{foreach from=$list->iterate() item="row"}
		<tr>
			<th scope="row">{$row.label}</th>
			<td>{$row.match_file_name}</td>
			<td>{$row.match_label}</td>
			<td>{$row.match_account}</td>
			<td>{$row.target_account}</td>
			<td class="actions">
				{linkbutton href="edit.php?id=%d"|args:$row.id label="Modifier" shape="edit" target="_dialog"}
				{linkbutton href="delete.php?id=%d"|args:$row.id label="Supprimer" shape="delete" target="_dialog"}
			</td>
		</tr>
	{/foreach}
	</table>
{/if}

{$list->getHTMLPagination()|raw}

{include file="_foot.tpl"}