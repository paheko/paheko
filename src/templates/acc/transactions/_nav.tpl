<nav class="tabs">
	{if $current == 'index'}
		<aside>
			<form method="get" action="{$admin_url}acc/search.php" data-focus="1">
				<input type="search" name="qt" id="f_qt" value="" placeholder="Recherche rapide" />
				{button type="submit" shape="search" label="" title="Chercher" aria-label="Chercher"}
				{linkbutton shape="help" href="#help" label="" onclick="g.toggle('#search-help', true); return false;" title="Aide sur la recherche rapide"}
			</form>
		</aside>
	{/if}

	<ul>
		{tabitem href="!acc/transactions/" label="Suivi des écritures" name="index" selected=$current}
		{tabitem href="!acc/search.php" label="Recherche avancée" name="search" selected=$current}
		{tabitem href="!acc/saved_searches.php" label="Recherches enregistrées" name="saved_searches" selected=$current}
		{if $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)
			&& $current_year
			&& $current_year->isOpen()}
			{tabitem href="!acc/years/import.php?year=%d"|args:$current_year.id label="Import" name="import" selected=$current}
		{/if}
	</ul>
</nav>