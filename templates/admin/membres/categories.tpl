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
                    | <a href="cat_supprimer.php?id={$cat.id|escape}">Supprimer</a>
                </td>
            </tr>
        {/foreach}
    </tbody>
</table>

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Ajouter une catégorie</legend>
        <dl>
            <dt><label for="f_nom">Nom</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="nom" id="f_nom" value="{form_field name=nom}" /></dd>
            <dt><label for="f_montant_cotisation">Montant cotisation</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="number" name="montant_cotisation" step="0.50" id="f_montant_cotisation" value="{form_field default=20 name=montant_cotisation}" /></dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="new_cat"}
        <input type="submit" name="save" value="Enregistrer &rarr;" />
    </p>

</form>


{include file="admin/_foot.tpl"}