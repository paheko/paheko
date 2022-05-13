<nav class="tabs">
	<ul>
		<li{if $current == 'charts'} class="current"{/if}><a href="{$admin_url}acc/charts/">Plans comptables</a></li>
		<li{if $current == 'install'} class="current"{/if}><a href="{$admin_url}acc/charts/install.php">Installer un plan comptable</a></li>
		<li{if $current == 'import'} class="current"{/if}><a href="{$admin_url}acc/charts/import.php">Importer un plan comptable personnel</a></li>
	</ul>
</nav>
