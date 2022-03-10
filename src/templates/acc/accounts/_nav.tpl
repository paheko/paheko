
<nav class="tabs">
	<aside>
		{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
			{linkbutton shape="edit" href="!acc/charts/accounts/?id=%d"|args:$current_year.id_chart label="Modifier les comptes"}
		{/if}
		{linkbutton shape="search" href="!acc/search.php?year=%d"|args:$current_year.id label="Recherche"}
	</aside>
	<ul>
		<li{if $current == 'index'} class="current"{/if}><a href="{$admin_url}acc/accounts/">Comptes usuels</a></li>
		<li{if $current == 'all'} class="current"{/if}><a href="{$admin_url}acc/accounts/all.php?year={$current_year.id}">Tous les comptes</a></li>
		<li{if $current == 'users'} class="current"{/if}><a href="{$admin_url}acc/accounts/users.php">Comptes de membres</a></li>
		<li><a href="{$admin_url}acc/reports/statement.php?year={$current_year.id}"><em>Compte de résultat</em></a></li>
		<li><a href="{$admin_url}acc/reports/balance_sheet.php?year={$current_year.id}"><em>Bilan</em></a></li>
	</ul>
</nav>