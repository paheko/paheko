{include file="admin/_head.tpl" title="Grand livre" current="compta"}

<ul class="actions">
    <li class="journal current"><a href="{$www_url}admin/compta/journal.php">Journal général</a></li>
</ul>

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
            <td rowspan="2">{$ligne.date|strtotime|date_fr:'d/m/Y'|escape}</td>
            <th rowspan="2">{$ligne.libelle|escape}</th>
            <td>{$ligne.compte_debit|escape} - {$ligne.compte_debit|get_nom_compte}</td>
            <td>{$ligne.montant|escape}&nbsp;{$config.monnaie|escape}</td>
            <td></td>
        </tr>
        <tr>
            <td>{$ligne.compte_credit|escape} - {$ligne.compte_credit|get_nom_compte}</td>
            <td></td>
            <td>{$ligne.montant|escape}&nbsp;{$config.monnaie|escape}</td>
        </tr>
    {/foreach}
    </tbody>
</table>

{include file="admin/_foot.tpl"}