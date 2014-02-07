{include file="admin/_head.tpl" title="Paiements" current="membres/transactions"}

<ul class="actions">
    <li class="current"><a href="{$admin_url}membres/transactions/">Paiements</a></li>
    <li><a href="{$admin_url}membres/transactions/ajout.php">Saisie d'un paiement</a></li>
    <li><a href="{$admin_url}membres/transactions/rappels.php">État des rappels</a></li>
</ul>

<table class="list">
    <thead>
        <th>Activité ou cotisation</th>
        <td>Période</td>
        <td>Montant</td>
        <td>Nombre de paiements</td>
        <td>Nombre de membres inscrits</td>
        <td></td>
    </thead>
    <tbody>
        {foreach from=$liste item="tr"}
            <tr>
                <th>{$tr.intitule|escape}</th>
                <td>
                    {if $tr.duree}
                        {$tr.duree|escape} jours
                    {elseif $tr.debut}
                        du {$tr.debut|format_sqlite_date_to_french} au {$tr.fin|format_sqlite_date_to_french}
                    {else}
                        ponctuelle
                    {/if}
                </td>
                <td class="num">{$tr.montant|html_money} {$config.monnaie|escape}</td>
                <td class="num">{$tr.nb_paiements|escape}</td>
                <td class="num">{$tr.nb_membres|escape}</td>
                <td class="actions">
                    <a href="{$admin_url}membres/transactions/voir.php?id={$tr.id|escape}">Paiements</a>
                </td>
            </tr>
        {/foreach}
    </tbody>
</table>


{include file="admin/_foot.tpl"}