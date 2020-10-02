{include file="admin/_head.tpl" title="Exercices" current="acc/years"}

<nav class="tabs">
    <ul>
        <li class="current"><a href="{$self_url}">Exercices</a></li>
        <li><a href="{$admin_url}acc/years/new.php">Nouvel exercice</a></li>
    </ul>
</nav>

{if !empty($list)}
    <dl class="list">
    {foreach from=$list item="year"}
        <dt>{$year.label}</dt>
        <dd class="desc">
            {if $year.closed}Clôturé{else}En cours{/if}
            | Du {$year.start_date|date_fr:'d/m/Y'} au {$year.end_date|date_fr:'d/m/Y'}
        </dd>
        <dd class="desc">
            <a href="{$admin_url}acc/reports/journal.php?exercice={$year.id}">Journal général</a>
            | <a href="{$admin_url}acc/reports/grand_livre.php?exercice={$year.id}">Grand livre</a>
            | <a href="{$admin_url}acc/reports/compte_resultat.php?exercice={$year.id}">Compte de résultat</a>
            | <a href="{$admin_url}acc/reports/bilan.php?exercice={$year.id}">Bilan</a>
        </dd>
        {if $session->canAccess('compta', Membres::DROIT_ADMIN) && !$year.closed}
        <dd class="actions">
            {linkbutton label="Modifier" shape="edit" href="acc/years/edit.php?id=%d"|args:$year.id}
            {linkbutton label="Clôturer" shape="lock" href="acc/years/close.php?id=%d"|args:$year.id}
            {linkbutton label="Supprimer" shape="delete" href="acc/years/delete.php?id=%d"|args:$year.id}
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