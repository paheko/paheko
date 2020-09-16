<nav class="tabs">
	<ul>
	    <li{if $current == 'index'} class="current"{/if}><a href="{$admin_url}membres/">Liste des membres</a></li>
	    <li{if $current == 'recherche'} class="current"{/if}><a href="{$admin_url}membres/recherche.php">Recherche avancée</a></li>
	    <li{if $current == 'recherches'} class="current"{/if}><a href="{$admin_url}membres/recherches.php">Recherches enregistrées</a></li>
	    {if $session->canAccess('membres', Membres::DROIT_ADMIN)}
	        <li{if $current == 'sql'} class="current"{/if}><a href="{$admin_url}membres/recherche_sql.php">Recherche SQL</a></li>
	        <li{if $current == 'import'} class="current"{/if}><a href="{$admin_url}membres/import.php">Import &amp; export</a></li>
	    {/if}
	</ul>
</nav>