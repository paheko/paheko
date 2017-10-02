{include file="admin/_head.tpl" title="Exercices" current="compta/exercices"}

{if !$current_exercice}
<ul class="actions">
    <li><strong><a href="{$www_url}admin/compta/exercices/ajouter.php">Commencer un nouvel exercice</a></strong></li>
</ul>
{/if}

{if !empty($liste)}
    <dl class="catList">
    {foreach from=$liste item="exercice"}
        <dt>{$exercice.libelle}</dt>
        <dd class="desc">
            {if $exercice.cloture}Clôturé{else}En cours{/if}
            | Du {$exercice.debut|date_fr:'d/m/Y'} au {$exercice.fin|date_fr:'d/m/Y'}
        </dd>
        <dd class="compte">
            <strong>{$exercice.nb_operations}</strong> opérations enregistrées.
        </dd>
        <dd class="desc">
            <a href="{$www_url}admin/compta/exercices/journal.php?id={$exercice.id}">Journal général</a>
            | <a href="{$www_url}admin/compta/exercices/grand_livre.php?id={$exercice.id}">Grand livre</a>
            | <a href="{$www_url}admin/compta/exercices/compte_resultat.php?id={$exercice.id}">Compte de résultat</a>
            | <a href="{$www_url}admin/compta/exercices/bilan.php?id={$exercice.id}">Bilan</a>
        </dd>
        {if $session->canAccess('compta', Garradin\Membres::DROIT_ADMIN)}
        <dd class="actions">
            {if !$exercice.cloture}
            <a class="icn" href="{$www_url}admin/compta/exercices/modifier.php?id={$exercice.id}" title="Modifier">✎</a>
            <a class="icn" href="{$www_url}admin/compta/exercices/supprimer.php?id={$exercice.id}" title="Supprimer">✘</a>
            <a class="icn" href="{$www_url}admin/compta/exercices/cloturer.php?id={$exercice.id}" title="Clôturer cet exercice">🔒</a>
            {elseif $exercice.cloture && $exercice.nb_operations == 0}
            <a class="icn" href="{$www_url}admin/compta/exercices/supprimer.php?id={$exercice.id}" title="Supprimer">✘</a>
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