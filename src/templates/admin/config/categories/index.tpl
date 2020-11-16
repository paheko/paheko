{include file="admin/_head.tpl" title="Catégories de membres" current="config"}

{include file="admin/config/_menu.tpl" current="categories"}

<table class="list">
    <thead>
        <th>Nom</th>
        <td class="num">Membres</td>
        <td>Droits</td>
        <td></td>
    </thead>
    <tbody>
        {foreach from=$liste item="cat"}
            <tr>
                <th>{$cat.nom}</th>
                <td class="num">{$cat.nombre}</td>
                <td class="droits">
                    {format_droits droits=$cat}
                </td>
                <td class="actions">
                    {if $cat.id != $user.id_categorie}
                        {linkbutton shape="delete" label="Supprimer" href="supprimer.php?id=%d"|args:$cat.id}
                    {/if}
                    {linkbutton shape="edit" label="Modifier" href="modifier.php?id=%d"|args:$cat.id}
                    {linkbutton shape="users" label="Liste des membres" href="!membres/?cat=%d"|args:$cat.id}
                </td>
            </tr>
        {/foreach}
    </tbody>
</table>

<form method="post" action="{$self_url}">

    <fieldset>
        <legend>Ajouter une catégorie</legend>
        <dl>
            <dt><label for="f_nom">Nom</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="nom" id="f_nom" value="{form_field name=nom}" required="required" /></dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="new_cat"}
        {button type="submit" name="save" label="Ajouter" shape="right" class="main"}
    </p>

</form>


{include file="admin/_foot.tpl"}