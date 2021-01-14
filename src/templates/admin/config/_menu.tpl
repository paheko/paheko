<nav class="tabs">
	<ul>
		<li{if $current == 'index'} class="current"{/if}><a href="{$admin_url}config/">Général</a></li>
		<li{if $current == 'categories'} class="current"{/if}><a href="{$admin_url}config/categories/">Catégories de membres</a></li>
		<li{if $current == 'fiches_membres'} class="current"{/if}><a href="{$admin_url}config/membres.php">Fiche des membres</a></li>
		<li{if $current == 'donnees'} class="current"{/if}><a href="{$admin_url}config/donnees/">Sauvegarde et restauration</a></li>
		<li{if $current == 'plugins'} class="current"{/if}><a href="{$admin_url}config/plugins.php">Extensions</a></li>
		{if ENABLE_TECH_DETAILS}
		<li{if $current == 'logs'} class="current"{/if}><a href="{$admin_url}config/logs.php?type=errors">Journaux</a></li>
		{/if}
	</ul>
</nav>