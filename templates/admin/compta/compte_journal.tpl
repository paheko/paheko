{include file="admin/_head.tpl" title="Journal : `$compte.id` - `$compte.libelle`" current="compta/gestion"}

<table class="list">
{foreach from=$journal item="ligne"}
    <tr>
        <td>{$ligne.date|escape}</td>
        <th>{$ligne.libelle|escape}</th>
        <td>{if $ligne.compte_credit == $compte.id}-{$ligne.montant|escape}{else}+{$ligne.montant|escape}{/if}</td>
    </tr>
{/foreach}
    <tbody>
        <tr>
            <td></td>
            <th>Solde</th>
            <td>{$solde|escape} {$config.monnaie|escape}</td>
        </tr>
    </tbody>
</table>


{include file="admin/_foot.tpl"}