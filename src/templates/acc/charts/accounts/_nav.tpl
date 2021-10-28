<nav class="tabs">
	<ul>
		<li class="current"><a href="{$admin_url}acc/charts/">Plans comptables</a></li>
		{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
		<li><a href="{$admin_url}acc/charts/import.php">Importer un plan comptable</a></li>
		{/if}
	</ul>
	<ul class="sub">
		<li class="title">{$chart.label}</li>
		<li{if $current == 'favorites'} class="current"{/if}><a href="{$admin_url}acc/charts/accounts/?id={$chart.id}">Comptes favoris</a></li>
		<li{if $current == 'all'} class="current"{/if}><a href="{$admin_url}acc/charts/accounts/all.php?id={$chart.id}">Tous les comptes</a></li>
		{if $session->canAccess($session::SECTION_ACCOUNTING, $session::ACCESS_ADMIN)}
			<li{if $current == 'new'} class="current"{/if}><a href="{$admin_url}acc/charts/accounts/new.php?id={$chart.id}"><strong>Ajouter un compte</strong></a></li>
		{/if}
	</ul>
</nav>