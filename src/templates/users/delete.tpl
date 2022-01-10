{include file="admin/_head.tpl" title="Supprimer un membre" current="membres"}

<nav class="tabs">
    <ul>
        <li><a href="{$admin_url}membres/fiche.php?id={$membre.id}">{$membre.identite}</a></li>
        <li><a href="{$admin_url}membres/modifier.php?id={$membre.id}">Modifier</a></li>
        {if $session->canAccess($session::SECTION_USERS, $session::ACCESS_ADMIN)}
            <li class="current"><a href="{$admin_url}membres/supprimer.php?id={$membre.id}">Supprimer</a></li>
        {/if}
    </ul>
</nav>

{form_errors}

<form method="post" action="{$self_url}">

    <fieldset>
        <legend>Supprimer ce membre ?</legend>
        <h3 class="warning">
            Êtes-vous sûr de vouloir supprimer le membre «&nbsp;{$membre.identite}&nbsp;» ?
        </h3>
        <p class="block alert">
            <strong>Attention</strong> : cette action est irréversible et effacera toutes les
            données personnelles et l'historique de ces membres.
        </p>
        <p class="help">
            Alternativement, il est aussi possible de déplacer les membres qui ne font plus
            partie de l'association dans une catégorie «&nbsp;Anciens membres&nbsp;», plutôt
            que de les effacer complètement.
        </p>
    </fieldset>

    <p class="submit">
        {csrf_field key="delete_membre_"|cat:$membre.id}
        {button type="submit" name="delete" label="Supprimer" shape="delete" class="main"}
    </p>

</form>

{include file="admin/_foot.tpl"}