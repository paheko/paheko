{include file="admin/_head.tpl" title="Ajouter une catégorie" current="compta/categories"}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Ajouter une catégorie</legend>
        <dl>
            <dt><label for="f_type">Type</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                <select name="type" id="f_type" required="required">
                    <option value="{Garradin\Compta\Categories::RECETTES}"{if $type == Garradin\Compta\Categories::RECETTES} selected="selected"{/if}>Recette</option>
                    <option value="{Garradin\Compta\Categories::DEPENSES}"{if $type == Garradin\Compta\Categories::DEPENSES} selected="selected"{/if}>Dépense</option>
                </select>
            </dd>
            <dt><label for="f_intitule">Intitulé</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="intitule" id="f_intitule" value="{form_field name=intitule}" required="required" /></dd>
            <dt><label for="f_description">Description</label></dt>
            <dd><textarea name="description" id="f_description" rows="4" cols="30">{form_field name=description}</textarea></dd>
            <dt><label for="f_compte">Compte affecté</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                {select_compte comptes=$comptes name="compte"}
            </dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="compta_ajout_cat"}
        <input type="submit" name="add" value="Enregistrer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}