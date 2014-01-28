{include file="admin/_head.tpl" title="Supprimer une activité" current="membres/transactions/admin"}

<ul class="actions">
    <li class="current"><a href="{$admin_url}membres/transactions/gestion/">Cotisations et activités</a></li>
    <li><a href="{$admin_url}membres/transactions/gestion/rappels.php">Gestion des rappels</a></li>
    <li><a href="{$admin_url}membres/transactions/rappels.php">État des rappels</a></li>
</ul>

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Supprimer cette activité ou cotisation ?</legend>
        <h3 class="warning">
            Êtes-vous sûr de vouloir supprimer l'activité «&nbsp;{$transaction.intitule|escape}&nbsp;» ?
        </h3>
        <p class="help">
            Attention, l'activité ne doit plus être liée à des paiements existantspour pouvoir
            être supprimée.
        </p>
    </fieldset>

    <p class="submit">
        {csrf_field key="delete_tr_"|cat:$transaction.id}
        <input type="submit" name="delete" value="Supprimer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}