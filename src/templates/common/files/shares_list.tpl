{include file="_head.tpl" title="Partages existants" current="docs"}

{include file="./_share_nav.tpl" current="list"}

{form_errors}

<form method="post" action="">
	{if $list->count()}
		{include file="common/dynamic_list_head.tpl"}
		{foreach from=$list->iterate() item="row"}
			<tr>
				<td>{$row.user_name}</td>
				<td>{$sharing_options[$row.option]}</td>
				<td>{if !$row.expiry}jamais{else}{$row.expiry|relative_date:true}{/if}</td>
				<td>{if $row.password}{tag label="Oui" color="indianred"}{/if}</td>
				<td>{$row.created|relative_date:true}</td>
				<td class="actions">
					{button shape="delete" name="delete" type="submit" value=$row.hash_id label="Supprimer"}
					{linkbutton shape="link" label="Lien" href="share.php?h=%s&s=%s"|args:$file.hash_id:$row.hash_id}
				</td>
			</tr>
		{/foreach}
		</tbody>
		</table>
		{csrf_field key=$csrf_key}
	{else}
		<p class="alert block">Il n'y a aucun lien de partage existant pour ce fichier.</p>
	{/if}
</form>


{include file="_foot.tpl"}