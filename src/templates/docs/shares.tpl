{include file="_head.tpl" title="Fichiers partagés" current="docs" hide_title=true}

<nav class="tabs">
	{include file="./_nav.tpl" context="shares"}
	<h2>Fichiers partagés</h2>
</nav>

<p class="help">

{form_errors}

<form method="post" action="">
	{if $list->count()}
		{include file="common/dynamic_list_head.tpl"}
		{foreach from=$list->iterate() item="row"}
			<tr>
				<th>{$row.file_name}</th>
				<td>{link href="!docs/?id=%s"|args:$row.file_hash_id label=$row.file_parent}</td>
				<td>{$row.user_name}</td>
				<td>{$sharing_options[$row.option]}</td>
				<td>{if !$row.expiry}jamais{else}{$row.expiry|relative_date:true}{/if}</td>
				<td>{if $row.password}{tag label="Oui" color="indianred"}{/if}</td>
				<td>{$row.created|relative_date:true}</td>
				<td class="actions">
					{button shape="delete" name="delete" type="submit" value=$row.hash_id label="Supprimer"}
					{linkbutton shape="link" label="Lien" href="!common/files/share.php?h=%s&s=%s"|args:$row.file_hash_id:$row.hash_id target="_dialog"}
				</td>
			</tr>
		{/foreach}
		</tbody>
		</table>
		{csrf_field key=$csrf_key}
		{$list->getHTMLPagination()|raw}
	{else}
		<p class="alert block">Il n'y a aucun fichier partagé.</p>
	{/if}
</form>

{include file="_foot.tpl"}
