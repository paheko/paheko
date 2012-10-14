{include file="admin/_head.tpl" title="Exercices" current="compta/exercices"}

{if !$current}
<ul class="actions">
    <li><strong><a href="{$www_url}admin/compta/exercice_ajouter.php">Commencer un nouvel exercice</a></strong></li>
</ul>
{/if}

{if !empty($liste)}
    <dl class="catList">
    {foreach from=$liste item="exercice"}
        <dt>{$exercice.libelle|escape}</dt>
        <dd class="desc">
            {if $exercice.cloture}Clôturé{else}En cours{/if}
            | Du {$exercice.debut|date_fr:'d/m/Y'} au {$exercice.fin|date_fr:'d/m/Y'}
        </dd>
        <dd class="compte">
            <strong>{$exercice.nb_operations|escape}</strong> opérations enregistrées.
        </dd>
        <dd class="desc">
            <a href="{$www_url}admin/compta/rapport/journal.php?exercice={$exercice.id|escape}">Journal général</a>
            | <a href="{$www_url}admin/compta/rapport/grand_livre.php?exercice={$exercice.id|escape}">Grand livre</a>
            | <a href="{$www_url}admin/compta/rapport/compte_resultat.php?exercice={$exercice.id|escape}">Compte de résultat</a>
            | <a href="{$www_url}admin/compta/rapport/bilan.php?exercice={$exercice.id|escape}">Bilan</a>
        </dd>
        <dd class="actions">
            {if !$exercice.cloture}
            <a href="{$www_url}admin/compta/exercice_modifier.php?id={$exercice.id|escape}">Modifier</a>
            | <a href="{$www_url}admin/compta/exercice_cloturer.php?id={$exercice.id|escape}">Clôturer</a> |
            {/if}
            <a href="{$www_url}admin/compta/exercice_supprimer.php?id={$exercice.id|escape}">Supprimer</a>
        </dd>
    {/foreach}
    </dl>
{else}
    <p class="alert">
        Il n'y a pas d'exercice en cours.
    </p>
{/if}

{include file="admin/_foot.tpl"}