<nav class="tabs">
	<ul>
		<li{if $current == 'index'} class="current"{/if}><a href="{$admin_url}config/">Général</a></li>
		<li{if $current == 'categories'} class="current"{/if}><a href="{$admin_url}config/categories/">Catégories de membres</a></li>
		<li{if $current == 'fiches_membres'} class="current"{/if}><a href="{$admin_url}config/membres.php">Fiche des membres</a></li>
		<li{if $current == 'donnees'} class="current"{/if}><a href="{$admin_url}config/donnees/">Sauvegardes</a></li>
		<li{if $current == 'plugins'} class="current"{/if}><a href="{$admin_url}config/plugins.php">Extensions</a></li>
		<li{if $current == 'advanced'} class="current"{/if}><a href="{$admin_url}config/advanced/">Fonctions avancées</a></li>
	</ul>

	{if $current == 'advanced'}
	<ul class="sub">
		<li{if !$sub_current} class="current"{/if}><a href="{$admin_url}config/advanced/">Fonctions avancées</a></li>
		<li{if $sub_current == 'sql'} class="current"{/if}><a href="{$admin_url}config/advanced/sql.php">SQL</a></li>
		{if ENABLE_TECH_DETAILS}
		<li{if $sub_current == 'errors'} class="current"{/if}><a href="{$admin_url}config/advanced/errors.php">Journal d'erreurs</a></li>
		{/if}
	</ul>
	{/if}
</nav>