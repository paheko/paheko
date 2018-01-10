<ul class="actions">
    <li{if $current == 'index'} class="current"{/if}><a href="{$admin_url}config/">Général</a></li>
    <li{if $current == 'membres'} class="current"{/if}><a href="{$admin_url}config/membres.php">Fiche des membres</a></li>
    <li{if $current == 'site'} class="current"{/if}><a href="{$admin_url}config/site.php">Site public</a></li>
    <li{if $current == 'donnees'} class="current"{/if}><a href="{$admin_url}config/donnees.php">Données&nbsp;: sauvegarde et restauration</a></li>
    <li{if $current == 'import'} class="current"{/if}><a href="{$admin_url}config/import.php">Import &amp; export</a></li>
    <li{if $current == 'plugins'} class="current"{/if}><a href="{$admin_url}config/plugins.php">Extensions</a></li>
</ul>
