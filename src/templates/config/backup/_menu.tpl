<nav class="tabs">
	{if $current == 'restore'}
	<aside>
		{linkbutton shape="upload" label="Restaurer les documents" href="documents.php"}
		{linkbutton shape="upload" label="Restaurer à partir d'un fichier sur mon ordinateur" href="restore.php?from_file=1"}
	</aside>
	{/if}
	<ul class="sub">
		<li{if $current == 'index'} class="current"{/if}><a href="{$admin_url}config/backup/">Sauvegarder</a></li>
		<li{if $current == 'restore'} class="current"{/if}><a href="{$admin_url}config/backup/restore.php">Restaurer</a></li>
		<li{if $current == 'config'} class="current"{/if}><a href="{$admin_url}config/backup/config.php">Configurer</a></li>
	</ul>
</nav>