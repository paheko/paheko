{include file="admin/_head.tpl" title="Suivi des opérations" current="compta/gestion"}

<ul class="actions">
    <li class="recettes{if $type == Garradin_Compta_Categories::RECETTES} current{/if}"><a href="{$www_url}admin/compta/gestion.php?recettes">Recettes</a></li>
    <li class="depenses{if $type == Garradin_Compta_Categories::DEPENSES} current{/if}"><a href="{$www_url}admin/compta/gestion.php?depenses">Dépenses</a></li>
    <li class="autres{if $type == Garradin_Compta_Categories::AUTRES} current{/if}"><a href="{$www_url}admin/compta/gestion.php?autres">Autres</a></li>
    <li class="journal"><a href="{$www_url}admin/compta/journal.php">Journal général</a></li>
    <li class="grand_livre"><a href="{$www_url}admin/compta/grand_livre.php">Grand livre</a></li>
</ul>

{if $type != Garradin_Compta_Categories::AUTRES}
<form method="get" action="{$self_url}">
    <fieldset>
        <legend>Filtrer par catégorie</legend>
        <select name="cat" onchange="if (!this.value) location.href = '?{if $type == Garradin_Compta_Categories::RECETTES}recettes{else}depenses{/if}'; else this.form.submit();">
            <option value="">-- Toutes</option>
        {foreach from=$liste_cats item="cat"}
            <option value="{$cat.id|escape}"{if $cat.id == $categorie.id} selected="selected"{/if}>{$cat.intitule|escape}</option>
        {/foreach}
        </select>
        <input type="submit" value="OK" />
    </fieldset>
</form>
{/if}

<table class="list">
    <colgroup>
        <col width="3%" />
        <col width="3%" />
        <col width="12%" />
        <col width="10%" />
        <col />
        {if !$categorie && $type}
        <col width="20%" />
        {/if}
    </colgroup>
    <tbody>
    {foreach from=$journal item="ligne"}
        <tr>
            <td><a href="{$admin_url}compta/operation.php?id={$ligne.id|escape}">{$ligne.id|escape}</a></td>
            <td class="actions">
                <a class="icn" href="{$admin_url}compta/operation_modifier.php?id={$ligne.id|escape}">✎</a>
            </td>
            <td>{$ligne.date|date_fr:'d/m/Y'|escape}</td>
            <td>{$ligne.montant|escape_money} {$config.monnaie|escape}</td>
            <th>{$ligne.libelle|escape}</th>
            {if !$categorie && $type}
            <td>{$ligne.categorie|escape}</td>
            {/if}
        </tr>
    {foreachelse}
        <tr>
            <td colspan="3"></td>
            <td colspan="2">
                Aucune opération.
            </td>
            {if !$categorie && $type}<td></td>{/if}
        </tr>
    {/foreach}
    </tbody>
    <tfoot>
        <tr>
            <td></td>
            <td></td>
            <th>Total</th>
            <td>{$total|escape_money} {$config.monnaie|escape}</td>
            <td></td>
            {if !$categorie && $type}<td></td>{/if}
        </tr>
    </tfoot>
</table>

{include file="admin/_foot.tpl"}