{include file="admin/_head.tpl" title="Supprimer une cotisation pour le membre n°%s"|args:$membre.id current="membres/cotisations"}

<ul class="actions">
    <li><a href="{$admin_url}membres/fiche.php?id={$membre.id}">Membre n°{$membre.id}</a></li>
    <li><a href="{$admin_url}membres/modifier.php?id={$membre.id}">Modifier</a></li>
    {if $session->canAccess('membres', Garradin\Membres::DROIT_ADMIN) && $user.id != $membre.id}
        <li><a href="{$admin_url}membres/supprimer.php?id={$membre.id}">Supprimer</a></li>
    {/if}
    <li class="current"><a href="{$admin_url}membres/cotisations.php?id={$membre.id}">Suivi des cotisations</a></li>
</ul>

{form_errors}

<form method="post" action="{$self_url}">
    <fieldset>
        <legend>Supprimer une cotisation membre</legend>
        <h3 class="warning">
            Êtes-vous sûr de vouloir supprimer la cotisation membre
            du {$cotisation.date|format_sqlite_date_to_french}&nbsp;?
        </h3>
        {if $nb_operations > 0}
            <p class="alert">
                Attention il y a {$nb_operations} écritures comptables liées à cette cotisation.
                Celles-ci ne seront pas supprimées lors de la suppression de la cotisation membre.
            </p>
        {else}
            <p class="help">
                Aucune écriture comptable n'est liée à cette cotisation.
            </p>
        {/if}
    </fieldset>
    </fieldset>

    <p class="submit">
        {csrf_field key="del_cotisation_%s"|args:$cotisation.id}
        <input type="submit" name="delete" value="Supprimer &rarr;" />
    </p>
</form>


{include file="admin/_foot.tpl"}