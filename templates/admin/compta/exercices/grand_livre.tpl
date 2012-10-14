{include file="admin/_head.tpl" title="Grand livre" current="compta/gestion"}

<div id="gdlivre">
    <h2>En date du {$now|date_fr:'d/m/Y'}</h2>
    {foreach from=$livre.classes key="classe" item="comptes"}
    <h3>{$classe|get_nom_compte|escape}</h3>

    {foreach from=$comptes item="compte" key="code"}
        {foreach from=$compte.comptes item="souscompte" key="souscode"}
        <table class="list">
            <caption><h4>{$souscode|escape} — {$souscode|get_nom_compte|escape}</h4></caption>
            <colgroup>
                <col width="15%" />
                <col width="65%" />
                <col width="10%" />
                <col width="10%" />
            </colgroup>
            <thead>
                <tr>
                    <td>Date</td>
                    <th>Intitulé</th>
                    <td>Débit</td>
                    <td>Crédit</td>
                </tr>
            </thead>
            <tbody>
            {foreach from=$souscompte.journal item="ligne"}
                <tr>
                    <td>{$ligne.date|date_fr:'d/m/Y'|escape}</td>
                    <th>{$ligne.libelle|escape}</th>
                    <td>{if $ligne.compte_debit == $souscode}{$ligne.montant|escape_money}{/if}</td>
                    <td>{if $ligne.compte_credit == $souscode}{$ligne.montant|escape_money}{/if}</td>
                </tr>
            {/foreach}
            </tbody>
            <tfoot>
                <tr>
                    <td></td>
                    <th>Solde final</th>
                    <td>{if $souscompte.debit > 0}{$souscompte.debit|escape_money}{/if}</td>
                    <td>{if $souscompte.credit > 0}{$souscompte.credit|escape_money}{/if}</td>
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
                    <th>{$code|get_nom_compte|escape}</th>
                    <td>{if $compte.total > 0}{$compte.total|abs|escape_money}{/if}</td>
                    <td>{if $compte.total < 0}{$compte.total|abs|escape_money}{/if}</td>
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
                <td>{$livre.debit|escape_money}</td>
                <td>{$livre.credit|escape_money}</td>
            </tr>
        </tfoot>
    </table>
</div>

{include file="admin/_foot.tpl"}