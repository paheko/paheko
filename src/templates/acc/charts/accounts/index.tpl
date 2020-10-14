{include file="admin/_head.tpl" title="Comptes favoris" current="acc/charts"}

{include file="acc/charts/accounts/_nav.tpl" current="favorites"}

<table class="list">
{foreach from=$accounts_grouped key="group_name" item="accounts"}
	<tbody>
		<tr><td colspan="4"><h2 class="ruler">{$group_name}</h2></td></tr>

	{foreach from=$accounts item="account"}
		<tr>
			<td class="num">{$account.code}</td>
			<th>{$account.label}</th>
			<td class="desc">{$account.description}</td>
			<td class="actions">
				{if $session->canAccess('compta', Membres::DROIT_ADMIN)}
					{linkbutton shape="edit" label="Modifier" href="acc/charts/accounts/edit.php?id=%d"|args:$account.id}
					{linkbutton shape="delete" label="Supprimer" href="acc/charts/accounts/delete.php?id=%d"|args:$account.id}
				{/if}
			</td>
		</tr>
	{/foreach}
	</tbody>
{/foreach}
</table>

{include file="admin/_foot.tpl"}