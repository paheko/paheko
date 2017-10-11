{include file="admin/_head.tpl" title="Modifier une catégorie" current="compta/categories"}

{include file="admin/compta/categories/_nav.tpl" current=null}

{form_errors}

<form method="post" action="{$self_url}">

    <fieldset>
        <legend>Modifier une catégorie</legend>
        <dl>
            <dt><label for="f_intitule">Intitulé</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="intitule" id="f_intitule" value="{form_field name=intitule data=$cat}" required="required" /></dd>
            <dt><label for="f_description">Description</label></dt>
            <dd><textarea name="description" id="f_description" rows="4" cols="70">{form_field name=description data=$cat}</textarea></dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="compta_edit_cat_%s"|args:$cat.id}
        <input type="submit" name="save" value="Enregistrer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}