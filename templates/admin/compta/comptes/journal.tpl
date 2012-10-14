{include file="admin/_head.tpl" title="Journal : `$compte.id` - `$compte.libelle`" current="compta/gestion"}

<table class="list">
    <colgroup>
        <col width="3%" />
        <col width="3%" />
        <col width="12%" />
        <col width="10%" />
        <col />
    </colgroup>
    <tbody>
    {foreach from=$journal item="ligne"}
        <tr>
            <td><a href="{$admin_url}compta/operations/voir.php?id={$ligne.id|escape}">{$ligne.id|escape}</a></td>
            <td class="actions">
            {if $user.droits.compta >= Garradin_Membres::DROIT_ADMIN}
                <a class="icn" href="{$admin_url}compta/operations/modifier.php?id={$ligne.id|escape}">✎</a>
            {/if}
            </td>
            <td>{$ligne.date|date_fr:'d/m/Y'|escape}</td>
            <td>{if $ligne.compte_credit == $compte.id}-{else}+{/if}{$ligne.montant|escape_money}</td>
            <th>{$ligne.libelle|escape}</th>
        </tr>
    {/foreach}
        <tr>
            <td></td>
            <td></td>
            <th>Solde</th>
            <td>{$solde|escape_money} {$config.monnaie|escape}</td>
            <td></td>
        </tr>
    </tbody>
</table>


{include file="admin/_foot.tpl"}