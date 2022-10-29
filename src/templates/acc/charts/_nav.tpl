<nav class="tabs">

	<ul>
		<li><a href="{$admin_url}acc/years/">Exercices</a></li>
		<li><a href="{$admin_url}acc/projects/">Projets <em>(compta analytique)</em></a></li>
		<li class="current"><a href="{$admin_url}acc/charts/">Plans comptables</a></li>
	</ul>

	<ul class="sub">
		<li{if $current == 'charts'} class="current"{/if}><a href="{$admin_url}acc/charts/">Gestion des plans comptables</a></li>
		<li{if $current == 'install'} class="current"{/if}><a href="{$admin_url}acc/charts/install.php">Installer un plan comptable</a></li>
		<li{if $current == 'import'} class="current"{/if}><a href="{$admin_url}acc/charts/import.php">Importer un plan comptable personnel</a></li>
	</ul>
</nav>
