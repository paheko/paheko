{include file="admin/_head.tpl" title="Suivi des opérations" current="compta/gestion"}

<ul class="actions">
    <li class="recettes{if $type == Garradin\Compta\Categories::RECETTES} current{/if}"><a href="{$www_url}admin/compta/operations/?recettes">Recettes</a></li>
    <li class="depenses{if $type == Garradin\Compta\Categories::DEPENSES} current{/if}"><a href="{$www_url}admin/compta/operations/?depenses">Dépenses</a></li>
    <li class="autres{if $type == Garradin\Compta\Categories::AUTRES} current{/if}"><a href="{$www_url}admin/compta/operations/?autres">Autres</a></li>
    {*<li><a href="{$www_url}admin/compta/operations/recherche.php">Recherche d'opération</a></li>*}
    {if $session->canAccess('compta', Garradin\Membres::DROIT_ADMIN)}
        <li><a href="{$www_url}admin/compta/operations/recherche_sql.php">Recherche par requête SQL</a></li>
    {/if}
</ul>

{if $type != Garradin\Compta\Categories::AUTRES}
<form method="get" action="{$self_url}">
    <fieldset>
        <legend>Filtrer par catégorie</legend>
        <select name="cat" onchange="if (!this.value) location.href = '?{if $type == Garradin\Compta\Categories::RECETTES}recettes{else}depenses{/if}'; else this.form.submit();">
            <option value="">-- Toutes</option>
        {foreach from=$liste_cats item="cat"}
            <option value="{$cat.id}"{if $cat.id == $categorie.id} selected="selected"{/if}>{$cat.intitule}</option>
        {/foreach}
        </select>
        <input type="submit" value="OK" />
    </fieldset>
</form>
{/if}

<table class="list">
    <colgroup>
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
            <td class="num"><a href="{$admin_url}compta/operations/voir.php?id={$ligne.id}">{$ligne.id}</a></td>
            <td>{$ligne.date|date_fr:'d/m/Y'}</td>
            <td>{$ligne.montant|escape|html_money} {$config.monnaie}</td>
            <th>{$ligne.libelle}</th>
            {if !$categorie && $type}
            <td>{$ligne.categorie}</td>
            {/if}
        </tr>
    {foreachelse}
        <tr>
            <td colspan="2"></td>
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
            <th>Total</th>
            <td>{$total|escape|html_money} {$config.monnaie}</td>
            <td></td>
            {if !$categorie && $type}<td></td>{/if}
        </tr>
    </tfoot>
</table>

{include file="admin/_foot.tpl"}