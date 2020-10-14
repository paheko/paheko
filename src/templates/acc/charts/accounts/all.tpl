{include file="admin/_head.tpl" title="Plan comptable"|args:$chart.label current="acc/charts"}

{include file="acc/charts/accounts/_nav.tpl" current="all"}

<table class="accounts">
	<tbody>
	{foreach from=$accounts item="account"}
		<tr class="account-level-{$account.code|strlen}">
			<td>{$account.code}</td>
			<th>{$account.label}</th>
			<td>
				{if $account.type}
					<?=Entities\Accounting\Account::TYPES_NAMES[$account->type]?>
				{/if}
			</td>
			<td class="actions">
				{if $session->canAccess('compta', Membres::DROIT_ADMIN)}
					{linkbutton shape="edit" label="Modifier" href="acc/charts/accounts/edit.php?id=%d"|args:$account.id}
					{linkbutton shape="delete" label="Supprimer" href="acc/charts/accounts/delete.php?id=%d"|args:$account.id}
				{/if}
			</td>
		</tr>
	{/foreach}
	</tbody>
</table>

{include file="admin/_foot.tpl"}