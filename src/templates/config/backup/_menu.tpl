<nav class="tabs">
	<ul class="sub">
		<li{if $current == 'index'} class="current"{/if}><a href="{$admin_url}config/backup/">Sauvegarder</a></li>
		<li{if $current == 'restore'} class="current"{/if}><a href="{$admin_url}config/backup/restore.php">Restaurer</a></li>
		<li{if $current == 'auto'} class="current"{/if}><a href="{$admin_url}config/backup/auto.php">Sauvegardes automatiques</a></li>
		<li{if $current == 'versions'} class="current"{/if}><a href="{$admin_url}config/backup/versions.php">Versionnement des fichiers</a></li>
	</ul>
</nav>