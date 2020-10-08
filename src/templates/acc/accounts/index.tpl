{include file="admin/_head.tpl" title="Comptes favoris" current="acc/accounts"}

{include file="acc/_year_select.tpl"}

<nav class="tabs">
	<ul>
		<li class="current"><a href="{$admin_url}acc/accounts/">Comptes favoris</a></li>
		<li><a href="{$admin_url}acc/accounts/all.php">Tous les comptes</a></li>
		{if $session->canAccess('compta', Membres::DROIT_ADMIN)}
		<li><a href="{$admin_url}acc/accounts/new.php"><strong>Ajouter un compte</strong></a></li>
		<li><a href="{$admin_url}acc/charts/">Plans comptables</a></li>
		<li><a href="{$admin_url}acc/charts/import.php">Importer un plan comptable</a></li>
		{/if}
	</ul>
</nav>

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
				{linkbutton shape="menu" label="Journal" href="acc/accounts/journal.php?id=%d&simple=1"|args:$account.id}
				{if $session->canAccess('compta', Membres::DROIT_ADMIN)}
					{linkbutton shape="edit" label="Modifier" href="acc/accounts/edit.php?id=%d"|args:$account.id}
					{linkbutton shape="delete" label="Supprimer" href="acc/accounts/delete.php?id=%d"|args:$account.id}
				{/if}
			</td>
		</tr>
	{/foreach}
	</tbody>
{/foreach}
</table>

{include file="admin/_foot.tpl"}