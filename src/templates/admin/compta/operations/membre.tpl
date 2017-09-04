{include file="admin/_head.tpl" title="Écritures réalisées par le membre" current="compta/gestion"}

<ul class="actions">
    <li><a href="{$admin_url}membres/fiche.php?id={$membre.id}"><b>{$membre.identite}</b></a></li>
    <li><a href="{$admin_url}membres/modifier.php?id={$membre.id}">Modifier</a></li>
    {if $session->canAccess('membres', Garradin\Membres::DROIT_ADMIN)}
        <li><a href="{$admin_url}membres/supprimer.php?id={$membre.id}">Supprimer</a></li>
    {/if}
    <li><a href="{$admin_url}membres/cotisations.php?id={$membre.id}">Suivi des cotisations</a></li>
</ul>

<form method="get" action="{$self_url}">
    <fieldset>
        <legend>Exercice à visualiser</legend>
        <p>
            <input type="hidden" name="id" value="{$membre.id}" />
            <select name="exercice" id="f_exercice" onchange="this.form.submit();">
                {foreach from=$exercices item="e"}
                <option value="{$e.id}" {form_field name="exercice" selected=$e.id default=$exercice}>{$e.libelle} —
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

{if empty($journal)}
    <p class="alert">Aucune écriture comptable n'est associée à ce membre pour l'exercice demandé.</p>
{else}
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
            <td><a href="{$admin_url}compta/operations/voir.php?id={$ligne.id}">{$ligne.id}</a></td>
            <td class="actions">
            {if $session->canAccess('compta', Garradin\Membres::DROIT_ADMIN)}
                <a class="icn" href="{$admin_url}compta/operations/modifier.php?id={$ligne.id}" title="Modifier cette opération">✎</a>
            {/if}
            </td>
            <td>{$ligne.date|format_sqlite_date_to_french}</td>
            <td>{$ligne.montant|escape|html_money}</td>
            <th>{$ligne.libelle}</th>
            <td>{$ligne.compte_debit} — {$ligne.compte_debit|get_nom_compte}</td>
            <td>{$ligne.compte_credit} — {$ligne.compte_credit|get_nom_compte}</td>
        </tr>
    {/foreach}
    </tbody>
</table>
{/if}

{include file="admin/_foot.tpl"}