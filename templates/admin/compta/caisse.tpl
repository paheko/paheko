{include file="admin/_head.tpl" title="Caisse - Journal" current="compta/banques"}

<ul class="actions">
    <li><a href="{$www_url}admin/compta/banques.php">Comptes bancaires</a></li>
    <li class="current"><a href="{$www_url}admin/compta/caisse.php">Caisse</a></li>
</ul>

<table class="list">
{foreach from=$journal item="ligne"}
    <tr>
        <td>{$ligne.date|escape}</td>
        <th>{$ligne.libelle|escape}</th>
        <td>{if $ligne.compte_credit == Garradin_Compta_Comptes::CAISSE}-{$ligne.montant|escape}{else}+{$ligne.montant|escape}{/if}</td>
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