<nav class="tabs">
	<ul>
		<li{if $current == 'index'} class="current"{/if}><a href="./">Liste des membres</a></li>
		<li{if $current == 'recherche'} class="current"{/if}><a href="search.php">Recherche avancée</a></li>
		<li{if $current == 'recherches'} class="current"{/if}><a href="saved_searches.php">Recherches enregistrées</a></li>
		{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)}
			<li{if $current == 'import'} class="current"{/if}><a href="import.php">Import &amp; export</a></li>
		{/if}
	</ul>
</nav>