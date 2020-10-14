<nav class="tabs">
	<ul>
		<li class="current"><a href="{$admin_url}acc/charts/">Plans comptables</a></li>
		<li><a href="{$admin_url}acc/charts/import.php">Importer un plan comptable</a></li>
	</ul>
	<ul class="sub">
		<li><strong>{$chart.label}&nbsp;:</strong></li>
		<li{if $current == 'favorites'} class="current"{/if}><a href="{$admin_url}acc/charts/accounts/?id={$chart.id}">Comptes favoris</a></li>
		<li{if $current == 'all'} class="current"{/if}><a href="{$admin_url}acc/charts/accounts/all.php?id={$chart.id}">Tous les comptes</a></li>
		{if $session->canAccess('compta', Membres::DROIT_ADMIN)}
			<li{if $current == 'add'} class="current"{/if}><a href="{$admin_url}acc/charts/accounts/new.php?id={$chart.id}"><strong>Ajouter un compte</strong></a></li>
		{/if}
	</ul>
</nav>