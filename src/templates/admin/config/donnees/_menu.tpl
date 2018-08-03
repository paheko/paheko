<ul class="actions sub">
	<li{if $current == 'index'} class="current"{/if}><a href="{$admin_url}config/donnees/">Sauvegarder et restaurer</a></li>
	<li{if $current == 'import'} class="current"{/if}><a href="{$admin_url}config/donnees/import.php">Import et export</a></li>
	{if ENABLE_AUTOMATIC_BACKUPS}
	<li{if $current == 'local'} class="current"{/if}><a href="{$admin_url}config/donnees/local.php">Gestion des sauvegardes</a></li>
	<li{if $current == 'automatique'} class="current"{/if}><a href="{$admin_url}config/donnees/automatique.php">Configuration de la sauvegarde automatique</a></li>
	{/if}
</ul>
