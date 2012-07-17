{include file="admin/_head.tpl" title="Ajouter un compte" current="compta/comptes"}

{if $error}
    <p class="error">
        {$error|escape}
    </p>
{/if}

<form method="post" action="{$self_url|escape}">

    <fieldset>
        <legend>Ajouter un compte</legend>
        <dl>
            <dt><label for="f_libelle">Libellé</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd><input type="text" name="libelle" id="f_libelle" value="{form_field name=libelle}" /></dd>
            <dt><label for="f_parent">Compte parent</label> <b title="(Champ obligatoire)">obligatoire</b></dt>
            <dd>
                {select_compte comptes=$comptes name="parent"}
            </dd>
        </dl>
    </fieldset>

    <p class="submit">
        {csrf_field key="compta_ajout_compte"}
        <input type="submit" name="add" value="Enregistrer &rarr;" />
    </p>

</form>

{include file="admin/_foot.tpl"}