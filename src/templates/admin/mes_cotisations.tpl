{include file="admin/_head.tpl" title="Mes cotisations" current="mes_cotisations"}

<dl class="cotisation">
    <dt>
        {if $nb_activites == 1}
            Vous avez {$nb_activites|escape} cotisation enregistrée.
        {elseif $nb_activites}
            Vous avez {$nb_activites|escape} cotisations enregistrées.
        {else}
            Vous n'avez aucune cotisation enregistrée.
        {/if} 
    </dt>
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
    <dd>
        {if !$cotisation.a_jour}
            <b class="error">Vous n'êtes pas à jour de cotisation</b>
        {else}
            <b class="confirm">&#10003; À jour de cotisation</b>
            {if $cotisation.expiration}
                (expire le {$cotisation.expiration|format_sqlite_date_to_french})
            {/if}
        {/if}
    </dd>
{/if}
{if !empty($cotisations_membre)}
    <dt>Cotisations en cours</dt>
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
</dl>

{if !empty($cotisations)}
<div class="infos">
    <h3>Historique des cotisations</h3>
</div>

<table class="list">
    <thead>
        <th>Date</th>
        <td>Cotisation</td>
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
            </tr>
        {/foreach}
    </tbody>
</table>
{/if}

{include file="admin/_foot.tpl"}