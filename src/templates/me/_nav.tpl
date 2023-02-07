<nav class="tabs">
	<ul>
		<li{if $current == 'me'} class="current"{/if}><a href="{$admin_url}me/">Mes informations personnelles</a></li>
		<li{if $current == 'security'} class="current"{/if}><a href="{$admin_url}me/security.php">Mot de passe et options de sécurité</a></li>
		<li{if $current == 'preferences'} class="current"{/if}><a href="{$admin_url}me/preferences.php">Préférences</a></li>
	</ul>
</nav>