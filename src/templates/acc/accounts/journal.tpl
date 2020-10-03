{include file="admin/_head.tpl" title="Journal : %s - %s"|args:$account.code:$account.label current="acc/accounts" body_id="rapport"}

{include file="acc/_year_select.tpl"}

<table class="list">
    <colgroup>
        <col width="3%" />
        <col width="12%" />
        <col width="10%" />
        <col width="10%" />
        <col width="12%" />
        <col />
        <col width="6%" />
    </colgroup>
    <thead>
        <tr>
            <td>N°</td>
            <td>Date</td>
            <td>Débit</td>
            <td>Crédit</td>
            <td>Solde cumulé</td>
            <th>Libellé</th>
            <td></td>
        </tr>
    </thead>
    <tbody>
    {foreach from=$journal item="line"}
        <tr>
            <td class="num"><a href="{$admin_url}acc/transactions/details.php?id={$line.id}">{$line.id}</a></td>
            <td>{$line.date|date_fr:'d/m/Y'}</td>
            <td class="money">{if $line.debit}{$line.debit|escape|html_money}{/if}</td>
            <td class="money">{if $line.credit}{$line.credit|escape|html_money}{/if}</td>
            <td class="money">{$line.running_sum|escape|html_money}</td>
            <th>{$line.label}</th>
            <td class="actions">
                {linkbutton href="acc/transactions/details.php?id=%d"|args:$line.id label="Détails" shape="search"}
            </td>
        </tr>
    {/foreach}
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4">Solde</td>
            <td class="money">{$sum|escape|html_money}</td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
</table>

{include file="admin/_foot.tpl"}