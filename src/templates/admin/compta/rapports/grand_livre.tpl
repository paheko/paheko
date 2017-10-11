{include file="admin/_head.tpl" title="Grand livre" current="compta/exercices" body_id="rapport"}

{include file="admin/compta/rapports/_header.tpl"}

{foreach from=$livre.classes key="classe" item="comptes"}
<h3>{$classe|get_nom_compte}</h3>

{foreach from=$comptes item="compte" key="code"}
    {foreach from=$compte.comptes item="souscompte" key="souscode"}
    <table class="list">
        <caption><h4>{$souscode} — {$souscode|get_nom_compte}</h4></caption>
        <colgroup>
            <col width="15%" />
            <col width="55%" />
            <col width="10%" />
            <col width="10%" />
            <col width="10%" />
        </colgroup>
        <thead>
            <tr>
                <td>Date</td>
                <th>Intitulé</th>
                <td class="money">Débit</td>
                <td class="money">Crédit</td>
                <td class="money">Solde</td>
            </tr>
        </thead>
        <tbody>
        {foreach from=$souscompte.journal item="ligne"}
            <tr>
                <td>{$ligne.date|date_fr:'d/m/Y'}</td>
                <th>{$ligne.libelle}</th>
                <td class="money">{if $ligne.compte_debit == $souscode}{$ligne.montant|escape|html_money}{/if}</td>
                <td class="money">{if $ligne.compte_credit == $souscode}{$ligne.montant|escape|html_money}{/if}</td>
                <td class="money">{$ligne.solde|escape|html_money}</td>
            </tr>
        {/foreach}
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <th>Solde final</th>
                <td class="money">{if $souscompte.debit > 0}{$souscompte.debit|escape|html_money}{/if}</td>
                <td class="money">{if $souscompte.credit > 0}{$souscompte.credit|escape|html_money}{/if}</td>
                <td class="money">{$souscompte.solde|escape|html_money}</td>
            </tr>
        </tfoot>
    </table>
    {/foreach}

    <table class="list">
        <colgroup>
            <col width="15%" />
            <col width="65%" />
            <col width="10%" />
            <col width="10%" />
        </colgroup>
        <tfoot>
            <tr>
                <td>Total</td>
                <th>{$code|get_nom_compte}</th>
                <td>{if $compte.total > 0}{$compte.total|abs|escape|html_money}{/if}</td>
                <td>{if $compte.total < 0}{$compte.total|abs|escape|html_money}{/if}</td>
            </tr>
        </tfoot>
    </table>
    {/foreach}
{/foreach}

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
            <td>{$livre.debit|escape|html_money}</td>
            <td>{$livre.credit|escape|html_money}</td>
        </tr>
    </tfoot>
</table>

<p class="help">Toutes les opérations sont libellées en {$config.monnaie}.</p>

{include file="admin/_foot.tpl"}
