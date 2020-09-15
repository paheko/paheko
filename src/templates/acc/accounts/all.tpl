{include file="acc/_head.tpl" title="Comptes favoris" current="acc/charts"}

<ul class="actions">
	<li><a href="{$admin_url}acc/accounts/">Comptes favoris</a></li>
	<li class="current"><a href="{$admin_url}acc/accounts/all.php">Tous les comptes</a></li>
	{if $session->canAccess('compta', Membres::DROIT_ADMIN)}
	<li><a href="{$admin_url}acc/accounts/new.php"><strong>Ajouter un compte</strong></a></li>
	<li><a href="{$admin_url}acc/charts/">Plans comptables</a></li>
	<li><a href="{$admin_url}acc/charts/import.php">Importer un plan comptable</a></li>
	{/if}
</ul>


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
				{icon shape="menu" label="Journal" href="acc/transactions/journal.php?id=%d"|args:$account.id}
				{if $session->canAccess('compta', Membres::DROIT_ADMIN)}
					{icon shape="edit" label="Modifier" href="acc/accounts/edit.php?id=%d"|args:$account.id}
					{icon shape="delete" label="Supprimer" href="acc/accounts/delete.php?id=%d"|args:$account.id}
				{/if}
			</td>
		</tr>
	{/foreach}
	</tbody>
</table>

{include file="admin/_foot.tpl"}