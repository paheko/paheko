{include file="admin/_head.tpl" title="Rapprochement — `$compte.id`" current="compta/banques" js=1}

<ul class="actions">
    <li><a href="{$www_url}admin/compta/banques/">Comptes bancaires</a></li>
    <li><a href="{$www_url}admin/compta/comptes/journal.php?id={Garradin\Compta\Comptes::CAISSE}&amp;suivi">Journal de caisse</a></li>
    <li class="current"><a href="{$admin_url}compta/banques/rapprochement.php?id={$compte.id|escape}">Rapprochement</a></li>
</ul>

<form method="get" action="{$self_url|escape}">
    <fieldset>
        <legend>Période de rapprochement</legend>
        <dl>
            <dt><label for="f_debut">Début</label></dt>
            <dd><input type="date" name="debut" id="f_debut" value="{form_field name='debut' default=$debut}" /></dd>
            <dt><label for="f_fin">Fin</label></dt>
            <dd><input type="date" name="fin" id="f_fin" value="{form_field name='fin' default=$fin}" /></dd>
        </dl>
        <p>
            <input type="hidden" name="id" value="{$compte.id|escape}" />
            <input type="submit" value="Afficher" />
        </p>
    </fieldset>
</form>

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
            <td>{if $ligne.compte_credit == $compte.id}-{else}+{/if}{$ligne.montant|html_money}</td>
            <td>{$ligne.solde|html_money}</td>
            <th>{$ligne.libelle|escape}</th>
        </tr>
    {/foreach}
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3"></td>
            <th>Solde</th>
            <td colspan="2">{*{$solde|html_money} {$config.monnaie|escape}*}</td>
        </tr>
    </tfoot>
</table>

{include file="admin/_foot.tpl"}