{include file="admin/_head.tpl" title="Comptes favoris" current="acc/charts"}

{include file="acc/charts/accounts/_nav.tpl" current="favorites"}

<table class="list">
{foreach from=$accounts_grouped item="group"}
	<tbody>
		<tr>
			<td colspan="3"><h2 class="ruler">{$group.label}</h2></td>
			<td class="actions">
				{if !$chart.archived}
					{linkbutton label="Ajouter un compte" shape="plus" href="acc/charts/accounts/new.php?id=%d&type=%d"|args:$chart.id,$group.type}
				{/if}
			</td>
		</tr>

	{foreach from=$group.accounts item="account"}
		<tr>
			<td class="num">{$account.code}</td>
			<th>{$account.label}</th>
			<td class="desc">{$account.description}</td>
			<td class="actions">
				{if $session->canAccess('compta', Membres::DROIT_ADMIN) && !$chart.archived}
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