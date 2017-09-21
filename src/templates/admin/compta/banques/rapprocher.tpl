{include file="admin/_head.tpl" title="Rapprochement — %s"|args:$compte.id current="compta/banques" js=1}

<ul class="actions">
    <li><a href="{$www_url}admin/compta/banques/">Comptes bancaires</a></li>
    <li><a href="{$www_url}admin/compta/comptes/journal.php?id={$id_caisse}&amp;suivi">Journal de caisse</a></li>
    <li class="current"><a href="{$self_url_no_qs}?id={$compte.id}">Rapprochement</a></li>
</ul>

<form method="get" action="{$self_url_no_qs}">
    {if !empty($prev) && !empty($next)}
    <fieldset class="shortFormRight">
        <legend>Rapprochement par mois</legend>
        <dl>
            <dd class="actions">
            <a class="icn" href="{$self_url_no_qs}?id={$compte.id}&amp;debut={$prev|date_fr:'Y-m-01'}&amp;fin={$prev|date_fr:'Y-m-t'}{if \Garradin\qg('sauf')}&amp;sauf=1{/if}">&larr; {$prev|date_fr:'F Y'}</a>
            | <a class="icn" href="{$self_url_no_qs}?id={$compte.id}&amp;debut={$next|date_fr:'Y-m-01'}&amp;fin={$next|date_fr:'Y-m-t'}{if \Garradin\qg('sauf')}&amp;sauf=1{/if}">{$next|date_fr:'F Y'} &rarr;</a>
            </dd>
        </dl>
    </fieldset>
    {/if}
    <fieldset>
        <legend>Période de rapprochement</legend>
        <p>
            Du
            <span><input type="date" name="debut" id="f_debut" value="{$debut}" /></span>
            au
            <span><input type="date" name="fin" id="f_fin" value="{$fin}" /></span>
            <label><input type="checkbox" name="sauf" value="1" {if \Garradin\qg('sauf')} checked="checked"{/if} /> Ne pas inclure les écritures déjà rapprochées</label>
            <input type="hidden" name="id" value="{$compte.id}" />
            <input type="submit" value="Afficher" />
        </p>
    </fieldset>
</form>

{form_errors}

<form method="post" action="{$self_url}">
    <table class="list">
        <colgroup>
            <col width="3%" />
            <col width="3%" />
            <col width="3%" />
            <col width="12%" />
            <col width="10%" />
            <col width="12%" />
            <col />
        </colgroup>
        <thead>
            <tr>
                <td class="check"><input type="checkbox" title="Tout cocher / décocher" /></td>
                <td></td>
                <td></td>
                <td>Date</td>
                <td>Montant</td>
                <td>Solde cumulé</td>
                <th>Libellé</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="5"></td>
                <td>{$solde_initial|escape|html_money} {$config.monnaie}</td>
                <th>Solde au {$debut|format_sqlite_date_to_french}</th>
            </tr>
        {foreach from=$journal item="ligne"}
            <tr>
                <td class="check"><input type="checkbox" name="rapprocher[{$ligne.id}]" value="1" {if $ligne.date_rapprochement}checked="checked"{/if} /></td>
                <td class="num"><a href="{$admin_url}compta/operations/voir.php?id={$ligne.id}">{$ligne.id}</a></td>
                <td class="actions">
                {if $session->canAccess('compta', Garradin\Membres::DROIT_ADMIN)}
                    <a class="icn" href="{$admin_url}compta/operations/modifier.php?id={$ligne.id}" title="Modifier cette opération">✎</a>
                {/if}
                </td>
                <td>{$ligne.date|date_fr:'d/m/Y'}</td>
                <td>{if $ligne.compte_credit == $compte.id}-{else}+{/if}{$ligne.montant|escape|html_money}</td>
                <td>{$ligne.solde|escape|html_money}</td>
                <th>{$ligne.libelle}</th>
            </tr>
        {/foreach}
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5"></td>
                <td>{$solde_final|escape|html_money} {$config.monnaie}</td>
                <th>Solde au {$fin|format_sqlite_date_to_french}</th>
            </tr>
        </tfoot>
    </table>
    <p class="submit">
        {csrf_field key="compta_rapprocher_%s"|args:$compte.id}
        <input type="submit" name="save" value="Enregistrer" />
    </p>
</form>

{include file="admin/_foot.tpl"}