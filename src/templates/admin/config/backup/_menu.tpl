<nav class="tabs">
	<ul class="sub">
		<li{if $current == 'index'} class="current"{/if}><a href="{$admin_url}config/backup/">Informations</a></li>
		<li{if $current == 'save'} class="current"{/if}><a href="{$admin_url}config/backup/save.php">Sauvegarder</a></li>
		<li{if $current == 'restore'} class="current"{/if}><a href="{$admin_url}config/backup/restore.php">Restaurer</a></li>
	</ul>
</nav>