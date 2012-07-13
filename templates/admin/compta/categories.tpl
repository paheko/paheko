{include file="admin/_head.tpl" title="Catégories" current="compta/categories"}

<ul class="actions">
    <li{if $type == Garradin_Compta_Categories::RECETTES} class="current"{/if}><a href="?recettes">Recettes</a></li>
    <li{if $type == Garradin_Compta_Categories::DEPENSES} class="current"{/if}><a href="?depenses">Dépenses</a></li>
    <li{if $type == Garradin_Compta_Categories::AUTRES} class="current"{/if}><a href="?autres">Autres</a></li>
    <li><strong><a href="{$www_url}admin/compta/cat_ajouter.php">Ajouter une catégorie</a></strong></li>
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
                <a href="{$www_url}admin/compta/cat_modifier.php?id={$cat.id|escape}">Modifier</a>
                | <a href="{$www_url}admin/compta/cat_supprimer.php?id={$cat.id|escape}">Supprimer</a>
            </dd>
        {/foreach}
        </dl>
    {else}
        <p class="alert">
            Aucune catégorie trouvée.
        </p>
    {/if}

{include file="admin/_foot.tpl"}