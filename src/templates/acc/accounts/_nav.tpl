
<nav class="tabs">
	<aside>
		{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
			{linkbutton shape="edit" href="!acc/charts/accounts/?id=%d"|args:$chart_id label="Modifier les comptes"}
		{/if}
		{linkbutton shape="search" href="!acc/search.php?year=%d"|args:$current_year.id label="Recherche"}
	</aside>
	<ul>
		<li{if $current == 'index'} class="current"{/if}><a href="{$admin_url}acc/accounts/">Comptes favoris</a></li>
		<li><a href="{$admin_url}acc/reports/trial_balance.php?year={$current_year.id}">Balance générale (tous les comptes)</a></li>
		<li{if $current == 'users'} class="current"{/if}><a href="{$admin_url}acc/accounts/users.php">Comptes de membres</a></li>
		{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
			<li><a href="{$admin_url}acc/charts/accounts/all.php?id={$chart_id}">Plan comptable complet</a></li>
		{/if}
	</ul>
</nav>