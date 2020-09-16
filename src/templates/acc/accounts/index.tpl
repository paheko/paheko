{include file="acc/_head.tpl" title="Comptes favoris" current="acc/charts"}

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

{foreach from=$accounts_grouped key="group_name" item="accounts"}
	<h2 class="ruler">{$group_name}</h2>

	<dl class="list">
	{foreach from=$accounts item="account"}
		<dt>{$account.label} <em>({$account.code})</em></dt>
		<dd class="desc">{$account.description}</dd>
		<dd class="actions">
			{button shape="menu" label="Journal" href="acc/transactions/journal.php?id=%d"|args:$account.id}
			{if $session->canAccess('compta', Membres::DROIT_ADMIN)}
				{button shape="edit" label="Modifier" href="acc/accounts/edit.php?id=%d"|args:$account.id}
				{button shape="delete" label="Supprimer" href="acc/accounts/delete.php?id=%d"|args:$account.id}
			{/if}
		</dd>
	{/foreach}
	</dl>
{/foreach}

{include file="admin/_foot.tpl"}