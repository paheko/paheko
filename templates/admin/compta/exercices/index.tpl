{include file="admin/_head.tpl" title="Exercices" current="compta/exercices"}

{if !$current}
<ul class="actions">
    <li><strong><a href="{$www_url}admin/compta/exercices/ajouter.php">Commencer un nouvel exercice</a></strong></li>
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
            <a href="{$www_url}admin/compta/exercices/journal.php?id={$exercice.id|escape}">Journal général</a>
            | <a href="{$www_url}admin/compta/exercices/grand_livre.php?id={$exercice.id|escape}">Grand livre</a>
            | <a href="{$www_url}admin/compta/exercices/compte_resultat.php?id={$exercice.id|escape}">Compte de résultat</a>
            | <a href="{$www_url}admin/compta/exercices/bilan.php?id={$exercice.id|escape}">Bilan</a>
        </dd>
        {if $user.droits.compta >= Garradin_Membres::DROIT_ADMIN}
        <dd class="actions">
            {if !$exercice.cloture}
            <a href="{$www_url}admin/compta/exercices/modifier.php?id={$exercice.id|escape}">Modifier</a>
            | <a href="{$www_url}admin/compta/exercices/cloturer.php?id={$exercice.id|escape}">Clôturer</a>
            | <a href="{$www_url}admin/compta/exercices/supprimer.php?id={$exercice.id|escape}">Supprimer</a>
            {/if}
        </dd>
        {/if}
    {/foreach}
    </dl>
{else}
    <p class="alert">
        Il n'y a pas d'exercice en cours.
    </p>
{/if}

{include file="admin/_foot.tpl"}