{include file="admin/_head.tpl" title="Écritures comptable pour le paiement n°`$transaction.id`" current="membres/transactions"}

<ul class="actions">
    <li><a href="{$admin_url}membres/fiche.php?id={$membre.id|escape}">Membre n°{$membre.id|escape}</a></li>
    <li><a href="{$admin_url}membres/modifier.php?id={$membre.id|escape}">Modifier</a></li>
    {if $user.droits.membres >= Garradin\Membres::DROIT_ADMIN}
        <li><a href="{$admin_url}membres/supprimer.php?id={$membre.id|escape}">Supprimer</a></li>
    {/if}
    <li class="current"><a href="{$admin_url}membres/transactions.php?id={$membre.id|escape}">Suivi des paiements</a></li>
    <li><a href="{$admin_url}membres/transactions/ajout.php?id={$membre.id|escape}">Enregistrer un paiement</a></li>
</ul>

<dl class="cotisation">
    <dt>Paiement</dt>
    <dd>{$transaction.date|format_sqlite_date_to_french} — {$transaction.libelle|escape} — {$transaction.montant|html_money} {$config.monnaie|escape}</dd>
    <dd>
        <a href="{$admin_url}membres/transactions/modifier.php?id={$transaction.id|escape}">Modifier</a>
        | <a href="{$admin_url}membres/transactions/supprimer.php?id={$transaction.id|escape}">Supprimer</a>
    </dd>
    <dt>Nombre d'écritures comptables liées</dt>
    <dd>{$transaction.nb_operations|escape}</dd>
</dl>

{if !empty($operations)}
<table class="list">
    <thead>
        <th>Date</th>
        <td>Libellé</td>
        <td>Écriture</td>
        <td class="actions"></td>
    </thead>
    <tbody>
        {foreach from=$operations item="operation"}
        <tr>
            <th>{$operation.date|format_sqlite_date_to_french}</th>
            <td>{$operation.libelle|escape}</td>
            <td>
                <table class="list multi">
                    <thead>
                        <tr>
                            <th colspan="2">Comptes</th>
                            <td>Débit</td>
                            <td>Crédit</td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            {if $user.droits.compta >= Garradin\Membres::DROIT_ACCES}
                                <td><a href="{$admin_url}compta/comptes/journal.php?id={$operation.compte_debit|escape}">{$operation.compte_debit|escape}</a></td>
                            {else}
                                <td>{$operation.compte_debit|escape}</td>
                            {/if}
                            <td>{$operation.compte_debit|get_nom_compte}</td>
                            <td>{$operation.montant|html_money}&nbsp;{$config.monnaie|escape}</td>
                            <td></td>
                        </tr>
                        <tr>
                            {if $user.droits.compta >= Garradin\Membres::DROIT_ACCES}
                                <td><a href="{$admin_url}compta/comptes/journal.php?id={$operation.compte_credit|escape}">{$operation.compte_credit|escape}</a></td>
                            {else}
                                <td>{$operation.compte_credit|escape}</td>
                            {/if}
                            <td>{$operation.compte_credit|get_nom_compte}</td>
                            <td></td>
                            <td>{$operation.montant|html_money}&nbsp;{$config.monnaie|escape}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td class="actions">
                {if $user.droits.compta >= Garradin\Membres::DROIT_ACCES}
                    <a class="icn" href="{$admin_url}compta/operations/voir.php?id={$operation.id|escape}" title="Voir les détails de l'opération">❓</a>
                {/if}
                {if $user.droits.compta >= Garradin\Membres::DROIT_ECRITURE}
                    <a class="icn" href="{$admin_url}compta/operations/modifier.php?id={$operation.id|escape}" title="Modifier">✎</a>
                {/if}
                {if $user.droits.compta >= Garradin\Membres::DROIT_ADMIN}
                    <a class="icn" href="{$admin_url}compta/operations/supprimer.php?id={$operation.id|escape}" title="Supprimer">✘</a>
                {/if}
            </td>
        </tr>
        {/foreach}
    </tbody>
</table>
{/if}

{include file="admin/_foot.tpl"}