{include file="admin/_head.tpl" title="Journal général" current="compta/gestion"}

<table class="list multi">
    <thead>
        <tr>
            <td>Date</td>
            <th>Intitulé</th>
            <td>Comptes</td>
            <td>Débit</td>
            <td>Crédit</td>
        </tr>
    </thead>
    <tbody>
    {foreach from=$journal item="ligne"}
        <tr>
            <td rowspan="2">{$ligne.date|date_fr:'d/m/Y'|escape}</td>
            <th rowspan="2">{$ligne.libelle|escape}</th>
            <td>{$ligne.compte_debit|escape} - {$ligne.compte_debit|get_nom_compte|escape}</td>
            <td>{$ligne.montant|escape}</td>
            <td></td>
        </tr>
        <tr>
            <td>{$ligne.compte_credit|escape} - {$ligne.compte_credit|get_nom_compte|escape}</td>
            <td></td>
            <td>{$ligne.montant|escape}</td>
        </tr>
    {/foreach}
    </tbody>
</table>

<p class="help">Toutes les opérations sont libellées en {$config.monnaie|escape}.</p>

{include file="admin/_foot.tpl"}