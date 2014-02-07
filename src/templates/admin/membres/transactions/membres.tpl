{include file="admin/_head.tpl" title="Membres ayant payé une activité ou cotisation" current="membres/transactions"}

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
    <dt>Nombre de membres liés</dt>
    <dd>{$cotisation.nb_membres|escape}</dd>
</dl>

{if !empty($liste)}
    <table class="list">
        <thead>
            <td></td>
            <th>Nom</th>
            <td>Paiement</td>
            <td></td>
        </thead>
        <tbody>
            {foreach from=$liste item="tr"}
                <tr>
                    <td><a class="icn" href="{$admin_url}membres/fiche.php?id={$tr.id_membre|escape}">{$tr.id_membre|escape}</a></td>
                    <th>{$tr.nom|escape}</th>
                    <td>
                        {if $tr.a_payer > 0}
                            <b class="alert">Partiel</b>
                        {elseif $tr.a_jour}
                            <span class="confirm">À jour</span>
                        {else}
                            <b class="error">Expiré</b>
                        {/if}
                    </td>
                    <td class="actions">
                        <a href="{$admin_url}membres/transactions.php?id={$tr.id_membre|escape}">Activités &amp; cotisations de ce membre</a>
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>

    {pagination url=$pagination_url page=$page bypage=$bypage total=$total}
{/if}


{include file="admin/_foot.tpl"}