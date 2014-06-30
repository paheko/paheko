{include file="admin/_head.tpl" title="Grand livre" current="compta/exercices" body_id="rapport"}

<div class="exercice">
    <h2>{$config.nom_asso|escape}</h2>
    <p>Exercice comptable {if $exercice.cloture}clôturé{else}en cours{/if} du
        {$exercice.debut|date_fr:'d/m/Y'} au {$exercice.fin|date_fr:'d/m/Y'}, généré le {$cloture|date_fr:'d/m/Y'}</p>
</div>

{foreach from=$livre.classes key="classe" item="comptes"}
<h3>{$classe|get_nom_compte|escape}</h3>

{foreach from=$comptes item="compte" key="code"}
    {foreach from=$compte.comptes item="souscompte" key="souscode"}
    <table class="list">
        <caption><h4>{$souscode|escape} — {$souscode|get_nom_compte|escape}</h4></caption>
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
                <td>{$ligne.date|date_fr:'d/m/Y'|escape}</td>
                <th>{$ligne.libelle|escape}</th>
                <td class="money">{if $ligne.compte_debit == $souscode}{$ligne.montant|html_money}{/if}</td>
                <td class="money">{if $ligne.compte_credit == $souscode}{$ligne.montant|html_money}{/if}</td>
                <td class="money">{$ligne.solde|html_money}</td>
            </tr>
        {/foreach}
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <th>Solde final</th>
                <td class="money">{if $souscompte.debit > 0}{$souscompte.debit|html_money}{/if}</td>
                <td class="money">{if $souscompte.credit > 0}{$souscompte.credit|html_money}{/if}</td>
                <td class="money">{$souscompte.solde|html_money}</td>
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
                <td>{if $compte.total > 0}{$compte.total|abs|html_money}{/if}</td>
                <td>{if $compte.total < 0}{$compte.total|abs|html_money}{/if}</td>
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
            <td>{$livre.debit|html_money}</td>
            <td>{$livre.credit|html_money}</td>
        </tr>
    </tfoot>
</table>

<p class="help">Toutes les opérations sont libellées en {$config.monnaie|escape}.</p>

{include file="admin/_foot.tpl"}
