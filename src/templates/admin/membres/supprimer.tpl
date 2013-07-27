{include file="admin/_head.tpl" title="Supprimer un membre" current="membres"}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Supprimer ce membre ?</legend>
        <h3 class="warning">
            Êtes-vous sûr de vouloir supprimer le membre «&nbsp;{$membre.nom|escape}&nbsp;» ?
        </h3>
        <p class="alert">
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
        <input type="submit" name="delete" value="Supprimer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}