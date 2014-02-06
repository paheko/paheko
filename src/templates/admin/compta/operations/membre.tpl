{include file="admin/_head.tpl" title="Écritures réalisées par le membre n°`$membre.id`" current="compta/gestion"}

<form method="get" action="{$self_url|escape}">
    <fieldset>
        <legend>Exercice à visualiser</legend>
        <p>
            <input type="hidden" name="id" value="{$membre.id|escape}" />
            <select name="exercice" id="f_exercice" onchange="this.form.submit();">
                {foreach from=$exercices item="e"}
                <option value="{$e.id|escape}" {form_field name="exercice" selected=$e.id default=$exercice}>{$e.libelle} —
                {if $e.cloture}Clôturé{else}En cours{/if}
                — Du {$e.debut|date_fr:'d/m/Y'} au {$e.fin|date_fr:'d/m/Y'}
                </option>
                {/foreach}
            </select>
        </p>
        <noscript>
            <p>
                <input type="submit" value="Visualiser &rarr;" />
            </p>
        </noscript>
    </fieldset>
</form>

<table class="list">
    <colgroup>
        <col width="3%" />
        <col width="3%" />
        <col width="12%" />
        <col width="10%" />
        <col />
    </colgroup>
    <thead>
        <tr>
            <td></td>
            <td></td>
            <td>Date</td>
            <td>Montant</td>
            <th>Libellé</th>
            <td>Compte débité</td>
            <td>Compte crédité</td>
        </tr>
    </thead>
    <tbody>
    {foreach from=$journal item="ligne"}
        <tr>
            <td><a href="{$admin_url}compta/operations/voir.php?id={$ligne.id|escape}">{$ligne.id|escape}</a></td>
            <td class="actions">
            {if $user.droits.compta >= Garradin\Membres::DROIT_ADMIN}
                <a class="icn" href="{$admin_url}compta/operations/modifier.php?id={$ligne.id|escape}">✎</a>
            {/if}
            </td>
            <td>{$ligne.date|format_sqlite_date_to_french|escape}</td>
            <td>{$ligne.montant|html_money}</td>
            <th>{$ligne.libelle|escape}</th>
            <td>{$ligne.compte_debit|escape} — {$ligne.compte_debit|get_nom_compte}</td>
            <td>{$ligne.compte_credit|escape} — {$ligne.compte_credit|get_nom_compte}</td>
        </tr>
    {/foreach}
    </tbody>
</table>

{include file="admin/_foot.tpl"}