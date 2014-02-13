{include file="admin/_head.tpl" title="Cotisations du membre n°`$membre.id`" current="membres/cotisations"}

<ul class="actions">
    <li><a href="{$admin_url}membres/fiche.php?id={$membre.id|escape}">Membre n°{$membre.id|escape}</a></li>
    <li><a href="{$admin_url}membres/modifier.php?id={$membre.id|escape}">Modifier</a></li>
    {if $user.droits.membres >= Garradin\Membres::DROIT_ADMIN}
        <li><a href="{$admin_url}membres/supprimer.php?id={$membre.id|escape}">Supprimer</a></li>
    {/if}
    <li class="current"><a href="{$admin_url}membres/cotisations.php?id={$membre.id|escape}">Suivi des cotisations</a></li>
</ul>

<dl class="cotisation">
{if $cotisation}
    <dt>Cotisation obligatoire</dt>
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
            {$nb_activites|escape} cotisation enregistrée
        {elseif $nb_activites}
            {$nb_activites|escape} cotisations enregistrées
        {else}
            Aucune cotisation enregistrée
        {/if} 
    </dt>
{if !empty($cotisations_membre)}
    {foreach from=$cotisations_membre item="co"}
    <dd>{$co.intitule|escape} — 
        {if $co.a_jour}
            <span class="confirm">À jour</span>{if $co.expiration} — Expire le {$co.expiration|format_sqlite_date_to_french}{/if}
        {else}
            <span class="error">En retard</span>
        {/if}
    </dd>
    {/foreach}
{/if}
    <dt><form method="get" action="{$admin_url}membres/cotisations/ajout.php"><input type="submit" value="Enregistrer une cotisation &rarr;" /><input type="hidden" name="id" value="{$membre.id|escape}" /></form></dt>
</dl>

{if !empty($cotisations)}
<table class="list">
    <thead>
        <th>Date</th>
        <td>Cotisation</td>
        <td class="actions"></td>
    </thead>
    <tbody>
        {foreach from=$cotisations item="c"}
            <tr>
                <td>{$c.date|format_sqlite_date_to_french}</td>
                <td>
                    {$c.intitule|escape} — 
                    {if $c.duree}
                        {$c.duree|escape} jours
                    {elseif $c.debut}
                        du {$c.debut|format_sqlite_date_to_french} au {$c.fin|format_sqlite_date_to_french}
                    {else}
                        ponctuelle
                    {/if}
                    — {$c.montant|html_money} {$config.monnaie|escape}
                </td>
                <td class="actions">
                    <a href="{$admin_url}membres/cotisations/voir.php?id={$c.id_cotisation|escape}">Voir les autres membres cotisants</a>
                    | <a href="{$admin_url}membres/cotisations/supprimer.php?id={$c.id|escape}">Supprimer</a>
                </td>
            </tr>
        {/foreach}
    </tbody>
</table>
{/if}

{include file="admin/_foot.tpl"}