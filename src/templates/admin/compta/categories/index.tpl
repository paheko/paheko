{include file="admin/_head.tpl" title="Catégories" current="compta/categories"}

{include file="admin/compta/categories/_nav.tpl" current=$current_nav}

    {if !empty($liste)}
        <dl class="catList">
        {foreach from=$liste item="cat"}
            <dt>{$cat.intitule}</dt>
            {if !empty($cat.description)}
                <dd class="desc">{$cat.description}</dd>
            {/if}
            <dd class="compte"><strong>{$cat.compte}</strong> - {$cat.compte_libelle}</dd>
            <dd class="actions">
                <a class="icn" href="{$www_url}admin/compta/operations/?cat={$cat.id}" title="Lister les opérations de cette catégorie">𝍢</a>
                <a class="icn" href="{$www_url}admin/compta/categories/modifier.php?id={$cat.id}" title="Modifier">✎</a>
                <a class="icn" href="{$www_url}admin/compta/categories/supprimer.php?id={$cat.id}" title="Supprimer">✘</a>
            </dd>
        {/foreach}
        </dl>
    {else}
        <p class="alert">
            Aucune catégorie trouvée.
        </p>
    {/if}

{include file="admin/_foot.tpl"}