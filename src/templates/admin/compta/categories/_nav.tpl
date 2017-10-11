<ul class="actions">
    <li{if $current == "recettes"} class="current"{/if}><a href="{$admin_url}compta/categories/?recettes">Recettes</a></li>
    <li{if $current == "depenses"} class="current"{/if}><a href="{$admin_url}compta/categories/?depenses">Dépenses</a></li>
    <li{if $current == "ajouter"} class="current"{/if}><strong><a href="{$admin_url}compta/categories/ajouter.php">Ajouter une catégorie</a></strong></li>
    <li{if $current == "plan"} class="current"{/if}><em><a href="{$admin_url}compta/comptes/">Plan comptable</a></em></li>
</ul>
