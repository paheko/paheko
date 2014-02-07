{include file="admin/_head.tpl" title="Paiements" current="membres/transactions"}

<ul class="actions">
    <li class="current"><a href="{$admin_url}membres/transactions/">Paiements</a></li>
    <li><a href="{$admin_url}membres/transactions/ajout.php">Saisie d'un paiement</a></li>
    <li><a href="{$admin_url}membres/transactions/rappels.php">État des rappels</a></li>
</ul>

<dl class="cotisation">
    <dt>Cotisation ou activité</dt>
    <dd>{$cotisation.intitule|escape} — 
        {if $cotisation.duree}
            {$cotisation.duree|escape} jours
        {elseif $cotisation.debut}
            du {$cotisation.debut|format_sqlite_date_to_french} au {$cotisation.fin|format_sqlite_date_to_french}
        {else}
            ponctuelle
        {/if}
        — {$cotisation.montant|escape_money} {$config.monnaie|escape}
    </dd>
    <dt>Nombre de paiements liés</dt>
    <dd>{$cotisation.nb_paiements|escape}</dd>
</dl>

{if !empty($liste)}
    <table class="list">
        <thead>
            <td>Membre</td>
            <td>Date</td>
            <th>Libellé</th>
            <td>Montant</td>
            <td></td>
        </thead>
        <tbody>
            {foreach from=$liste item="tr"}
                <tr>
                    <td><a class="icn" href="{$admin_url}membres/fiche.php?id={$tr.id_membre|escape}">{$tr.id_membre|escape}</a></td>
                    <th>{$tr.date|format_sqlite_date_to_french}</th>
                    <td>{$tr.libelle|escape}</td>
                    <td class="num">{$tr.montant|html_money} {$config.monnaie|escape}</td>
                    <td class="actions">
                        <a class="icn" href="{$admin_url}membres/transactions/modifier.php?id={$tr.id|escape}" title="Modifier">✎</a>
                        <a class="icn" href="{$admin_url}membres/transactions/supprimer.php?id={$tr.id|escape}" title="Supprimer">✘</a>
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>

    {pagination url=$pagination_url page=$page bypage=$bypage total=$total}
{/if}


{include file="admin/_foot.tpl"}