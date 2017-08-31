{include file="admin/_head.tpl" title="Ajouter un compte" current="compta/categories"}

{form_errors}

<form method="post" action="{$self_url}">

    <fieldset>
        <legend>Ajouter un compte</legend>
        <dl>
            <dt><label for="f_parent">Compte parent</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                {select_compte comptes=$comptes name="parent" create=true}
            </dd>
            <dt><label for="f_numero">Numéro de compte</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" size="10" name="numero" id="f_numero" value="{form_field name=numero}" required="required" /></dd>
            <dt><label for="f_libelle">Libellé</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="libelle" id="f_libelle" value="{form_field name=libelle}" required="required" /></dd>
            <dt><label for="f_position_1">Position</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            {foreach from=$positions item="pos" key="id"}
            <dd>
                <input type="radio" name="position" id="f_position_{$id}" value="{$id}" {if $position == $id}checked="checked"{/if} />
                <label for="f_position_{$id}">{$pos}</label>
            </dd>
            {/foreach}
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="compta_ajout_compte"}
        <input type="submit" name="add" value="Enregistrer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}