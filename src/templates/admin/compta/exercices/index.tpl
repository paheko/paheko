{include file="admin/_head.tpl" title="Exercices" current="compta/exercices"}

<ul class="actions">
    <li class="current"><a href="{$admin_url}compta/exercices/">Exercices</a></li>
    <li><a href="{$admin_url}compta/projets/">Projets (compta analytique)</a></li>
</ul>

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
            <a href="{$www_url}admin/compta/rapports/journal.php?exercice={$exercice.id}">Journal général</a>
            | <a href="{$www_url}admin/compta/rapports/grand_livre.php?exercice={$exercice.id}">Grand livre</a>
            | <a href="{$www_url}admin/compta/rapports/compte_resultat.php?exercice={$exercice.id}">Compte de résultat</a>
            | <a href="{$www_url}admin/compta/rapports/bilan.php?exercice={$exercice.id}">Bilan</a>
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