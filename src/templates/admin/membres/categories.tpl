{include file="admin/_head.tpl" title="Catégories de membres" current="membres/categories"}

<table class="list">
    <thead>
        <th>Nom</th>
        <td>Membres</td>
        <td>Droits</td>
        <td></td>
    </thead>
    <tbody>
        {foreach from=$liste item="cat"}
            <tr>
                <th>{$cat.nom|escape}</th>
                <td class="num">{$cat.nombre|escape}</td>
                <td class="droits">
                    {format_droits droits=$cat}
                </td>
                <td class="actions">
                    <a class="icn" href="cat_modifier.php?id={$cat.id|escape}" title="Modifier">✎</a>
                    {if $cat.id != $user.id_categorie}
                    <a class="icn" href="cat_supprimer.php?id={$cat.id|escape}" title="Supprimer">✘</a>
                    {/if}
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
            <dd><input type="text" name="nom" id="f_nom" value="{form_field name=nom}" required="required" /></dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="new_cat"}
        <input type="submit" name="save" value="Enregistrer &rarr;" />
    </p>

</form>


{include file="admin/_foot.tpl"}