{include file="admin/_head.tpl" title="Cotisations du membre" current="membres/cotisations"}

<ul class="actions">
    <li><a href="{$admin_url}membres/fiche.php?id={$membre.id}"><b>{$membre.identite}</b></a></li>
    <li><a href="{$admin_url}membres/modifier.php?id={$membre.id}">Modifier</a></li>
    {if $session->canAccess('membres', Garradin\Membres::DROIT_ADMIN) && $user.id != $membre.id}
        <li><a href="{$admin_url}membres/supprimer.php?id={$membre.id}">Supprimer</a></li>
    {/if}
    <li class="current"><a href="{$admin_url}membres/cotisations.php?id={$membre.id}">Suivi des cotisations</a></li>
</ul>

<dl class="cotisation">
{if $cotisation}
    <dt>Cotisation obligatoire</dt>
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
    <dt>À jour de cotisation ?</dt>
    <dd>
        {if !$cotisation.a_jour}
            <span class="error"><b>Non</b>, cotisation non payée</span>
        {else}
            <b class="confirm">&#10003; Oui</b>
            {if $cotisation.expiration}
                (expire le {$cotisation.expiration|format_sqlite_date_to_french})
            {/if}
        {/if}
    </dd>
{/if}
    <dt>
        {if $nb_activites == 1}
            {$nb_activites} cotisation enregistrée
        {elseif $nb_activites}
            {$nb_activites} cotisations enregistrées
        {else}
            Aucune cotisation enregistrée
        {/if} 
    </dt>
{if !empty($cotisations_membre)}
    {foreach from=$cotisations_membre item="co"}
    <dd>{$co.intitule} — 
        {if $co.a_jour}
            <span class="confirm">À jour</span>{if $co.expiration} — Expire le {$co.expiration|format_sqlite_date_to_french}{/if}
        {else}
            <span class="error">En retard</span>
            — <a href="{$admin_url}membres/cotisations/rappels.php?id={$membre.id}">Suivi des rappels</a>
        {/if}
    </dd>
    {/foreach}
{/if}
    <dt><form method="get" action="{$admin_url}membres/cotisations/ajout.php"><input type="submit" value="Enregistrer une cotisation &rarr;" /><input type="hidden" name="id" value="{$membre.id}" /></form></dt>
</dl>

{if !empty($cotisations)}
<table class="list">
    <thead>
        <th>Date</th>
        <td>Cotisation</td>
        <td></td>
        <td class="actions"></td>
    </thead>
    <tbody>
        {foreach from=$cotisations item="c"}
            <tr>
                <td>{$c.date|format_sqlite_date_to_french}</td>
                <td>
                    {$c.intitule} — 
                    {if $c.duree}
                        {$c.duree} jours
                    {elseif $c.debut}
                        du {$c.debut|format_sqlite_date_to_french} au {$c.fin|format_sqlite_date_to_french}
                    {else}
                        ponctuelle
                    {/if}
                    — {$c.montant|escape|html_money} {$config.monnaie}
                </td>
                <td>
                    {if $session->canAccess('compta', Garradin\Membres::DROIT_ECRITURE) && !empty($c.nb_operations)}
                        <a href="{$admin_url}compta/operations/cotisation.php?id={$c.id}">{$c.nb_operations} écriture{if $c.nb_operations > 1}s{/if}</a>
                    {/if}
                </td>
                <td class="actions">
                    <a class="icn" href="{$admin_url}membres/cotisations/voir.php?id={$c.id_cotisation}" title="Liste des membres inscrits à cette cotisation">👪</a>
                    <a class="icn" href="{$admin_url}membres/cotisations/supprimer.php?id={$c.id}" title="Supprimer cette cotisation pour ce membre">✘</a>
                </td>
            </tr>
        {/foreach}
    </tbody>
</table>
{/if}

{include file="admin/_foot.tpl"}