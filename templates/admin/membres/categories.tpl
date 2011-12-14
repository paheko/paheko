{include file="admin/_head.tpl" title="Catégories de membres" current="membres/categories"}

<table class="list">
    <thead>
        <th>Nom</th>
        <td>Description</td>
        <td>Cotisation</td>
        <td>Droits</td>
        <td></td>
    </thead>
    <tbody>
        {foreach from=$liste item="cat"}
            <tr>
                <th>{$cat.nom|escape}</th>
                <td>{$cat.description|truncate:60|escape}</td>
                <td>{$cat.montant_cotisation|escape} € pour {$cat.duree_cotisation|escape} mois</td>
                <td class="droits">
                    {format_droits droits=$cat}
                </td>
                <td class="actions">
                    <a href="cat_modifier.php?id={$cat.id|escape}">Modifier</a>
                </td>
            </tr>
        {/foreach}
    </tbody>
</table>

{include file="admin/_foot.tpl"}