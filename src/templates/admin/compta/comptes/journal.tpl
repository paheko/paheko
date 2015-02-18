{if isset($tpl.get.suivi)}
    {include file="admin/_head.tpl" title="Journal : `$compte.id` - `$compte.libelle`" current="compta/banques" body_id="rapport"}

    <ul class="actions">
        <li><a href="{$www_url}admin/compta/banques/">Comptes bancaires</a></li>
        <li><a href="{$www_url}admin/compta/comptes/journal.php?id={Garradin\Compta\Comptes::CAISSE}">Journal de caisse</a></li>
    </ul>
{else}
    {include file="admin/_head.tpl" title="Journal : `$compte.id` - `$compte.libelle`" current="compta/gestion" body_id="rapport"}
{/if}


<table class="list">
    <colgroup>
        <col width="3%" />
        <col width="3%" />
        <col width="12%" />
        <col width="10%" />
        <col width="12%" />
        <col />
    </colgroup>
    <thead>
        <tr>
            <td></td>
            <td></td>
            <td>Date</td>
            <td>Montant</td>
            <td>Solde cumulé</td>
            <th>Libellé</th>
        </tr>
    </thead>
    <tbody>
    {foreach from=$journal item="ligne"}
        <tr>
            <td class="num"><a href="{$admin_url}compta/operations/voir.php?id={$ligne.id|escape}">{$ligne.id|escape}</a></td>
            <td class="actions">
            {if $user.droits.compta >= Garradin\Membres::DROIT_ADMIN}
                <a class="icn" href="{$admin_url}compta/operations/modifier.php?id={$ligne.id|escape}" title="Modifier cette opération">✎</a>
            {/if}
            </td>
            <td>{$ligne.date|date_fr:'d/m/Y'|escape}</td>
            <td>{if $ligne.compte_credit == $compte.id}{$credit}{else}{$debit}{/if}{$ligne.montant|html_money}</td>
            <td>{$ligne.solde|html_money}</td>
            <th>{$ligne.libelle|escape}</th>
        </tr>
    {/foreach}
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3"></td>
            <th>Solde</th>
            <td colspan="2">{$solde|html_money} {$config.monnaie|escape}</td>
        </tr>
    </tfoot>
</table>

{include file="admin/_foot.tpl"}