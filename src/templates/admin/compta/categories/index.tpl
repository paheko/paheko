{include file="admin/_head.tpl" title="Catégories" current="compta/categories"}

<ul class="actions">
    <li{if $type == Garradin\Compta\Categories::RECETTES} class="current"{/if}><a href="?recettes">Recettes</a></li>
    <li{if $type == Garradin\Compta\Categories::DEPENSES} class="current"{/if}><a href="?depenses">Dépenses</a></li>
    <li><strong><a href="{$www_url}admin/compta/categories/ajouter.php">Ajouter une catégorie</a></strong></li>
    <li><em><a href="{$www_url}admin/compta/comptes/">Plan comptable</a></em></li>
</ul>

    {if !empty($liste)}
        <dl class="catList">
        {foreach from=$liste item="cat"}
            <dt>{$cat.intitule|escape}</dt>
            {if !empty($cat.description)}
                <dd class="desc">{$cat.description|escape}</dd>
            {/if}
            <dd class="compte"><strong>{$cat.compte|escape}</strong> - {$cat.compte_libelle|escape}</dd>
            <dd class="actions">
                <a class="icn" href="{$www_url}admin/compta/operations/?cat={$cat.id|escape}" title="Lister les opérations de cette catégorie">𝍢</a>
                <a class="icn" href="{$www_url}admin/compta/categories/modifier.php?id={$cat.id|escape}" title="Modifier">✎</a>
                <a class="icn" href="{$www_url}admin/compta/categories/supprimer.php?id={$cat.id|escape}" title="Supprimer">✘</a>
            </dd>
        {/foreach}
        </dl>
    {else}
        <p class="alert">
            Aucune catégorie trouvée.
        </p>
    {/if}

{include file="admin/_foot.tpl"}