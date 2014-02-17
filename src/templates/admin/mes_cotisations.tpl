{include file="admin/_head.tpl" title="Mes cotisations" current="mes_cotisations"}

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
</dl>

{if !empty($cotisations)}
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