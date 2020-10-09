{include file="admin/_head.tpl" title="Grand livre" current="compta/exercices" body_id="rapport"}

{include file="acc/reports/_header.tpl"}

{foreach from=$ledger item="account"}

    <table class="list">
        <caption><h4><a href="{$admin_url}acc/accounts/journal.php?id={$account.id}&amp;year={$year.id}">{$account.code} — {$account.label}</h4></caption>
        <colgroup>
            <col width="10%" />
            <col width="10%" />
            <col width="50%" />
            <col width="10%" />
            <col width="10%" />
            <col width="10%" />
        </colgroup>
        <thead>
            <tr>
                <td>Réf.</td>
                <td>Date</td>
                <th>Intitulé</th>
                <td class="money">Débit</td>
                <td class="money">Crédit</td>
                <td class="money">Solde</td>
            </tr>
        </thead>
        <tbody>
        {foreach from=$account.lines item="line"}
            <tr>
                <td class="num"><a href="{$admin_url}acc/transactions/details.php?id={$line.id}">{if $line.line_reference}{$line.line_reference}{elseif $line.reference}{$line.reference}{else}#{$line.id}{/if}</a></td>
                <td>{$line.date|date_fr:'d/m/Y'}</td>
                <th>{$line.label}{if $line.line_label} <em>({$line.line_label})</em>{/if}</th>
                <td class="money">{if $line.debit}{$line.debit|escape|html_money}{/if}</td>
                <td class="money">{if $line.credit}{$line.credit|escape|html_money}{/if}</td>
                <td class="money">{$line.running_sum|escape|html_money}</td>
            </tr>
        {/foreach}
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td></td>
                <th>Solde final</th>
                <td class="money">{if $account.debit}{$account.debit|escape|html_money}{/if}</td>
                <td class="money">{if $account.credit}{$account.credit|escape|html_money}{/if}</td>
                <td class="money">{$account.sum|escape|html_money}</td>
            </tr>
        </tfoot>
    </table>

{if isset($account->all_debit)}
    <table class="list">
        <colgroup>
            <col width="15%" />
            <col width="65%" />
            <col width="10%" />
            <col width="10%" />
        </colgroup>
        <tfoot>
            <tr>
                <td><strong>Total</strong></td>
                <th></th>
                <td>{$account.all_debit|escape|html_money}</td>
                <td>{$account.all_credit|escape|html_money}</td>
            </tr>
        </tfoot>
    </table>
{/if}

{/foreach}

<p class="help">Toutes les opérations sont libellées en {$config.monnaie}.</p>

{include file="admin/_foot.tpl"}
