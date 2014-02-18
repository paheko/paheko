{include file="admin/_head.tpl" title="Membres ayant cotisé" current="membres/cotisations"}

<ul class="actions">
    <li class="current"><a href="{$admin_url}membres/cotisations/">Cotisations</a></li>
    <li><a href="{$admin_url}membres/cotisations/ajout.php">Saisie d'une cotisation</a></li>
    <li><a href="{$admin_url}membres/cotisations/rappels.php">État des rappels</a></li>
</ul>

<dl class="cotisation">
    <dt>Cotisation</dt>
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
    <dt>Nombre de membres ayant cotisé</dt>
    <dd>{$cotisation.nb_membres|escape}</dd>
</dl>

{if !empty($liste)}
    <table class="list">
        <thead>
            <td></td>
            <th>Membre</th>
            <td>Statut</td>
            <td>Date de cotisation</td>
            <td></td>
        </thead>
        <tbody>
            {foreach from=$liste item="co"}
                <tr>
                    <td><a class="icn" href="{$admin_url}membres/fiche.php?id={$co.id_membre|escape}">{$co.id_membre|escape}</a></td>
                    <th>{$co.nom|escape}</th>
                    <td>{if $co.a_jour}<b class="confirm">À jour</b>{else}<b class="error">En retard</b>{/if}</td>
                    <td>{$co.date|format_sqlite_date_to_french}</td>
                    <td class="actions">
                        <a href="{$admin_url}membres/cotisations/ajout.php?id={$co.id_membre|escape}&amp;cotisation={$cotisation.id|escape}">Saisir cette cotisation</a>
                        | <a href="{$admin_url}membres/cotisations.php?id={$co.id_membre|escape}">Voir toutes les cotisations de ce membre</a>
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>

    {pagination url=$pagination_url page=$page bypage=$bypage total=$total}
{/if}


{include file="admin/_foot.tpl"}