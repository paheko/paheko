{include file="admin/_head.tpl" title="Modifier un compte" current="compta/categories"}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Modifier un compte</legend>
        <dl>
            <dt><label for="f_libelle">Libellé</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="libelle" id="f_libelle" value="{form_field name=libelle data=$compte}" required="required" /></dd>
            <dt><label for="f_position_1">Position</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            {foreach from=$positions item="pos" key="id"}
            <dd>
                <input type="radio" name="position" id="f_position_{$id|escape}" value="{$id|escape}" {if $position == $id}checked="checked"{/if} />
                <label for="f_position_{$id|escape}">{$pos|escape}</label>
            </dd>
            {/foreach}
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="compta_edit_compte_`$compte.id`"}
        <input type="submit" name="save" value="Enregistrer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}