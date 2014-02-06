{if $membre}
    {include file="admin/_head.tpl" title="Supprimer un paiement pour le membre n°`$membre.id`" current="membres/transactions"}

    <ul class="actions">
        <li><a href="{$admin_url}membres/fiche.php?id={$membre.id|escape}">Membre n°{$membre.id|escape}</a></li>
        <li><a href="{$admin_url}membres/modifier.php?id={$membre.id|escape}">Modifier</a></li>
        {if $user.droits.membres >= Garradin\Membres::DROIT_ADMIN}
            <li><a href="{$admin_url}membres/supprimer.php?id={$membre.id|escape}">Supprimer</a></li>
        {/if}
        <li><a href="{$admin_url}membres/transactions.php?id={$membre.id|escape}">Suivi des paiements</a></li>
        <li><a href="{$admin_url}membres/transactions/ajout.php?id={$membre.id|escape}">Enregistrer un paiement</a></li>
    </ul>
{else}
    {include file="admin/_head.tpl" title="Supprimer un paiement" current="membres/transactions"}

    <ul class="actions">
        <li><a href="{$admin_url}membres/transactions/">Suivi des paiements</a></li>
        <li><a href="{$admin_url}membres/transactions/ajout.php">Saisie d'un paiement</a></li>
        <li><a href="{$admin_url}membres/transactions/rappels.php">État des rappels</a></li>
    </ul>
{/if}

{if $error}
    <p class="error">{$error|escape}</p>
{/if}

<form method="post" action="{$self_url|escape}">
    <fieldset>
        <legend>Supprimer un paiement</legend>
        <h3 class="warning">
            Êtes-vous sûr de vouloir supprimer le paiement «&nbsp;{$transaction.libelle|escape}&nbsp;» 
            du {$transaction.date|format_sqlite_date_to_french}&nbsp;?
        </h3>
        <p class="help">
            Attention, les écritures comptables liées à ce paiement ne seront plus liées
            aux paiements et deviendront orphelines.
        </p>
    </fieldset>
    </fieldset>

    <p class="submit">
        {csrf_field key="del_transaction_`$transaction.id`"}
        <input type="submit" name="delete" value="Supprimer &rarr;" />
    </p>
</form>


{include file="admin/_foot.tpl"}