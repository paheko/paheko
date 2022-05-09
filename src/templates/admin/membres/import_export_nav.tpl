<nav class="tabs">
    <ul class="sub">
        <li{if $current == 'import'} class="current"{/if}><a href="{$admin_url}membres/import.php">Importer</a></li>
        <li{if $current == 'export'} class="current"{/if}><a href="{$admin_url}membres/export.php">Exporter</a></li>
    </ul>
</nav>
