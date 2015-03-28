{include file="admin/_head.tpl" title="Supprimer une cotisation" current="membres/cotisations"}

<ul class="actions">
    <li class="current"><a href="{$admin_url}membres/cotisations/">Cotisations</a></li>
    <li><a href="{$admin_url}membres/cotisations/ajout.php">Saisie d'une cotisation</a></li>
    {if $user.droits.membres >= Garradin\Membres::DROIT_ADMIN}
        <li><a href="{$admin_url}membres/cotisations/gestion/rappels.php">Gestion des rappels automatiques</a></li>
    {/if}
</ul>

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Supprimer cette cotisation ?</legend>
        <h3 class="warning">
            Êtes-vous sûr de vouloir supprimer la cotisation «&nbsp;{$cotisation.intitule|escape}&nbsp;» ?
        </h3>
        <p class="help">
            Attention, l'historique des membres ayant cotisé à cette cotisation sera supprimé.
            Si des écritures comptables sont liées à l'historique des cotisations, elles ne seront pas supprimées,
            et la comptabilité demeurera inchangée.
        </p>
    </fieldset>

    <p class="submit">
        {csrf_field key="delete_co_"|cat:$cotisation.id}
        <input type="submit" name="delete" value="Supprimer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}