{include file="admin/_head.tpl" title="Grand livre" current="compta/gestion"}

<div id="gdlivre">
    {foreach from=$livre key="classe" item="comptes"}
    <h3>{$classe|get_nom_compte|escape}</h3>

    {foreach from=$comptes item="compte" key="code"}
        {foreach from=$compte.comptes item="journal" key="subcode"}
        <table class="list">
            <caption><h4>{$subcode|escape} — {$subcode|get_nom_compte|escape}</h4></caption>
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
            {foreach from=$journal item="ligne"}
                <tr>
                    <td>{$ligne.date|date_fr:'d/m/Y'|escape}</td>
                    <th>{$ligne.libelle|escape}</th>
                    <td>{if $ligne.compte_debit == $subcode}{$ligne.montant|escape_money}{/if}</td>
                    <td>{if $ligne.compte_credit == $subcode}{$ligne.montant|escape_money}{/if}</td>
                </tr>
            {/foreach}
            </tbody>
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
</div>

{include file="admin/_foot.tpl"}