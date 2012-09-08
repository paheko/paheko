{include file="admin/_head.tpl" title="Journal : `$compte.id` - `$compte.libelle`" current="compta/gestion"}

<table class="list">
    <colgroup>
        <col width="3%" />
        <col width="3%" />
        <col width="12%" />
        <col width="10%" />
        <col />
    </colgroup>
{foreach from=$journal item="ligne"}
    <tr>
        <td><a href="{$admin_url}compta/operation.php?id={$ligne.id|escape}">{$ligne.id|escape}</a></td>
        <td class="actions">
            <a class="icn" href="{$admin_url}compta/operation_modifier.php?id={$ligne.id|escape}">✎</a>
        </td>
        <td>{$ligne.date|date_fr:'d/m/Y'|escape}</td>
        <td>{if $ligne.compte_credit == $compte.id}-{$ligne.montant|escape}{else}+{$ligne.montant|escape}{/if}</td>
        <th>{$ligne.libelle|escape}</th>
    </tr>
{/foreach}
    <tbody>
        <tr>
            <td></td>
            <td></td>
            <th>Solde</th>
            <td>{$solde|escape} {$config.monnaie|escape}</td>
            <td></td>
        </tr>
    </tbody>
</table>


{include file="admin/_foot.tpl"}