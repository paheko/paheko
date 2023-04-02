{include file="_head.tpl" title="Fichiers supprimés" current="docs" hide_title=true}

<nav class="tabs">
	{include file="./_nav.tpl"}
	<h2>Fichiers supprimés</h2>
</nav>

<p class="help">
	Les fichiers supprimés occupent actuellement <strong>{$trash_size|size_in_bytes}</strong>.
</p>

{form_errors}

<form method="post" action="">
{if $list->count()}
	{include file="common/dynamic_list_head.tpl" check=true}

	{foreach from=$list->iterate() item="item"}
		<tr>
			<td class="check">
				{input type="checkbox" name="check[]" value=$item->path}
			</td>
			<td>{$item.name}</td>
			<td>{$item.parent}</td>
			<td>{$item.modified|date_short:true}</td>
			<td class="actions">
			</td>
		</tr>
	{/foreach}
	</tbody>
	</table>

	<p class="submit">
		{csrf_field key=$csrf_key}
		{button type="submit" name="restore" label="Restaurer les fichiers sélectionnés" shape="reset"}
		{if $session->canAccess($session::SECTION_CONFIG, $session::ACCESS_ADMIN)}
			{button type="submit" name="delete" label="Supprimer définitivement les fichiers sélectionnés" shape="delete"}
		{/if}
	</p>

	{$list->getHTMLPagination()|raw}

{else}

	<p class="alert block">Il n'y a aucun fichier supprimé.</p>

{/if}
</form>