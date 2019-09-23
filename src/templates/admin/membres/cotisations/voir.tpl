{include file="admin/_head.tpl" title="Membres ayant cotisé" current="membres/cotisations"}

<ul class="actions">
    <li class="current"><a href="{$admin_url}membres/cotisations/">Cotisations</a></li>
    {if $session->canAccess('membres', Membres::DROIT_ECRITURE)}
        <li><a href="{$admin_url}membres/cotisations/ajout.php">Saisie d'une cotisation</a></li>
    {/if}
    {if $session->canAccess('membres', Membres::DROIT_ADMIN)}
        <li><a href="{$admin_url}membres/cotisations/gestion/rappels.php">Gestion des rappels automatiques</a></li>
    {/if}
</ul>

<dl class="cotisation">
    <dt>Cotisation</dt>
    <dd>{$cotisation.intitule} — 
        {if $cotisation.duree}
            {$cotisation.duree} jours
        {elseif $cotisation.debut}
            du {$cotisation.debut|format_sqlite_date_to_french} au {$cotisation.fin|format_sqlite_date_to_french}
        {else}
            ponctuelle
        {/if}
        — {$cotisation.montant|escape|html_money} {$config.monnaie}
    </dd>
    <dd>
        {if !$cats}
            <a href="?id={$cotisation.id}&amp;cats=1">Afficher les membres des catégories pour lesquelles cette cotisation est obligatoire</a>
        {else}
            <a href="?id={$cotisation.id}">Afficher seulement les membres à jour (ou qui ont déjà payé, mais ne sont plus à jour)</a>
        {/if}
    </dd>    <dt>Nombre de membres ayant cotisé</dt>
    <dd>
        {$cotisation.nb_membres}
        <small class="help">(incluant les membres des catégories cachées)</small>
    </dd>
</dl>

{if !empty($liste)}
    <table class="list">
        <thead class="userOrder">
            <tr>
                <td class="{if $order == "id"} cur {if $desc}desc{else}asc{/if}{/if}"><a href="?id={$cotisation.id}&amp;o=id&amp;a&amp;cats={$cats}" class="icn up">&uarr;</a><a href="?id={$cotisation.id}&amp;o=id&amp;d&amp;cats={$cats}" class="icn dn">&darr;</a></td>
                <th class="{if $order == "identite"} cur {if $desc}desc{else}asc{/if}{/if}">Membre <a href="?id={$cotisation.id}&amp;o=identite&amp;a&amp;cats={$cats}" class="icn up">&uarr;</a><a href="?id={$cotisation.id}&amp;o=identite&amp;d&amp;cats={$cats}" class="icn dn">&darr;</a></th>
                <td class="{if $order == "a_jour"} cur {if $desc}desc{else}asc{/if}{/if}">Statut <a href="?id={$cotisation.id}&amp;o=a_jour&amp;a&amp;cats={$cats}" class="icn up">&uarr;</a><a href="?id={$cotisation.id}&amp;o=a_jour&amp;d&amp;cats={$cats}" class="icn dn">&darr;</a></td>
                <td class="{if $order == "date"} cur {if $desc}desc{else}asc{/if}{/if}">Date de cotisation <a href="?id={$cotisation.id}&amp;o=date&amp;a" class="icn up">&uarr;</a><a href="?id={$cotisation.id}&amp;o=date&amp;d&amp;cats={$cats}" class="icn dn">&darr;</a></td>
                <td></td>
            </tr>
        </thead>
        <tbody>
            {foreach from=$liste item="co"}
                <tr>
                    <td class="num">{$co.numero}</td>
                    <th><a href="{$admin_url}membres/fiche.php?id={$co.id_membre}" class="icn">{$co.nom}</a></th>
                    <td>{if $co.a_jour}<b class="confirm">À jour</b>{else}<b class="error">En retard</b>{/if}</td>
                    <td>{$co.date|format_sqlite_date_to_french}</td>
                    <td class="actions">
                        {if $session->canAccess('membres', Membres::DROIT_ECRITURE)}
                        <a class="icn" href="{$admin_url}membres/cotisations/ajout.php?id={$co.id_membre}&amp;cotisation={$cotisation.id}" title="Saisir une cotisation">➕</a>
                        {/if}
                        <a class="icn" href="{$admin_url}membres/cotisations.php?id={$co.id_membre}" title="Voir toutes les cotisations de ce membre">𝍢</a>
                        <a class="icn" href="{$admin_url}membres/cotisations/rappels.php?id={$co.id_membre}" title="Rappels envoyés à ce membre">⚠</a>
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>

    {pagination url=$pagination_url page=$page bypage=$bypage total=$total}
{/if}


{include file="admin/_foot.tpl"}